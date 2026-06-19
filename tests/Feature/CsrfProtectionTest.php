<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Security tests for CSRF protection.
 * Verifies that POST requests without CSRF tokens are rejected.
 *
 * @internal
 */
final class CsrfProtectionTest extends CIUnitTestCase
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
        // NOTE: We do NOT disable CSRF here — that's what we're testing!
    }

    public function testPostWithoutCsrfIsRejected(): void
    {
        $this->withSession([
            'isLoggedIn'    => true,
            'id_karyawan'   => 1,
            'nama_karyawan' => 'Kasir Test',
            'username'      => 'kasir_test',
            'level'         => 'kasir',
        ]);

        // POST without CSRF token should fail
        // CI4 with redirect=true will redirect back with 302/403
        $result = $this->call('post', 'transaksi/simpanTransaksi', [
            'id_pelanggan' => 1,
        ]);

        // CSRF protection with redirect=true sends a redirect (302)
        // The response should NOT be 200 OK
        $this->assertTrue(
            $result->response()->getStatusCode() !== 200,
            'POST without CSRF should not return 200 OK'
        );
    }

    public function testGetRequestsNotAffectedByCsrf(): void
    {
        $this->withSession([
            'isLoggedIn'    => true,
            'id_karyawan'   => 1,
            'nama_karyawan' => 'Kasir Test',
            'username'      => 'kasir_test',
            'level'         => 'kasir',
        ]);

        // GET requests should work fine without CSRF
        $result = $this->call('get', 'transaksi/daftar');
        $result->assertOK();
    }

    public function testLoginGetPageNotAffectedByCsrf(): void
    {
        // Login page is excluded from auth filter and doesn't need CSRF
        $result = $this->call('get', 'login');
        $result->assertOK();
    }
}
