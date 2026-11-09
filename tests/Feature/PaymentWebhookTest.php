<?php

namespace Tests\Feature;

use App\Services\PaymentSettlementService;
use App\Services\TransaksiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class PaymentWebhookTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrateOnce = true;
    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = false;
    private const KEY = 'webhook-local-test-key-not-a-secret';
    private array $previousEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousEnv = [];
        foreach (['PAYMENT_API_KEY', 'PAYMENT_API_URL', 'PAYMENT_WEBHOOK_SECRET'] as $name) {
            $this->previousEnv[$name] = [getenv($name), $_ENV[$name] ?? null, $_SERVER[$name] ?? null];
        }
        $this->environment('PAYMENT_API_KEY', 'local-merchant-api-key');
        $this->environment('PAYMENT_WEBHOOK_SECRET', self::KEY);
        $this->environment('PAYMENT_API_URL', 'https://payment.invalid/api/transactions');
        $this->db->table('tb_payment_intent')->insert([
            'gateway_ref' => 'gateway-test-1', 'merchant_id' => 'M-test', 'customer_ref' => 'LAU/20260522/0001',
            'id_transaksi' => 1, 'amount' => 35000, 'method' => 'QRIS', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('tb_transaksi')->where('id_transaksi', 1)->update(['payment_ref' => 'gateway-test-1', 'id_metode_pembayaran' => 2]);
    }

    protected function tearDown(): void
    {
        foreach ($this->previousEnv as $name => [$process, $environment, $server]) {
            putenv($process === false ? $name : "$name=$process");
            if ($environment === null) unset($_ENV[$name]); else $_ENV[$name] = $environment;
            if ($server === null) unset($_SERVER[$name]); else $_SERVER[$name] = $server;
        }
        parent::tearDown();
    }

    private function environment(string $name, string $value): void
    {
        putenv("$name=$value");
        $_ENV[$name] = $_SERVER[$name] = $value;
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'event' => 'payment.success', 'transaction_id' => 'gateway-test-1', 'merchant_id' => 'M-test',
            'customer_ref' => 'LAU/20260522/0001', 'status' => 'SUCCESS', 'amount' => 35000,
            'method' => 'QRIS', 'paid_at' => '2026-11-10T00:00:00.000Z',
        ], $changes);
    }

    private function postCallback(array $changes = [], ?string $signature = null)
    {
        $raw = json_encode($this->payload($changes), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        return $this->raw($raw, $signature ?? hash_hmac('sha256', $raw, self::KEY));
    }

    private function raw(string $raw, string $signature)
    {
        return $this->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-Signature' => $signature])
            ->withBody($raw)->call('POST', 'webhook/payment');
    }

    private function assertUnpaid(): void
    {
        $this->assertSame(0, $this->db->table('tb_pembayaran')->countAllResults());
        $this->assertSame(3, (int) $this->db->table('tb_transaksi')->where('id_transaksi', 1)->get()->getRowArray()['id_status_pembayaran']);
        $this->assertSame('PENDING', $this->db->table('tb_payment_intent')->get()->getRowArray()['status']);
    }

    public function testValidCallbackAndRepeatedDeliveryRecordExactlyOnePayment(): void
    {
        $first = $this->postCallback();
        $first->assertStatus(200);
        $this->assertFalse(json_decode($first->getJSON(), true)['duplicate']);
        $again = $this->postCallback();
        $again->assertStatus(200);
        $this->assertTrue(json_decode($again->getJSON(), true)['duplicate']);
        $this->assertSame(1, $this->db->table('tb_pembayaran')->countAllResults());
        $row = $this->db->table('tb_pembayaran')->get()->getRowArray();
        $this->assertEquals(35000, $row['nominal_bayar']);
        $this->assertEquals(0, $row['sisa_tagihan']);
        $this->assertSame(1, (int) $this->db->table('tb_transaksi')->where('id_transaksi', 1)->get()->getRowArray()['id_status_pembayaran']);
        $this->assertSame((int) $row['id_pembayaran'], (int) $this->db->table('tb_payment_intent')->get()->getRowArray()['id_pembayaran']);
    }

    public function testUnsignedForgedAndTamperedPayloadsAreRejected(): void
    {
        foreach (['', str_repeat('0', 64), 'not-hex'] as $signature) {
            $this->postCallback([], $signature)->assertStatus(401);
        }
        $valid = json_encode($this->payload(), JSON_THROW_ON_ERROR);
        // Knowing the merchant API key must not authorize a webhook.
        $this->raw($valid, hash_hmac('sha256', $valid, 'local-merchant-api-key'))->assertStatus(401);
        $this->raw($valid . ' ', hash_hmac('sha256', $valid, self::KEY))->assertStatus(401);
        $this->assertUnpaid();
    }

    public function testMissingConfigurationAndInvalidJsonFailClosed(): void
    {
        $this->environment('PAYMENT_WEBHOOK_SECRET', '');
        $this->postCallback()->assertStatus(503);
        $this->environment('PAYMENT_WEBHOOK_SECRET', 'local-merchant-api-key');
        $this->postCallback()->assertStatus(503);
        $this->environment('PAYMENT_WEBHOOK_SECRET', self::KEY);
        $this->raw('{broken', hash_hmac('sha256', '{broken', self::KEY))->assertStatus(400);
        $this->raw('[]', hash_hmac('sha256', '[]', self::KEY))->assertStatus(422);
        $this->assertUnpaid();
    }

    public function testWrongIdentityAmountMethodOrStatusCannotSettle(): void
    {
        foreach ([['merchant_id' => 'other'], ['customer_ref' => 'other-nota'], ['amount' => 10000], ['method' => 'BANK_TRANSFER']] as $patch) {
            $this->postCallback($patch)->assertStatus(409);
        }
        foreach ([['amount' => 35000.5], ['amount' => 35000.0], ['amount' => '35000'], ['amount' => 0], ['amount' => true], ['method' => 'CASH'], ['status' => 'FAILED'], ['paid_at' => 'not-a-date'], ['paid_at' => '2026-02-30T00:00:00Z']] as $patch) {
            $this->postCallback($patch)->assertStatus(422);
        }
        $this->postCallback(['transaction_id' => 'not-found'])->assertStatus(404);
        $this->assertUnpaid();
    }

    public function testUnrelatedSignedEventIsIgnored(): void
    {
        $this->postCallback(['event' => 'payment.pending'])->assertStatus(200);
        $this->assertUnpaid();
    }

    public function testPartialIntentRecordsOnlyItsAmountAndRemainsInstallment(): void
    {
        $this->db->table('tb_payment_intent')->where('id_transaksi', 1)->update(['amount' => 10000]);
        $this->postCallback(['amount' => 10000])->assertStatus(200);
        $row = $this->db->table('tb_pembayaran')->get()->getRowArray();
        $this->assertEquals(10000, $row['nominal_bayar']);
        $this->assertEquals(25000, $row['sisa_tagihan']);
        $this->assertSame(2, (int) $this->db->table('tb_transaksi')->where('id_transaksi', 1)->get()->getRowArray()['id_status_pembayaran']);
    }

    public function testChangedBalanceIsNotSilentlyFilledByCallback(): void
    {
        $this->db->table('tb_pembayaran')->insert(['id_transaksi' => 1, 'tgl_bayar' => date('Y-m-d'), 'nominal_bayar' => 10000, 'sisa_tagihan' => 25000, 'id_metode_pembayaran' => 1]);
        $this->postCallback()->assertStatus(409);
        $this->assertSame(1, $this->db->table('tb_pembayaran')->countAllResults());
        $this->assertSame('PENDING', $this->db->table('tb_payment_intent')->get()->getRowArray()['status']);
    }

    public function testCancelledReplacedLegacyAndDeletedOrdersCannotBeSettled(): void
    {
        $this->db->table('tb_transaksi')->where('id_transaksi', 1)->update(['payment_ref' => null]);
        $this->postCallback()->assertStatus(409);
        $this->db->table('tb_transaksi')->where('id_transaksi', 1)->update(['payment_ref' => 'newer-reference']);
        $this->postCallback()->assertStatus(409);
        $this->db->table('tb_transaksi')->where('id_transaksi', 1)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        $this->postCallback()->assertStatus(404);
        $this->db->table('tb_payment_intent')->truncate();
        $this->postCallback()->assertStatus(404);
        $this->assertSame(0, $this->db->table('tb_pembayaran')->countAllResults());
    }

    public function testFailureAfterLedgerInsertRollsBackEverythingAndCanRetry(): void
    {
        $this->db->query("CREATE TRIGGER fixture_reject_settlement BEFORE UPDATE ON tb_payment_intent FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Test settlement failure'");
        try {
            $this->postCallback()->assertStatus(500);
            $this->assertUnpaid();
        } finally {
            $this->db->query('DROP TRIGGER fixture_reject_settlement');
        }
        $this->postCallback()->assertStatus(200);
        $this->assertSame(1, $this->db->table('tb_pembayaran')->countAllResults());
    }

    public function testGatewayCreationPersistsValidatedIntentWithoutMarkingPaid(): void
    {
        $this->gatewayFixture();
        $result = (new TransaksiService())->prosesPembayaran(1, ['nominal_bayar' => 35000, 'id_metode_pembayaran' => 2]);
        $this->assertTrue($result['success']);
        $intent = $this->db->table('tb_payment_intent')->get()->getRowArray();
        $this->assertSame('M-test', $intent['merchant_id']);
        $this->assertEquals(35000, $intent['amount']);
        $this->assertSame('gateway-created-1', $this->db->table('tb_transaksi')->where('id_transaksi', 1)->get()->getRowArray()['payment_ref']);
        $this->assertUnpaid();
        $this->postCallback(['transaction_id' => 'gateway-created-1'])->assertStatus(200);
    }

    public function testMismatchedGatewayResponseCannotCreateAnIntent(): void
    {
        $this->gatewayFixture(['amount' => 1]);
        $result = (new TransaksiService())->prosesPembayaran(1, ['nominal_bayar' => 35000, 'id_metode_pembayaran' => 2]);
        $this->assertFalse($result['success']);
        $this->assertSame(0, $this->db->table('tb_payment_intent')->countAllResults());
        $this->assertSame(0, $this->db->table('tb_pembayaran')->countAllResults());
        $this->assertNull($this->db->table('tb_transaksi')->where('id_transaksi', 1)->get()->getRowArray()['payment_ref']);
    }

    private function gatewayFixture(array $changes = []): void
    {
        $this->db->table('tb_payment_intent')->truncate();
        $this->db->table('tb_transaksi')->where('id_transaksi', 1)->update(['payment_ref' => null]);
        $response = new \CodeIgniter\HTTP\Response(config('App'));
        $response->setJSON(['success' => true, 'data' => array_replace([
            'transaction_id' => 'gateway-created-1', 'merchant_id' => 'M-test', 'customer_ref' => 'LAU/20260522/0001',
            'amount' => 35000, 'method' => 'QRIS', 'status' => 'PENDING', 'payment_detail' => [],
        ], $changes)]);
        $transport = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $transport->method('post')->willReturn($response);
        \Config\Services::injectMock('curlrequest', $transport);
    }

    public function testConcurrentDuplicateCallbacksCreateOneLedgerEntry(): void
    {
        $results = $this->race(['callback', 'callback']);
        $this->assertSame([false, true], array_values(array_unique(array_column($results, 'duplicate'))));
        $this->assertSame(1, $this->db->table('tb_pembayaran')->countAllResults());
    }

    public function testConcurrentCashAndCallbackCannotOverpayOrder(): void
    {
        $results = $this->race(['callback', 'cash']);
        $this->assertSame(1, count(array_filter($results, static fn ($r) => $r['success'])));
        $this->assertSame(1, $this->db->table('tb_pembayaran')->countAllResults());
        $this->assertEquals(35000, $this->db->table('tb_pembayaran')->selectSum('nominal_bayar', 'total')->get()->getRowArray()['total']);
    }

    private function race(array $kinds): array
    {
        $children = [];
        $results = [];
        $this->db->transBegin();
        $this->db->query('SELECT id_transaksi FROM tb_transaksi WHERE id_transaksi = 1 FOR UPDATE');
        try {
            foreach ($kinds as $kind) {
                $command = [PHP_BINARY];
                if ($ini = php_ini_loaded_file()) array_push($command, '-c', $ini);
                $command[] = ROOTPATH . 'tests/_support/Workers/payment_race.php';
                $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, ROOTPATH);
                $this->assertIsResource($process);
                stream_set_timeout($pipes[1], 10);
                fwrite($pipes[0], json_encode(['kind' => $kind, 'payload' => $this->payload()]) . "\n");
                $children[] = [$process, $pipes];
            }
            foreach ($children as [$process, $pipes]) $this->assertSame("READY\n", fgets($pipes[1]));
            foreach ($children as [$process, $pipes]) { fwrite($pipes[0], "GO\n"); fflush($pipes[0]); }
            foreach ($children as [$process, $pipes]) $this->assertSame("STARTING\n", fgets($pipes[1]));
            usleep(100000);
            $this->db->transCommit();
            foreach ($children as [$process, $pipes]) {
                $results[] = json_decode(fgets($pipes[1]), true, 32, JSON_THROW_ON_ERROR);
            }
        } finally {
            $this->db->transRollback();
            foreach ($children as [$process, $pipes]) {
                foreach ($pipes as $pipe) fclose($pipe);
                if (proc_get_status($process)['running']) proc_terminate($process);
                proc_close($process);
            }
        }
        if ($kinds === ['callback', 'callback']) usort($results, static fn ($a, $b) => $a['duplicate'] <=> $b['duplicate']);
        return $results;
    }
}
