<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\MTransaksi;
use App\Models\MInventaris;
use App\Models\MPelanggan;
use App\Models\MDetailTransaksi;

/**
 * Feature tests for the Transaction (Transaksi) workflow.
 *
 * @internal
 */
final class TransaksiFeatureTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = false;
    protected $migrateOnce = true;
    protected $migrate = true;
    protected $DBGroup = 'tests';
    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Run real web filters, while keeping fixture notifications offline.
        $whatsapp = new \Config\WhatsApp();
        $whatsapp->enabled = false;
        \Config\Services::injectMock('whatsapp', new \App\Libraries\WhatsAppNotifier($whatsapp));
    }

    private function loginAsKasir(): void
    {
        $this->withSession([
            'isLoggedIn'    => true,
            'id_karyawan'   => 1,
            'nama_karyawan' => 'Kasir Test',
            'username'      => 'kasir_test',
            'level'         => 'kasir',
        ]);
    }

    public function testDaftarTransaksiPageLoads(): void
    {
        $this->loginAsKasir();
        $result = $this->call('get', 'transaksi/daftar');
        $result->assertOK();
        $result->assertSee('Daftar Transaksi');
    }

    public function testAjaxDetailReturnsJsonForValidTransaction(): void
    {
        $this->loginAsKasir();
        $result = $this->call('get', 'transaksi/ajaxDetail/1');
        $result->assertOK();

        $json = json_decode($result->getJSON(), true);
        $this->assertEquals('success', $json['status']);
        $this->assertArrayHasKey('transaksi', $json);
        $this->assertArrayHasKey('detail', $json);
        $this->assertArrayHasKey('pembayaran', $json);
    }

    public function testAjaxDetailReturnsErrorForInvalidTransaction(): void
    {
        $this->loginAsKasir();
        $result = $this->call('get', 'transaksi/ajaxDetail/9999');
        $result->assertOK();

        $json = json_decode($result->getJSON(), true);
        $this->assertEquals('error', $json['status']);
    }

    public function testAddToCartAndRemoveFromCart(): void
    {
        $this->loginAsKasir();

        // Add to cart
        $result = $this->call('POST', 'transaksi/addToCart', [
            csrf_token() => csrf_hash(),
            'id_layanan' => 1,
            'berat'      => 3,
        ]);
        $result->assertRedirectTo(site_url('transaksi'));

        // Verify item in temp table
        $this->seeInDatabase('tb_temp_keranjang', [
            'id_kasir'   => 1,
            'id_layanan' => 1,
            'berat'      => 3,
        ]);

        // Get the temp record ID
        $temp = $this->db->table('tb_temp_keranjang')
            ->where('id_kasir', 1)
            ->where('id_layanan', 1)
            ->get()->getRowArray();

        // Remove from cart
        $result = $this->call('POST', 'transaksi/removeFromCart/' . $temp['id_temp'], [
            csrf_token() => csrf_hash(),
        ]);
        $result->assertRedirectTo(site_url('transaksi'));

        // Verify removed
        $this->dontSeeInDatabase('tb_temp_keranjang', [
            'id_temp' => $temp['id_temp'],
        ]);
    }

    public function testSimpanTransaksiRejectsEmptyCart(): void
    {
        $this->loginAsKasir();

        // Ensure cart is empty
        $this->db->table('tb_temp_keranjang')
            ->where('id_kasir', 1)
            ->where('id_pelanggan', null)
            ->delete();

        $result = $this->call('POST', 'transaksi/simpanTransaksi', [
            csrf_token() => csrf_hash(),
            'id_pelanggan' => 1,
        ]);

        $result->assertRedirectTo(site_url('transaksi'));
        $result->assertSessionHas('error', 'Keranjang masih kosong!');
        $this->assertSame(1, $this->db->table('tb_transaksi')->countAllResults());
    }

    public function testSimpanTransaksiRejectsNoCustomer(): void
    {
        $this->loginAsKasir();

        // Add something to cart first
        $this->db->table('tb_temp_keranjang')->insert([
            'id_pelanggan' => null,
            'id_kasir'     => 1,
            'id_layanan'   => 1,
            'berat'        => 3,
        ]);

        $result = $this->call('POST', 'transaksi/simpanTransaksi', [
            csrf_token() => csrf_hash(),
            'id_pelanggan' => '',
        ]);

        $result->assertRedirectTo(site_url('transaksi'));
        $result->assertSessionHas('error', 'Pelanggan belum dipilih!');
        $this->assertSame(1, $this->db->table('tb_transaksi')->countAllResults());
        $this->seeInDatabase('tb_temp_keranjang', ['id_kasir' => 1, 'berat' => 3]);
    }

    public function testSimpanTransaksiCreatesFullTransaction(): void
    {
        $this->loginAsKasir();

        // Add item to cart
        $this->db->table('tb_temp_keranjang')->insert([
            'id_pelanggan' => null,
            'id_kasir'     => 1,
            'id_layanan'   => 1,
            'berat'        => 5,
        ]);

        $result = $this->call('POST', 'transaksi/simpanTransaksi', [
            csrf_token() => csrf_hash(),
            'id_pelanggan'         => 1,
            'tgl_selesai_estimasi' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'poin_ditebus'         => 0,
            'inv_id'               => [1],
            'inv_jumlah_1'         => 0.25,
            'inv_ket_1'            => 'Deterjen usage',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('success', 'Transaksi berhasil disimpan!');

        // Verify transaction created (should be id 2, since seeder has id 1)
        $transaksi = $this->db->table('tb_transaksi')
            ->where('id_transaksi !=', 1)
            ->orderBy('id_transaksi', 'DESC')
            ->get()->getRowArray();

        $this->assertNotNull($transaksi);
        $this->assertEquals(1, (int) $transaksi['id_pelanggan']);
        $this->assertEquals(5, (float) $transaksi['total_berat']);
        $this->assertEquals(35000, (float) $transaksi['total_biaya']); // 5 * 7000
        $this->assertEquals(1, $transaksi['id_status_transaksi']);
        $result->assertRedirectTo(site_url('transaksi/nota/' . $transaksi['id_transaksi']));

        // Verify detail created
        $this->seeInDatabase('tb_detail_transaksi', [
            'id_transaksi' => $transaksi['id_transaksi'],
            'id_layanan'   => 1,
        ]);

        // Verify inventory deducted
        $inv = (new MInventaris())->find(1);
        $this->assertEquals(49.75, (float) $inv['stok']); // 50 - 0.25
        $this->seeInDatabase('tb_penggunaan_inventaris', [
            'id_transaksi' => $transaksi['id_transaksi'],
            'id_inventaris' => 1,
            'jumlah_pakai' => 0.25,
        ]);

        // Verify cart cleared
        $tempCount = $this->db->table('tb_temp_keranjang')
            ->where('id_kasir', 1)
            ->where('id_pelanggan', null)
            ->countAllResults();
        $this->assertEquals(0, $tempCount);
    }

    public function testPointCalculation(): void
    {
        $this->loginAsKasir();

        // Customer 1 has 25 poin, let's redeem 10
        $this->db->table('tb_temp_keranjang')->insert([
            'id_pelanggan' => null,
            'id_kasir'     => 1,
            'id_layanan'   => 2, // Cuci Ekspres: 12000/kg
            'berat'        => 5,
        ]);

        $result = $this->call('POST', 'transaksi/simpanTransaksi', [
            csrf_token() => csrf_hash(),
            'id_pelanggan'         => 1,
            'tgl_selesai_estimasi' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'poin_ditebus'         => 10,
        ]);

        $result->assertRedirect();

        // total_biaya = 5 * 12000 = 60000
        // diskon = 10 * 300 = 3000
        // grand_total = 60000 - 3000 = 57000
        // poin_diperoleh = floor(57000 / 10000) = 5
        $transaksi = $this->db->table('tb_transaksi')
            ->orderBy('id_transaksi', 'DESC')
            ->get()->getRowArray();

        $this->assertEquals(60000, (float) $transaksi['total_biaya']);
        $this->assertEquals(3000, (float) $transaksi['diskon']);
        $this->assertEquals(57000, (float) $transaksi['grand_total']);
        $this->assertEquals(5, (int) $transaksi['poin_diperoleh']);
        $this->assertEquals(10, (int) $transaksi['poin_ditebus']);
        $result->assertSessionHas('success', 'Transaksi berhasil disimpan!');
        $result->assertRedirectTo(site_url('transaksi/nota/' . $transaksi['id_transaksi']));

        // Verify customer poin updated: 25 - 10 + 5 = 20
        $pelanggan = (new MPelanggan())->find(1);
        $this->assertEquals(20, (int) $pelanggan['poin']);
    }

    public function testSimpanPembayaranRecordsPayment(): void
    {
        $this->loginAsKasir();

        // Transaction 1 has grand_total = 35000, status_pembayaran = 'belum'
        $result = $this->call('POST', 'transaksi/simpanPembayaran', [
            csrf_token() => csrf_hash(),
            'id_transaksi'  => 1,
            'nominal_bayar' => 35000,
            'id_metode_pembayaran'  => 1,
        ]);

        $result->assertRedirect();

        // Verify payment record created
        $this->seeInDatabase('tb_pembayaran', [
            'id_transaksi'  => 1,
            'id_metode_pembayaran'  => 1,
            'nominal_bayar' => 35000,
            'sisa_tagihan' => 0,
        ]);

        // Verify status updated to lunas
        $transaksi = (new MTransaksi())->find(1);
        $this->assertEquals(1, $transaksi['id_status_pembayaran']); // 1 is LUNAS
    }

    public function testSimpanPembayaranCalculatesChange(): void
    {
        $this->loginAsKasir();

        // Pay more than owed
        $result = $this->call('POST', 'transaksi/simpanPembayaran', [
            csrf_token() => csrf_hash(),
            'id_transaksi'  => 1,
            'nominal_bayar' => 50000, // grand_total is 35000
            'id_metode_pembayaran'  => 1,
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('kembalian', 15000.0);
        $this->seeInDatabase('tb_pembayaran', [
            'id_transaksi' => 1,
            'nominal_bayar' => 35000,
            'sisa_tagihan' => 0,
        ]);
        $this->assertSame(1, $this->db->table('tb_pembayaran')->where('id_transaksi', 1)->countAllResults());
    }
}
