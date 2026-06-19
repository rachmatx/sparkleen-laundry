<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\MLayananInventaris;

/**
 * @internal
 */
final class LayananInventarisModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = true;
    protected $migrateOnce = true;
    protected $migrate = true;
    protected $DBGroup = 'tests';
    protected $namespace = null;

    public function testGetResepByLayananReturnsJoinedData(): void
    {
        $model = new MLayananInventaris();
        $resep = $model->getResepByLayanan(1); // Cuci Reguler

        $this->assertIsArray($resep);
        $this->assertCount(2, $resep); // Deterjen + Pewangi

        foreach ($resep as $r) {
            $this->assertArrayHasKey('nama_barang', $r);
            $this->assertArrayHasKey('satuan', $r);
            $this->assertArrayHasKey('stok', $r);
            $this->assertArrayHasKey('jumlah_default', $r);
        }
    }

    public function testGetResepByLayananReturnsEmptyForNoRecipe(): void
    {
        $model = new MLayananInventaris();
        $resep = $model->getResepByLayanan(2); // Cuci Ekspres has no recipe seeded

        $this->assertIsArray($resep);
        $this->assertEmpty($resep);
    }

    public function testGetResepByLayananReturnsEmptyForNonexistent(): void
    {
        $model = new MLayananInventaris();
        $resep = $model->getResepByLayanan(9999);

        $this->assertIsArray($resep);
        $this->assertEmpty($resep);
    }
}
