<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Feature tests for Authentication (Auth controller).
 *
 * @internal
 */
final class AuthFeatureTest extends CIUnitTestCase
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

        // Disable CSRF for feature tests
        $filterConfig = config('Filters');
        $filterConfig->methods = [];
        \CodeIgniter\Config\Factories::injectMock('config', 'Filters', $filterConfig);

        // Clear any throttle cache
        $throttle = service('loginThrottle');
        $throttle->clear('127.0.0.1', 'kasir_test');
        $throttle->clear('127.0.0.1', 'nonaktif_test');
        $throttle->clear('127.0.0.1', 'wrong_user');
    }

    public function testLoginPageLoads(): void
    {
        $result = $this->call('get', 'login');
        $result->assertOK();
    }

    public function testSuccessfulLoginRedirectsToDashboard(): void
    {
        $result = $this->call('post', 'doLogin', [
            'username' => 'kasir_test',
            'password' => 'password123',
        ]);

        $result->assertRedirectTo(site_url('dashboard'));
        $result->assertSessionHas('isLoggedIn', true);
        $result->assertSessionHas('level', 'kasir');
    }

    public function testFailedLoginShowsError(): void
    {
        $result = $this->call('post', 'doLogin', [
            'username' => 'kasir_test',
            'password' => 'wrong_password',
        ]);

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('error');
    }

    public function testEmptyCredentialsShowsError(): void
    {
        $result = $this->call('post', 'doLogin', [
            'username' => '',
            'password' => '',
        ]);

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('error', 'Username/email dan password wajib diisi.');
    }

    public function testDeactivatedAccountCannotLogin(): void
    {
        $result = $this->call('post', 'doLogin', [
            'username' => 'nonaktif_test',
            'password' => 'password123',
        ]);

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi Admin.');
    }

    public function testLoginWithEmailWorks(): void
    {
        $result = $this->call('post', 'doLogin', [
            'username' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $result->assertRedirectTo(site_url('dashboard'));
        $result->assertSessionHas('isLoggedIn', true);
        $result->assertSessionHas('level', 'admin');
    }

    public function testLogoutDestroysSession(): void
    {
        // First login
        $this->withSession([
            'isLoggedIn'    => true,
            'id_karyawan'   => 1,
            'nama_karyawan' => 'Kasir Test',
            'username'      => 'kasir_test',
            'level'         => 'kasir',
        ]);

        $result = $this->call('get', 'logout');
        $result->assertRedirectTo(site_url('login'));
    }

    public function testAuthFilterRedirectsUnauthenticated(): void
    {
        // Re-enable auth filter (it's in globals)
        $result = $this->call('get', 'dashboard');

        // Should redirect to login
        $result->assertRedirectTo(site_url('login'));
    }

    public function testAlreadyLoggedInUserRedirectedFromLogin(): void
    {
        $this->withSession([
            'isLoggedIn' => true,
            'id_karyawan' => 1,
            'nama_karyawan' => 'Kasir Test',
            'username' => 'kasir_test',
            'level' => 'kasir',
        ]);

        $result = $this->call('get', 'login');
        $result->assertRedirectTo(site_url('dashboard'));
    }
}
