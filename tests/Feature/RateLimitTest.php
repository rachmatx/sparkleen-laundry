<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Security tests for login rate limiting (brute-force protection).
 *
 * @internal
 */
final class RateLimitTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = true;
    protected $migrateOnce = true;
    protected $migrate = true;
    protected $DBGroup = 'tests';
    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear throttle state
        $throttle = service('loginThrottle');
        $throttle->clear('127.0.0.1', 'kasir_test');
    }

    protected function tearDown(): void
    {
        // Clean up throttle
        $throttle = service('loginThrottle');
        $throttle->clear('127.0.0.1', 'kasir_test');
        parent::tearDown();
    }

    public function testLoginThrottleBlocksAfterMaxAttempts(): void
    {
        $maxAttempts = config('AuthThrottle')->maxAttempts; // 5

        // Record max failures
        for ($i = 0; $i < $maxAttempts; $i++) {
            $result = $this->call('POST', 'doLogin', [
                'username' => 'kasir_test',
                'password' => 'wrong_password_' . $i,
                csrf_token() => csrf_hash(),
            ]);
            $result->assertRedirectTo(site_url('login'));
        }

        // Next attempt should show blocked message
        $result = $this->call('POST', 'doLogin', [
            'username' => 'kasir_test',
            'password' => 'password123',
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('error');

        // Verify the error message contains blocking text
        $error = session()->getFlashdata('error');
        $this->assertStringContainsString('Terlalu banyak percobaan login gagal', $error);
        $result->assertSessionMissing('isLoggedIn');
    }

    public function testSuccessfulLoginClearsThrottle(): void
    {
        // Record a few failures first
        for ($i = 0; $i < 3; $i++) {
            $this->call('POST', 'doLogin', [
                'username' => 'kasir_test',
                'password' => 'wrong_pass',
                csrf_token() => csrf_hash(),
            ]);
        }

        $this->assertStringContainsString('Sisa percobaan: 2', service('loginThrottle')->getFailureMessage('127.0.0.1', 'kasir_test'));

        // Now login successfully
        $result = $this->call('POST', 'doLogin', [
            'username' => 'kasir_test',
            'password' => 'password123',
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirectTo(site_url('dashboard'));

        // Throttle should be cleared — user should not be blocked
        $throttle = service('loginThrottle', false);
        $this->assertFalse($throttle->isBlocked('127.0.0.1', 'kasir_test'));
        $this->assertStringContainsString('Sisa percobaan: 5', $throttle->getFailureMessage('127.0.0.1', 'kasir_test'));
        $result->assertSessionHas('isLoggedIn', true);
    }

    public function testFailedLoginRecordsActivityLog(): void
    {
        $this->call('POST', 'doLogin', [
            'username' => 'kasir_test',
            'password' => 'wrong_password',
            csrf_token() => csrf_hash(),
        ]);

        // Verify activity log entry
        $this->seeInDatabase('tb_log_aktivitas', [
            'aksi'           => 'Gagal Login',
            'nama_referensi' => 'kasir_test',
        ]);
    }

    public function testBlockedLoginRecordsSecurityLog(): void
    {
        $maxAttempts = config('AuthThrottle')->maxAttempts;

        // Trigger blocking
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->call('POST', 'doLogin', [
                'username' => 'kasir_test',
                'password' => 'wrong_pass_' . $i,
                csrf_token() => csrf_hash(),
            ]);
        }

        // One more attempt while blocked
        $this->call('POST', 'doLogin', [
            'username' => 'kasir_test',
            'password' => 'still_wrong',
            csrf_token() => csrf_hash(),
        ]);

        // Verify security log
        $this->seeInDatabase('tb_log_aktivitas', [
            'modul' => 'Keamanan',
            'aksi'  => 'Blokir Login',
        ]);
    }
}
