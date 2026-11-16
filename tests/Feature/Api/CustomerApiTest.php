<?php

namespace Tests\Feature\Api;

use App\Libraries\Mobile\CustomerAuth;
use App\Libraries\Mobile\CustomerOrders;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class CustomerApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;
    protected $migrateOnce = true;
    protected $DBGroup = 'tests';
    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    private string $token;
    private int $customerId;
    private int $orderId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db->table('tb_api_token')->truncate();
        $this->db->table('tb_api_auth_attempts')->truncate();
        $customer = $this->db->table('tb_pelanggan')->orderBy('id_pelanggan')->get(1)->getRowArray();
        $this->customerId = (int) $customer['id_pelanggan'];
        $this->db->table('tb_pelanggan')->where('id_pelanggan', $this->customerId)->update([
            'no_telp' => '081298765432', 'pin_hash' => password_hash('654321', PASSWORD_BCRYPT, ['cost' => 4]), 'is_aktif' => 1,
        ]);
        $customer = $this->db->table('tb_pelanggan')->where('id_pelanggan', $this->customerId)->get()->getRowArray();
        $this->token = (new CustomerAuth())->issue($customer)['token'];
        $order = $this->db->table('tb_transaksi')->where('id_pelanggan', $this->customerId)->orderBy('id_transaksi', 'DESC')->get(1)->getRowArray();
        $this->orderId = (int) $order['id_transaksi'];
    }

    private function api(string $method, string $path, ?array $body = null, ?string $token = null)
    {
        return $this->withHeaders(['Authorization' => $token === null ? '' : 'Bearer ' . $token, 'Accept' => 'application/json'])
            ->withBodyFormat('json')->call(strtoupper($method), 'api/v1/' . $path, $body ?? []);
    }

    private function data($result): array
    {
        return json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testEveryPrivateEndpointRejectsMissingAndInvalidTokenAsJson(): void
    {
        foreach ([['GET', 'me'], ['PATCH', 'me'], ['POST', 'auth/logout'], ['GET', 'layanan'], ['GET', 'outlet'], ['GET', 'transaksi'], ['GET', 'transaksi/' . $this->orderId], ['GET', 'transaksi/' . $this->orderId . '/nota']] as [$method, $path]) {
            foreach ([null, str_repeat('f', 64)] as $token) {
                $response = $this->api($method, $path, null, $token);
                $response->assertStatus(401);
                $this->assertSame('AUTH_INVALID_TOKEN', $this->data($response)['error']['code']);
                $this->assertStringNotContainsString('<html', strtolower($response->response()->getBody()));
            }
        }
    }

    public function testPhoneAliasLoginAndTokenHashStorage(): void
    {
        $response = $this->api('POST', 'auth/login', ['no_telp' => '6281298765432', 'pin' => '654321']);
        $response->assertStatus(200);
        $data = $this->data($response)['data'];
        $this->assertSame($this->customerId, $data['pelanggan']['id_pelanggan']);
        $stored = $this->db->table('tb_api_token')->where('token_hash', hash('sha256', $data['token']))->get()->getRowArray();
        $this->assertNotNull($stored);
        $this->assertNotContains($data['token'], array_values($stored));
        $this->assertArrayNotHasKey('pin_hash', $data['pelanggan']);
    }

    public function testRegistrationCreatesOneCanonicalCustomerAndRejectsAliasDuplicate(): void
    {
        $body = ['nama' => 'Pelanggan Baru', 'no_telp' => '081277776666', 'alamat' => 'Jl. Pengujian Nomor 10', 'pin' => '123456'];
        $response = $this->api('POST', 'auth/register', $body);
        $response->assertStatus(201);
        $id = $this->data($response)['data']['pelanggan']['id_pelanggan'];
        $stored = $this->db->table('tb_pelanggan')->where('id_pelanggan', $id)->get()->getRowArray();
        $this->assertTrue(password_verify('123456', $stored['pin_hash']));
        $this->assertSame('6281277776666', $stored['no_telp']);
        $body['no_telp'] = '6281277776666';
        $duplicate = $this->api('POST', 'auth/register', $body);
        $duplicate->assertStatus(409);
        $this->assertSame('PHONE_TAKEN', $this->data($duplicate)['error']['code']);
    }

    public function testBadPinIsValidatedAndWrongPinCannotAuthenticate(): void
    {
        $this->api('POST', 'auth/login', ['no_telp' => '081298765432', 'pin' => '123'])->assertStatus(422);
        $this->api('POST', 'auth/register', ['nama' => 'Pelanggan Baru', 'alamat' => 'Alamat Pengujian', 'no_telp' => '081234567890123', 'pin' => '123456'])->assertStatus(422);
        $this->api('POST', 'auth/login', ['no_telp' => '081298765432', 'pin' => '111111'])->assertStatus(401);
    }

    public function testAuthThrottleReturnsRetryAfter(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->api('POST', 'auth/login', ['no_telp' => '081298765432', 'pin' => '111111'])->assertStatus(401);
        }
        $response = $this->api('POST', 'auth/login', ['no_telp' => '081298765432', 'pin' => '654321']);
        $response->assertStatus(429);
        $this->assertGreaterThan(0, (int) $response->response()->getHeaderLine('Retry-After'));
    }

    public function testActivationRequiresOwnLatestNotaAndCannotReplaceExistingPin(): void
    {
        $this->db->table('tb_pelanggan')->where('id_pelanggan', $this->customerId)->update(['pin_hash' => null]);
        $nota = $this->db->table('tb_transaksi')->where('id_transaksi', $this->orderId)->get()->getRowArray()['no_nota'];
        $body = ['no_telp' => '6281298765432', 'no_nota' => 'NOT-A-NOTA', 'pin' => '123456'];
        $this->api('POST', 'auth/activate', $body)->assertStatus(422);
        $body['no_nota'] = $nota;
        $this->api('POST', 'auth/activate', $body)->assertStatus(200);
        $this->api('POST', 'auth/activate', $body)->assertStatus(409);
    }

    public function testOtherCustomersCannotReadDetailOrNota(): void
    {
        $other = $this->db->table('tb_pelanggan')->where('id_pelanggan !=', $this->customerId)->get(1)->getRowArray();
        $otherToken = (new CustomerAuth())->issue($other)['token'];
        $this->api('GET', 'transaksi/' . $this->orderId, null, $otherToken)->assertStatus(404);
        $this->api('GET', 'transaksi/' . $this->orderId . '/nota', null, $otherToken)->assertStatus(404);
    }

    public function testListPaginationAndFiltersNeverLeakOtherCustomers(): void
    {
        $response = $this->api('GET', 'transaksi?status=semua&per_page=1&page=1', null, $this->token);
        $response->assertStatus(200);
        $body = $this->data($response);
        $this->assertCount(1, $body['data']);
        $this->assertSame(1, $body['meta']['per_page']);
        foreach ($body['data'] as $row) {
            $stored = $this->db->table('tb_transaksi')->where('id_transaksi', $row['id_transaksi'])->get()->getRowArray();
            $this->assertSame($this->customerId, (int) $stored['id_pelanggan']);
            $this->assertArrayNotHasKey('pin_pengambilan', $row);
        }
        $this->api('GET', 'transaksi?status=invalid', null, $this->token)->assertStatus(422);
        $this->api('GET', 'transaksi?page=0', null, $this->token)->assertStatus(422);
    }

    public function testCashierStatusChangeAndCashPaymentAppearOnRefresh(): void
    {
        $this->db->table('tb_transaksi')->where('id_transaksi', $this->orderId)->update(['pin_pengambilan' => '4821', 'id_status_pembayaran' => 3]);
        $this->db->table('tb_pembayaran')->where('id_transaksi', $this->orderId)->delete();
        $service = new \App\Services\TransaksiService();
        $status = $service->prosesUbahStatus($this->orderId, ['id_status_transaksi' => 4, 'id_karyawan' => 1, 'kirim_wa' => '0'], 'kasir');
        $this->assertTrue($status['success']);
        $order = $this->db->table('tb_transaksi')->where('id_transaksi', $this->orderId)->get()->getRowArray();
        $cash = $service->prosesPembayaran($this->orderId, ['nominal_bayar' => $order['grand_total'], 'id_metode_pembayaran' => 1]);
        $this->assertTrue($cash['success']);
        $response = $this->api('GET', 'transaksi/' . $this->orderId, null, $this->token);
        $response->assertStatus(200);
        $body = $this->data($response)['data'];
        $this->assertSame('selesai', $body['status']);
        $this->assertSame('current', $body['timeline'][3]['state']);
        $this->assertSame('4821', $body['pin_pengambilan']);
        $this->assertSame('lunas', $body['status_pembayaran']);
        $this->assertSame(0, $body['sisa_tagihan']);
        $this->assertIsInt($body['grand_total']);
        $this->assertStringEndsWith('+07:00', $body['tgl_masuk']);
        $nota = $this->api('GET', 'transaksi/' . $this->orderId . '/nota', null, $this->token);
        $nota->assertStatus(200);
        $data = $this->data($nota)['data'];
        $this->assertArrayNotHasKey('pin_pengambilan', $data);
        $this->assertSame($body['grand_total'], $data['grand_total']);
    }

    public function testProfileUpdateAllowlistAndCatalog(): void
    {
        $before = $this->data($this->api('GET', 'me', null, $this->token))['data'];
        $response = $this->api('PATCH', 'me', ['nama' => 'Nama Diperbarui', 'alamat' => 'Alamat Pengujian Baru', 'poin' => 99999, 'no_telp' => '081111111111'], $this->token);
        $response->assertStatus(200);
        $profile = $this->data($response)['data'];
        $this->assertSame('Nama Diperbarui', $profile['nama']);
        $this->assertSame($before['poin'], $profile['poin']);
        $this->assertSame($before['no_telp'], $profile['no_telp']);
        $this->api('GET', 'layanan', null, $this->token)->assertStatus(200);
        $this->api('GET', 'outlet', null, $this->token)->assertStatus(200);
    }

    public function testExpiredRevokedAndInactiveCustomersAreRejected(): void
    {
        $this->db->table('tb_api_token')->where('token_hash', hash('sha256', $this->token))->update(['expires_at' => '2000-01-01 00:00:00']);
        $this->api('GET', 'me', null, $this->token)->assertStatus(401);
        $customer = $this->db->table('tb_pelanggan')->where('id_pelanggan', $this->customerId)->get()->getRowArray();
        $fresh = (new CustomerAuth())->issue($customer)['token'];
        $this->api('POST', 'auth/logout', null, $fresh)->assertStatus(204);
        $this->api('GET', 'me', null, $fresh)->assertStatus(401);
        $fresh = (new CustomerAuth())->issue($customer)['token'];
        $this->db->table('tb_pelanggan')->where('id_pelanggan', $this->customerId)->update(['is_aktif' => 0]);
        $this->api('GET', 'me', null, $fresh)->assertStatus(401);
    }

    public function testMissingDeletedAndEmptyOrders(): void
    {
        $this->api('GET', 'transaksi/999999', null, $this->token)->assertStatus(404);
        $this->db->table('tb_transaksi')->where('id_pelanggan', $this->customerId)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        $this->api('GET', 'transaksi/' . $this->orderId, null, $this->token)->assertStatus(404);
        $body = $this->data($this->api('GET', 'transaksi?status=semua', null, $this->token));
        $this->assertSame([], $body['data']);
        $this->assertSame(0, $body['meta']['total']);
    }

    public function testInvalidJsonAndUnknownApiRouteStayJson(): void
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])->withBody('{broken')->call('POST', 'api/v1/auth/login');
        $response->assertStatus(400);
        $this->assertSame('INVALID_JSON', $this->data($response)['error']['code']);
        $this->api('GET', 'not-a-route')->assertStatus(404);
    }

    public function testUnavailableGatewayNeverRecordsOnlinePaymentLocally(): void
    {
        $url = getenv('PAYMENT_API_URL');
        $key = getenv('PAYMENT_API_KEY');
        putenv('PAYMENT_API_URL=');
        putenv('PAYMENT_API_KEY=');
        $this->db->table('tb_pembayaran')->where('id_transaksi', $this->orderId)->delete();
        $this->db->table('tb_transaksi')->where('id_transaksi', $this->orderId)->update(['id_status_pembayaran' => 3]);
        $order = $this->db->table('tb_transaksi')->where('id_transaksi', $this->orderId)->get()->getRowArray();
        foreach ([2, 3] as $method) {
            $result = (new \App\Services\TransaksiService())->prosesPembayaran($this->orderId, ['nominal_bayar' => $order['grand_total'], 'id_metode_pembayaran' => $method]);
            $this->assertFalse($result['success']);
            $this->assertSame(0, $this->db->table('tb_pembayaran')->where('id_transaksi', $this->orderId)->countAllResults());
            $this->assertSame(3, (int) $this->db->table('tb_transaksi')->where('id_transaksi', $this->orderId)->get()->getRowArray()['id_status_pembayaran']);
        }
        putenv($url === false ? 'PAYMENT_API_URL' : 'PAYMENT_API_URL=' . $url);
        putenv($key === false ? 'PAYMENT_API_KEY' : 'PAYMENT_API_KEY=' . $key);
    }

    public function testGatewayTransportFailureDoesNotFallBackToLocalPayment(): void
    {
        $url = getenv('PAYMENT_API_URL');
        $key = getenv('PAYMENT_API_KEY');
        try {
            putenv('PAYMENT_API_URL=https://payment.invalid/api/transactions');
            putenv('PAYMENT_API_KEY=integration-demo-not-a-secret');
            $transport = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
            $transport->method('post')->willThrowException(new \RuntimeException('Simulated unavailable gateway'));
            \Config\Services::injectMock('curlrequest', $transport);
            $this->db->table('tb_pembayaran')->where('id_transaksi', $this->orderId)->delete();
            $this->db->table('tb_transaksi')->where('id_transaksi', $this->orderId)->update(['id_status_pembayaran' => 3]);
            $order = $this->db->table('tb_transaksi')->where('id_transaksi', $this->orderId)->get()->getRowArray();
            $result = (new \App\Services\TransaksiService())->prosesPembayaran($this->orderId, ['nominal_bayar' => $order['grand_total'], 'id_metode_pembayaran' => 2]);
            $this->assertFalse($result['success']);
            $this->assertSame(0, $this->db->table('tb_pembayaran')->where('id_transaksi', $this->orderId)->countAllResults());
        } finally {
            putenv($url === false ? 'PAYMENT_API_URL' : 'PAYMENT_API_URL=' . $url);
            putenv($key === false ? 'PAYMENT_API_KEY' : 'PAYMENT_API_KEY=' . $key);
        }
    }

    public function testMoneyDatesAndUnknownStatusStaySafe(): void
    {
        $this->db->table('tb_transaksi')->where('id_transaksi', $this->orderId)->update(['grand_total' => '42500.50']);
        $data = $this->data($this->api('GET', 'transaksi/' . $this->orderId, null, $this->token))['data'];
        $this->assertSame(42501, $data['grand_total']);
        $this->assertStringEndsWith('+07:00', $data['tgl_masuk']);
        $this->db->table('tb_master_status_transaksi')->insert(['nama_status' => 'status_baru']);
        $statusId = $this->db->insertID();
        $this->db->table('tb_transaksi')->where('id_transaksi', $this->orderId)->update(['id_status_transaksi' => $statusId]);
        $data = $this->data($this->api('GET', 'transaksi/' . $this->orderId, null, $this->token))['data'];
        $this->assertSame('Status belum tersedia', $data['status_label']);
        $this->assertSame(['pending'], array_values(array_unique(array_column($data['timeline'], 'state'))));
    }

    public function testCorsPreflightUsesExplicitOriginAllowlist(): void
    {
        $cors = config('Cors');
        $cors->default['allowedOrigins'] = ['https://sparkleen.example'];
        \CodeIgniter\Config\Factories::injectMock('config', 'Cors', $cors);
        $response = $this->withHeaders(['Origin' => 'https://sparkleen.example', 'Access-Control-Request-Method' => 'GET', 'Access-Control-Request-Headers' => 'authorization'])->call('OPTIONS', 'api/v1/me');
        $response->assertStatus(204);
        $this->assertSame('https://sparkleen.example', $response->response()->getHeaderLine('Access-Control-Allow-Origin'));
        // CI4 emits a fixed header for a single allowed origin. Browsers reject
        // other origins because that header does not match the requesting origin.
        $denied = $this->withHeaders(['Origin' => 'https://not-allowed.example', 'Access-Control-Request-Method' => 'GET'])->call('OPTIONS', 'api/v1/me');
        $this->assertNotSame('https://not-allowed.example', $denied->response()->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $denied->response()->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testNewMigrationRollsBackWithoutChangingBusinessRows(): void
    {
        $before = $this->db->table('tb_transaksi')->countAllResults();
        $migration = new \App\Database\Migrations\AddCustomerMobileAuth();
        $migration->down();
        $this->assertFalse($this->db->tableExists('tb_api_token'));
        $this->assertFalse($this->db->fieldExists('pin_hash', 'tb_pelanggan'));
        $this->assertSame($before, $this->db->table('tb_transaksi')->countAllResults());
        $migration->up();
        $this->assertTrue($this->db->fieldExists('pin_hash', 'tb_pelanggan'));
    }
}
