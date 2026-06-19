<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\MPenggunaanInventaris;

/**
 * @internal
 */
final class PenggunaanInventarisModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = true;
    protected $migrateOnce = true;
    protected $migrate = true;
    protected $DBGroup = 'tests';
    protected $namespace = null;

    public function testGetByTransaksiReturnsEmptyForNoUsage(): void
    {
        $model = new MPenggunaanInventaris();
        // Transaction 1 has no inventory usage seeded
        $result = $model->getByTransaksi(1);

        $this->assertIsArray($result);
    }

    public function testGetByTransaksiReturnsJoinedData(): void
    {
        $model = new MPenggunaanInventaris();

        // Insert a usage record first
        $model->insert([
            'id_transaksi'  => 1,
            'id_inventaris' => 1,
            'jumlah_pakai'  => 0.25,
            'keterangan'    => 'Test usage',
        ]);

        $result = $model->getByTransaksi(1);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('nama_barang', $result[0]);
        $this->assertArrayHasKey('satuan', $result[0]);
        $this->assertEquals('Deterjen Bubuk', $result[0]['nama_barang']);
    }

    public function testGetByTransaksiReturnsEmptyForNonexistent(): void
    {
        $model = new MPenggunaanInventaris();
        $result = $model->getByTransaksi(9999);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
