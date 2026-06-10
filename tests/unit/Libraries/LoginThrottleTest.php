<?php

namespace Tests\Unit\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\LoginThrottle;
use Config\AuthThrottle as AuthThrottleConfig;

/**
 * Tests for the LoginThrottle library.
 * LoginThrottle uses CI4's cache system for rate limiting.
 *
 * @internal
 */
final class LoginThrottleTest extends CIUnitTestCase
{
    private LoginThrottle $throttle;
    private AuthThrottleConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new AuthThrottleConfig();
        $this->config->maxAttempts = 5;
        $this->config->lockoutSeconds = 60;
        $this->config->showRemainingAttempts = true;

        $this->throttle = new LoginThrottle($this->config);

        // Clear any leftover cache from previous tests
        $this->throttle->clear('127.0.0.1', 'testuser');
        $this->throttle->clear('127.0.0.2', 'testuser2');
    }

    protected function tearDown(): void
    {
        // Clean up
        $this->throttle->clear('127.0.0.1', 'testuser');
        $this->throttle->clear('127.0.0.2', 'testuser2');
        parent::tearDown();
    }

    public function testIsBlockedReturnsFalseInitially(): void
    {
        $this->assertFalse($this->throttle->isBlocked('127.0.0.1', 'testuser'));
    }

    public function testIsBlockedReturnsFalseAfterFewFailures(): void
    {
        // Record 3 failures (below threshold of 5)
        for ($i = 0; $i < 3; $i++) {
            $this->throttle->recordFailure('127.0.0.1', 'testuser');
        }

        $this->assertFalse($this->throttle->isBlocked('127.0.0.1', 'testuser'));
    }

    public function testIsBlockedReturnsTrueAfterMaxAttempts(): void
    {
        for ($i = 0; $i < $this->config->maxAttempts; $i++) {
            $this->throttle->recordFailure('127.0.0.1', 'testuser');
        }

        $this->assertTrue($this->throttle->isBlocked('127.0.0.1', 'testuser'));
    }

    public function testIsBlockedByIpOnly(): void
    {
        // Block by IP (no identifier)
        for ($i = 0; $i < $this->config->maxAttempts; $i++) {
            $this->throttle->recordFailure('127.0.0.1');
        }

        $this->assertTrue($this->throttle->isBlocked('127.0.0.1'));
        // Different IP should not be blocked
        $this->assertFalse($this->throttle->isBlocked('127.0.0.2'));
    }

    public function testClearResetsState(): void
    {
        // Block the user
        for ($i = 0; $i < $this->config->maxAttempts; $i++) {
            $this->throttle->recordFailure('127.0.0.1', 'testuser');
        }
        $this->assertTrue($this->throttle->isBlocked('127.0.0.1', 'testuser'));

        // Clear and verify
        $this->throttle->clear('127.0.0.1', 'testuser');
        $this->assertFalse($this->throttle->isBlocked('127.0.0.1', 'testuser'));
    }

    public function testGetBlockedMessageFormatsSeconds(): void
    {
        for ($i = 0; $i < $this->config->maxAttempts; $i++) {
            $this->throttle->recordFailure('127.0.0.1', 'testuser');
        }

        $message = $this->throttle->getBlockedMessage('127.0.0.1', 'testuser');

        // Should contain retry time info
        $this->assertStringContainsString('Terlalu banyak percobaan login gagal', $message);
    }

    public function testGetFailureMessageShowsRemainingAttempts(): void
    {
        // Record 2 failures (3 remaining)
        $this->throttle->recordFailure('127.0.0.1', 'testuser');
        $this->throttle->recordFailure('127.0.0.1', 'testuser');

        $message = $this->throttle->getFailureMessage('127.0.0.1', 'testuser');

        $this->assertStringContainsString('Username/email atau password salah', $message);
        $this->assertStringContainsString('Sisa percobaan: 3', $message);
    }

    public function testGetFailureMessageHidesRemainingWhenDisabled(): void
    {
        $config = new AuthThrottleConfig();
        $config->maxAttempts = 5;
        $config->lockoutSeconds = 60;
        $config->showRemainingAttempts = false;

        $throttle = new LoginThrottle($config);
        $throttle->clear('127.0.0.1', 'testuser');

        $throttle->recordFailure('127.0.0.1', 'testuser');

        $message = $throttle->getFailureMessage('127.0.0.1', 'testuser');

        $this->assertEquals('Username/email atau password salah.', $message);
        $this->assertStringNotContainsString('Sisa percobaan', $message);

        $throttle->clear('127.0.0.1', 'testuser');
    }

    public function testRetryAfterReturnsPositiveWhenBlocked(): void
    {
        for ($i = 0; $i < $this->config->maxAttempts; $i++) {
            $this->throttle->recordFailure('127.0.0.1', 'testuser');
        }

        $retry = $this->throttle->getRetryAfterSeconds('127.0.0.1', 'testuser');
        $this->assertGreaterThan(0, $retry);
        $this->assertLessThanOrEqual($this->config->lockoutSeconds, $retry);
    }

    public function testRetryAfterReturnsZeroWhenNotBlocked(): void
    {
        $retry = $this->throttle->getRetryAfterSeconds('127.0.0.1', 'testuser');
        $this->assertEquals(0, $retry);
    }
}
