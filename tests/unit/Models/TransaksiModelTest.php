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
    protected $seedOnce = true;
    protected $migrateOnce = true;
    protected $migrate = true;
    protected $DBGroup = 'tests';
    protected $namespace = null;

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

    public function testPaymentMetadataPersistsWithoutChangingTransactionIdentity(): void
    {
        $model = new MTransaksi();
        $before = $model->find(1);
        $detail = json_encode(['method' => 'qris', 'status' => 'PENDING'], JSON_THROW_ON_ERROR);

        // Payment metadata belongs to the existing gateway schema; the primary
        // key and unknown input must still be excluded from model updates.
        $this->assertTrue($model->update(1, [
            'payment_ref' => 'TEST-PAYMENT-REF',
            'payment_detail' => $detail,
            'id_transaksi' => 9999,
            'unrecognized_input' => 'must not become a database column',
        ]));

        $stored = $model->find(1);
        $this->assertNotNull($stored);
        $this->assertSame('TEST-PAYMENT-REF', $stored['payment_ref']);
        $this->assertSame($detail, $stored['payment_detail']);
        $this->assertEquals($before['id_transaksi'], $stored['id_transaksi']);
        $this->assertSame($before['no_nota'], $stored['no_nota']);
        $this->assertSame($before['grand_total'], $stored['grand_total']);
        $this->assertSame($before['id_status_pembayaran'], $stored['id_status_pembayaran']);
        $this->assertNull($model->find(9999));
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
        $this->assertEquals(1, $result['id_status_transaksi']);
        $this->assertEquals(35000, $result['grand_total']);
    }
}
