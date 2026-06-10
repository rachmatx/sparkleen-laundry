<?php

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Feature tests for Role-Based Access Control (RBAC).
 * Tests that kasir/admin/pemilik roles have correct access.
 *
 * @internal
 */
final class RbacFeatureTest extends FeatureTestCase
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

        $filterConfig = config('Filters');
        $filterConfig->methods = [];
        \CodeIgniter\Config\Factories::injectMock('config', 'Filters', $filterConfig);
    }

    private function loginAs(string $role): void
    {
        $map = [
            'kasir'   => ['id' => 1, 'nama' => 'Kasir Test', 'user' => 'kasir_test'],
            'admin'   => ['id' => 2, 'nama' => 'Admin Test', 'user' => 'admin_test'],
            'pemilik' => ['id' => 3, 'nama' => 'Pemilik Test', 'user' => 'pemilik_test'],
        ];

        $this->session([
            'isLoggedIn'    => true,
            'id_karyawan'   => $map[$role]['id'],
            'nama_karyawan' => $map[$role]['nama'],
            'username'      => $map[$role]['user'],
            'level'         => $role,
        ]);
    }

    // ── Kasir Access ──

    public function testKasirCanAccessTransaksiDaftar(): void
    {
        $this->loginAs('kasir');
        $result = $this->call('get', 'transaksi/daftar');
        $result->assertOK();
    }

    public function testKasirCanAccessNewTransaksi(): void
    {
        $this->loginAs('kasir');
        $result = $this->call('get', 'transaksi');
        $result->assertOK();
    }

    public function testKasirCanAccessDashboard(): void
    {
        $this->loginAs('kasir');
        $result = $this->call('get', 'dashboard');
        $result->assertOK();
    }

    // ── Pemilik Access ──

    public function testPemilikCanAccessDashboard(): void
    {
        $this->loginAs('pemilik');
        $result = $this->call('get', 'dashboard');
        $result->assertOK();
    }

    public function testPemilikCanAccessLogAktivitas(): void
    {
        $this->loginAs('pemilik');
        $result = $this->call('get', 'log-aktivitas');
        $result->assertOK();
    }

    // ── Admin Access ──

    public function testAdminCanAccessInventaris(): void
    {
        $this->loginAs('admin');
        $result = $this->call('get', 'inventaris');
        $result->assertOK();
    }

    public function testAdminCanAccessPelanggan(): void
    {
        $this->loginAs('admin');
        $result = $this->call('get', 'pelanggan');
        $result->assertOK();
    }

    public function testAdminCanAccessTransaksiDaftar(): void
    {
        $this->loginAs('admin');
        $result = $this->call('get', 'transaksi/daftar');
        $result->assertOK();
    }
}
