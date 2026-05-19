<?php

namespace App\Libraries;

use Config\AuthThrottle as AuthThrottleConfig;

class LoginThrottle
{
    protected AuthThrottleConfig $config;

    protected string $cachePrefix = 'login_throttle_';

    public function __construct(?AuthThrottleConfig $config = null)
    {
        $this->config = $config ?? config('AuthThrottle');
    }

    public function isBlocked(string $ip, ?string $identifier = null): bool
    {
        if ($this->isScopeBlocked($ip, 'ip')) {
            return true;
        }

        if ($identifier !== null && $identifier !== '') {
            return $this->isScopeBlocked($this->normalizeIdentifier($identifier), 'user');
        }

        return false;
    }

    public function recordFailure(string $ip, ?string $identifier = null): void
    {
        $this->increment($ip, 'ip');

        if ($identifier !== null && $identifier !== '') {
            $this->increment($this->normalizeIdentifier($identifier), 'user');
        }
    }

    public function clear(string $ip, ?string $identifier = null): void
    {
        cache()->delete($this->cacheKey('ip', $ip));

        if ($identifier !== null && $identifier !== '') {
            cache()->delete($this->cacheKey('user', $this->normalizeIdentifier($identifier)));
        }
    }

    public function getRetryAfterSeconds(string $ip, ?string $identifier = null): int
    {
        $retry = 0;

        $retry = max($retry, $this->getScopeRetryAfter($ip, 'ip'));

        if ($identifier !== null && $identifier !== '') {
            $retry = max($retry, $this->getScopeRetryAfter($this->normalizeIdentifier($identifier), 'user'));
        }

        return $retry;
    }

    public function getBlockedMessage(string $ip, ?string $identifier = null): string
    {
        $seconds = $this->getRetryAfterSeconds($ip, $identifier);

        if ($seconds <= 0) {
            return 'Terlalu banyak percobaan login gagal. Silakan coba lagi nanti.';
        }

        if ($seconds < 60) {
            return "Terlalu banyak percobaan login gagal. Coba lagi dalam {$seconds} detik.";
        }

        $minutes = (int) ceil($seconds / 60);

        return "Terlalu banyak percobaan login gagal. Coba lagi dalam sekitar {$minutes} menit.";
    }

    public function getFailureMessage(string $ip, ?string $identifier = null): string
    {
        $base = 'Username/email atau password salah.';

        if (! $this->config->showRemainingAttempts) {
            return $base;
        }

        $leftIp = $this->attemptsRemaining($ip, 'ip');

        $leftUser = $identifier !== null && $identifier !== ''
            ? $this->attemptsRemaining($this->normalizeIdentifier($identifier), 'user')
            : $leftIp;

        $left = min($leftIp, $leftUser);

        if ($left <= 0) {
            return $base;
        }

        return $base . " Sisa percobaan: {$left}.";
    }

    protected function attemptsRemaining(string $value, string $scope): int
    {
        $record = $this->getRecord($value, $scope);

        if ($record['locked_until'] > time()) {
            return 0;
        }

        return max(0, $this->config->maxAttempts - $record['attempts']);
    }

    protected function isScopeBlocked(string $value, string $scope): bool
    {
        $record = $this->getRecord($value, $scope);

        return $record['locked_until'] > time();
    }

    protected function increment(string $value, string $scope): void
    {
        $key    = $this->cacheKey($scope, $value);
        $record = $this->getRecord($value, $scope, false);

        $record['attempts']++;

        if ($record['attempts'] >= $this->config->maxAttempts) {
            $record['locked_until'] = time() + $this->config->lockoutSeconds;
        }

        cache()->save($key, $record, $this->config->lockoutSeconds);
    }

    /**
     * @return array{attempts: int, locked_until: int}
     */
    protected function getRecord(string $value, string $scope, bool $resetIfExpired = true): array
    {
        $key    = $this->cacheKey($scope, $value);
        $record = cache()->get($key);

        if (! is_array($record)) {
            return ['attempts' => 0, 'locked_until' => 0];
        }

        if ($resetIfExpired && ($record['locked_until'] ?? 0) > 0 && time() >= $record['locked_until']) {
            cache()->delete($key);

            return ['attempts' => 0, 'locked_until' => 0];
        }

        return [
            'attempts'     => (int) ($record['attempts'] ?? 0),
            'locked_until' => (int) ($record['locked_until'] ?? 0),
        ];
    }

    protected function getScopeRetryAfter(string $value, string $scope): int
    {
        $record = $this->getRecord($value, $scope, false);

        if (($record['locked_until'] ?? 0) <= time()) {
            return 0;
        }

        return $record['locked_until'] - time();
    }

    protected function cacheKey(string $scope, string $value): string
    {
        return $this->cachePrefix . $scope . '_' . md5($value);
    }

    protected function normalizeIdentifier(string $identifier): string
    {
        return strtolower(trim($identifier));
    }
}
