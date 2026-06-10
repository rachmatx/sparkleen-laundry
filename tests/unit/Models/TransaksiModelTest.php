<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\MTransaksi;

/**
 * @internal
 */
final class TransaksiModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = false;
    protected $migrateOnce = false;
    protected $migrate = false;
    protected $DBGroup = 'tests';

    public function testGenerateNotaReturnsCorrectFormat(): void
    {
        $model = new MTransaksi();
        $nota = $model->generateNota();

        // Format: LAU/YYYYMMDD/XXXX
        $this->assertMatchesRegularExpression('/^LAU\/\d{8}\/\d{4}$/', $nota);
    }

    public function testGenerateNotaContainsTodayDate(): void
    {
        $model = new MTransaksi();
        $nota = $model->generateNota();
        $today = date('Ymd');

        $this->assertStringContainsString($today, $nota);
    }

    public function testAllowedFieldsAreCorrect(): void
    {
        $model = new MTransaksi();
        $expected = [
            'no_nota', 'id_pelanggan', 'id_kasir', 'tgl_masuk',
            'tgl_selesai_estimasi', 'tgl_selesai_aktual', 'total_berat',
            'total_biaya', 'diskon', 'poin_ditebus', 'poin_diperoleh', 'grand_total', 'status', 'status_pembayaran'
        ];
        
        $this->assertEquals($expected, $model->getAllowedFields());
    }

    public function testTableNameIsCorrect(): void
    {
        $model = new MTransaksi();
        $this->assertEquals('tb_transaksi', $model->getTable());
    }

    public function testCanFindSeededTransaction(): void
    {
        $model = new MTransaksi();
        $result = $model->find(1);

        $this->assertNotNull($result);
        $this->assertEquals('LAU/20260522/0001', $result['no_nota']);
        $this->assertEquals('menunggu', $result['status']);
        $this->assertEquals(35000, $result['grand_total']);
    }
}
