<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\MInventaris;

/**
 * @internal
 */
final class InventarisModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = false;
    protected $migrateOnce = false;
    protected $migrate = false;
    protected $DBGroup = 'tests';

    public function testKurangiStokReducesCorrectly(): void
    {
        $model = new MInventaris();
        
        // Item 1 starts with stok = 50
        $result = $model->kurangiStok(1, 10.0);
        $this->assertTrue($result);

        $item = $model->find(1);
        $this->assertEquals(40.0, (float) $item['stok']);
    }

    public function testKurangiStokDoesNotGoNegative(): void
    {
        $model = new MInventaris();
        
        // Try to deduct more than available (50)
        $result = $model->kurangiStok(1, 100.0);
        $this->assertTrue($result);

        $item = $model->find(1);
        $this->assertEquals(0.0, (float) $item['stok']); // Floored at 0
    }

    public function testKurangiStokReturnsFalseForNonexistent(): void
    {
        $model = new MInventaris();
        $result = $model->kurangiStok(9999, 5.0);

        $this->assertFalse($result);
    }

    public function testTambahStokIncreasesCorrectly(): void
    {
        $model = new MInventaris();
        
        // Item 2 starts with stok = 20
        $result = $model->tambahStok(2, 15.0);
        $this->assertTrue($result);

        $item = $model->find(2);
        $this->assertEquals(35.0, (float) $item['stok']);
    }

    public function testTambahStokReturnsFalseForNonexistent(): void
    {
        $model = new MInventaris();
        $result = $model->tambahStok(9999, 5.0);

        $this->assertFalse($result);
    }

    public function testGetStokKritisReturnsLowStockOnly(): void
    {
        $model = new MInventaris();
        $kritis = $model->getStokKritis();

        $this->assertIsArray($kritis);
        // Item 3 (Plastik Packing) has stok=2, stok_minimal=5 → critical
        $found = false;
        foreach ($kritis as $item) {
            $this->assertLessThanOrEqual((float) $item['stok_minimal'], (float) $item['stok']);
            if ((int) $item['id_inventaris'] === 3) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'Plastik Packing (stok 2, min 5) should be in critical list');
    }

    public function testGetSemuaInventarisAddsKritisFlag(): void
    {
        $model = new MInventaris();
        $all = $model->getSemuaInventaris();

        $this->assertIsArray($all);
        $this->assertNotEmpty($all);

        foreach ($all as $item) {
            $this->assertArrayHasKey('stok_kritis', $item);
            $this->assertContains((int) $item['stok_kritis'], [0, 1]);
        }

        // Verify specific items
        $deterjen = array_filter($all, fn($i) => (int) $i['id_inventaris'] === 1);
        $deterjen = array_values($deterjen);
        $this->assertEquals(0, (int) $deterjen[0]['stok_kritis']); // 50 > 5

        $plastik = array_filter($all, fn($i) => (int) $i['id_inventaris'] === 3);
        $plastik = array_values($plastik);
        $this->assertEquals(1, (int) $plastik[0]['stok_kritis']); // 2 <= 5
    }
}
