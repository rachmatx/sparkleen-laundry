<?php

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Security tests for login rate limiting (brute-force protection).
 *
 * @internal
 */
final class RateLimitTest extends FeatureTestCase
{
    use DatabaseTestTrait;

    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = false;
    protected $migrateOnce = false;
    protected $migrate = false;
    protected $DBGroup = 'tests';

    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF for login POST tests
        $filterConfig = config('Filters');
        $filterConfig->methods = [];
        \CodeIgniter\Config\Factories::injectMock('config', 'Filters', $filterConfig);

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
            $result = $this->call('post', 'doLogin', [
                'username' => 'kasir_test',
                'password' => 'wrong_password_' . $i,
            ]);
            $result->assertRedirectTo(site_url('login'));
        }

        // Next attempt should show blocked message
        $result = $this->call('post', 'doLogin', [
            'username' => 'kasir_test',
            'password' => 'wrong_password_again',
        ]);

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('error');

        // Verify the error message contains blocking text
        $error = session()->getFlashdata('error');
        $this->assertStringContainsString('Terlalu banyak percobaan login gagal', $error);
    }

    public function testSuccessfulLoginClearsThrottle(): void
    {
        // Record a few failures first
        for ($i = 0; $i < 3; $i++) {
            $this->call('post', 'doLogin', [
                'username' => 'kasir_test',
                'password' => 'wrong_pass',
            ]);
        }

        // Now login successfully
        $result = $this->call('post', 'doLogin', [
            'username' => 'kasir_test',
            'password' => 'password123',
        ]);

        $result->assertRedirectTo(site_url('dashboard'));

        // Throttle should be cleared — user should not be blocked
        $throttle = service('loginThrottle', false);
        $this->assertFalse($throttle->isBlocked('127.0.0.1', 'kasir_test'));
    }

    public function testFailedLoginRecordsActivityLog(): void
    {
        $this->call('post', 'doLogin', [
            'username' => 'kasir_test',
            'password' => 'wrong_password',
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
            $this->call('post', 'doLogin', [
                'username' => 'kasir_test',
                'password' => 'wrong_pass_' . $i,
            ]);
        }

        // One more attempt while blocked
        $this->call('post', 'doLogin', [
            'username' => 'kasir_test',
            'password' => 'still_wrong',
        ]);

        // Verify security log
        $this->seeInDatabase('tb_log_aktivitas', [
            'modul' => 'Keamanan',
            'aksi'  => 'Blokir Login',
        ]);
    }
}
