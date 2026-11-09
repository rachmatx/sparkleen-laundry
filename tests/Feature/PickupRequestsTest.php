<?php

namespace Tests\Feature;

use App\Libraries\Mobile\CustomerAuth;
use App\Services\PickupService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class PickupRequestsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrateOnce = true;
    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = false;
    private string $token;
    private string $otherToken;

    protected function setUp(): void
    {
        parent::setUp();
        $auth = new CustomerAuth();
        $this->token = $auth->issue($this->db->table('tb_pelanggan')->where('id_pelanggan', 1)->get()->getRowArray())['token'];
        $this->otherToken = $auth->issue($this->db->table('tb_pelanggan')->where('id_pelanggan', 2)->get()->getRowArray())['token'];
    }

    private function body(array $changes = []): array
    {
        return array_replace(['id_layanan' => 1, 'berat_perkiraan' => 4.5, 'alamat_jemput' => 'Jl. Melati Nomor 10, Depok',
            'tgl_jemput' => (new \DateTimeImmutable('tomorrow', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d'),
            'slot_jemput' => 'siang', 'catatan' => 'Pisahkan handuk'], $changes);
    }

    private function api(string $method, string $path, array $body = [], ?string $token = null)
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . ($token ?? $this->token), 'Accept' => 'application/json'])
            ->withBodyFormat('json')->call($method, 'api/v1/' . $path, $body);
    }

    private function makeRequest(): int
    {
        $response = $this->api('POST', 'permintaan', $this->body());
        $response->assertStatus(201);
        return json_decode($response->getJSON(), true)['data']['id_permintaan'];
    }

    private function staff(int $id, string $action, array $data = [], string $role = 'kasir')
    {
        return $this->withSession(['isLoggedIn' => true, 'id_karyawan' => $role === 'pemilik' ? 3 : 1, 'level' => $role])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->withBodyFormat('')
            ->call('POST', 'penjemputan/' . $id . '/' . $action, [csrf_token() => csrf_hash()] + $data);
    }

    public function testCustomerCanCreateAndReadOwnQuoteWithoutCreatingSale(): void
    {
        $response = $this->api('POST', 'permintaan', $this->body(['id_pelanggan' => 2, 'estimasi_harga' => 1, 'id_transaksi' => 1, 'status' => 'dikonfirmasi']));
        $response->assertStatus(201);
        $row = json_decode($response->getJSON(), true)['data'];
        $this->assertSame(31500, $row['estimasi_harga']);
        $this->assertSame('menunggu', $row['status']);
        $this->assertNull($row['id_transaksi']);
        $this->assertStringEndsWith('12:00:00+07:00', $row['tgl_jemput']);
        $this->assertSame(1, $this->db->table('tb_transaksi')->countAllResults());
        $this->assertSame(1, (int) $this->db->table('tb_permintaan_jemput')->get()->getRowArray()['id_pelanggan']);
        $this->api('GET', 'permintaan/' . $row['id_permintaan'])->assertStatus(200);
    }

    public function testAllCustomerEndpointsRequireTokenAndIsolateAccounts(): void
    {
        $id = $this->makeRequest();
        foreach ([['POST', 'permintaan'], ['GET', 'permintaan'], ['GET', 'permintaan/' . $id], ['POST', 'permintaan/' . $id . '/batal']] as [$method, $path]) {
            $this->api($method, $path, [], 'invalid')->assertStatus(401);
        }
        $this->api('GET', 'permintaan/' . $id, [], $this->otherToken)->assertStatus(404);
        $this->api('POST', 'permintaan/' . $id . '/batal', [], $this->otherToken)->assertStatus(404);
        $other = json_decode($this->api('GET', 'permintaan', [], $this->otherToken)->getJSON(), true);
        $this->assertSame([], $other['data']);
    }

    public function testDuplicateActiveRequestIsRejectedAndCancelIsIdempotent(): void
    {
        $id = $this->makeRequest();
        $this->api('POST', 'permintaan', $this->body())->assertStatus(409);
        $this->api('POST', 'permintaan/' . $id . '/batal')->assertStatus(200);
        $this->api('POST', 'permintaan/' . $id . '/batal')->assertStatus(200);
        $this->api('POST', 'permintaan', $this->body())->assertStatus(201);
        $this->assertSame(1, $this->db->table('tb_permintaan_jemput')->where('active_customer_id', 1)->countAllResults());
    }

    public function testInvalidScheduleWeightAddressAndInactiveServiceAreRejected(): void
    {
        foreach ([['id_layanan' => '1'], ['berat_perkiraan' => 0.01], ['berat_perkiraan' => true], ['berat_perkiraan' => 101], ['alamat_jemput' => 'short'], ['catatan' => str_repeat('a', 256)], ['slot_jemput' => 'malam'], ['tgl_jemput' => '2026-02-30'], ['tgl_jemput' => '2000-01-01'], ['tgl_jemput' => date('Y-m-d', strtotime('+32 days'))]] as $patch) {
            $this->api('POST', 'permintaan', $this->body($patch))->assertStatus(422);
        }
        $this->db->table('tb_layanan')->where('id_layanan', 1)->update(['is_aktif' => 0]);
        $this->api('POST', 'permintaan', $this->body())->assertStatus(422);
        $this->assertSame(0, $this->db->table('tb_permintaan_jemput')->countAllResults());
    }

    public function testQueueIsAuthenticatedEscapesCustomerTextAndRequiresCsrfAndRole(): void
    {
        $id = $this->makeRequest();
        $this->call('GET', 'penjemputan')->assertRedirectTo(site_url('login'));
        $this->db->table('tb_permintaan_jemput')->where('id_permintaan', $id)->update(['catatan' => '<script>alert(1)</script>']);
        $page = $this->withSession(['isLoggedIn' => true, 'id_karyawan' => 1, 'level' => 'kasir'])->call('GET', 'penjemputan');
        $page->assertOK();
        $page->assertSee('Jl. Melati Nomor 10, Depok');
        $this->assertStringNotContainsString('<script>alert(1)</script>', $page->response()->getBody());
        $this->staff($id, 'proses', [], 'pemilik')->assertStatus(403);
        $this->withSession(['isLoggedIn' => true, 'id_karyawan' => 1, 'level' => 'kasir'])
            ->withHeaders([])->call('POST', 'penjemputan/' . $id . '/proses');
        $this->assertSame('menunggu', (new PickupService())->get($id)['status']);
    }

    public function testCashierLifecycleUsesActualWeightAndCreatesOneSaleOnRepeat(): void
    {
        $id = $this->makeRequest();
        $points = (int) $this->db->table('tb_pelanggan')->where('id_pelanggan', 1)->get()->getRowArray()['poin'];
        $this->staff($id, 'proses')->assertRedirectTo(site_url('penjemputan'));
        $this->assertSame('diproses', (new PickupService())->get($id)['status']);
        $this->api('POST', 'permintaan/' . $id . '/batal')->assertStatus(409);
        $this->api('POST', 'permintaan', $this->body())->assertStatus(409);
        $this->db->table('tb_layanan')->where('id_layanan', 1)->update(['harga_per_kg' => 9000]);
        $this->staff($id, 'konfirmasi', ['berat_aktual' => '5', 'poin_ditebus' => 20]);
        $request = (new PickupService())->get($id);
        $this->assertSame('dikonfirmasi', $request['status']);
        $this->assertNotNull($request['id_transaksi']);
        $order = $this->db->table('tb_transaksi')->where('id_transaksi', $request['id_transaksi'])->get()->getRowArray();
        $this->assertEquals(5, $order['total_berat']);
        $this->assertEquals(45000, $order['grand_total']);
        $this->assertEquals(0, $order['poin_ditebus']);
        $this->staff($id, 'konfirmasi', ['berat_aktual' => '50'])->assertRedirectTo(site_url('transaksi/nota/' . $request['id_transaksi']));
        $this->assertSame(2, $this->db->table('tb_transaksi')->countAllResults());
        $this->assertSame($points + 4, (int) $this->db->table('tb_pelanggan')->where('id_pelanggan', 1)->get()->getRowArray()['poin']);
        $this->api('GET', 'transaksi/' . $request['id_transaksi'])->assertStatus(200);
        $this->api('GET', 'transaksi/' . $request['id_transaksi'], [], $this->otherToken)->assertStatus(404);
    }

    public function testRejectionReasonAndTerminalTransitions(): void
    {
        $id = $this->makeRequest();
        $this->staff($id, 'tolak', ['alasan_penolakan' => '']);
        $this->assertSame('menunggu', (new PickupService())->get($id)['status']);
        $this->staff($id, 'tolak', ['alasan_penolakan' => 'Di luar area penjemputan']);
        $request = (new PickupService())->get($id);
        $this->assertSame('ditolak', $request['status']);
        $this->assertSame('Di luar area penjemputan', $request['alasan_penolakan']);
        $this->staff($id, 'proses');
        $this->assertSame('ditolak', (new PickupService())->get($id)['status']);
        $this->api('POST', 'permintaan', $this->body())->assertStatus(201);
    }

    public function testPaginationFiltersAndInvalidStateDoNotCreateTransactions(): void
    {
        $id = $this->makeRequest();
        $this->staff($id, 'konfirmasi', ['berat_aktual' => '5']);
        $this->assertSame(1, $this->db->table('tb_transaksi')->countAllResults());
        $response = $this->api('GET', 'permintaan?status=aktif&per_page=1&page=1');
        $response->assertStatus(200);
        $body = json_decode($response->getJSON(), true);
        $this->assertSame(1, $body['meta']['total']);
        $this->api('GET', 'permintaan?page=0')->assertStatus(422);
        $this->api('GET', 'permintaan?status=unknown')->assertStatus(422);
        $this->api('GET', 'permintaan?status=selesai')->assertStatus(200);
    }

    public function testConfirmationFailureRollsBackSalePointsAndRequest(): void
    {
        $id = $this->makeRequest();
        $this->staff($id, 'proses');
        $before = $this->db->table('tb_pelanggan')->where('id_pelanggan', 1)->get()->getRowArray();
        $this->db->query("CREATE TRIGGER fixture_reject_pickup BEFORE UPDATE ON tb_permintaan_jemput FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Test pickup failure'");
        try { $this->staff($id, 'konfirmasi', ['berat_aktual' => '5']); }
        finally { $this->db->query('DROP TRIGGER fixture_reject_pickup'); }
        $this->assertSame('diproses', (new PickupService())->get($id)['status']);
        $this->assertSame(1, $this->db->table('tb_transaksi')->countAllResults());
        $after = $this->db->table('tb_pelanggan')->where('id_pelanggan', 1)->get()->getRowArray();
        $this->assertSame($before['poin'], $after['poin']);
        $this->assertSame($before['total_transaksi'], $after['total_transaksi']);
    }
    public function testConcurrentCreatesProduceOnlyOneActiveRequest(): void
    {
        $results = $this->race(['kind' => 'create', 'body' => $this->body()]);
        $this->assertSame(1, count(array_filter($results, static fn ($r) => $r['success'])));
        $this->assertSame(1, $this->db->table('tb_permintaan_jemput')->countAllResults());
    }

    public function testConcurrentConfirmationsProduceOnlyOneSale(): void
    {
        $id = $this->makeRequest();
        (new PickupService())->act($id, 1, 'proses');
        $results = $this->race(['kind' => 'confirm', 'id' => $id]);
        $this->assertTrue($results[0]['success']);
        $this->assertTrue($results[1]['success']);
        $this->assertSame($results[0]['data']['id_transaksi'], $results[1]['data']['id_transaksi']);
        $this->assertSame(2, $this->db->table('tb_transaksi')->countAllResults());
    }

    private function race(array $input): array
    {
        $children = [];
        $results = [];
        $this->db->transBegin();
        $this->db->query('SELECT id_pelanggan FROM tb_pelanggan WHERE id_pelanggan = 1 FOR UPDATE');
        try {
            for ($i = 0; $i < 2; $i++) {
                $command = [PHP_BINARY];
                if ($ini = php_ini_loaded_file()) array_push($command, '-c', $ini);
                $command[] = ROOTPATH . 'tests/_support/Workers/pickup_race.php';
                $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, ROOTPATH);
                $this->assertIsResource($process);
                stream_set_timeout($pipes[1], 10);
                fwrite($pipes[0], json_encode($input, JSON_THROW_ON_ERROR) . "\n");
                $children[] = [$process, $pipes];
            }
            foreach ($children as [$process, $pipes]) $this->assertSame("READY\n", fgets($pipes[1]));
            foreach ($children as [$process, $pipes]) { fwrite($pipes[0], "GO\n"); fflush($pipes[0]); }
            foreach ($children as [$process, $pipes]) $this->assertSame("STARTING\n", fgets($pipes[1]));
            usleep(100000);
            $this->db->transCommit();
            foreach ($children as [$process, $pipes]) $results[] = json_decode(fgets($pipes[1]), true, 32, JSON_THROW_ON_ERROR);
        } finally {
            $this->db->transRollback();
            foreach ($children as [$process, $pipes]) {
                foreach ($pipes as $pipe) fclose($pipe);
                if (proc_get_status($process)['running']) proc_terminate($process);
                proc_close($process);
            }
        }
        return $results;
    }

}
