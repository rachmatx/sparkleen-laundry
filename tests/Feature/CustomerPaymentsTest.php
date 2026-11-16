<?php
namespace Tests\Feature;
use App\Libraries\Mobile\CustomerAuth;
use App\Services\TransaksiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
final class CustomerPaymentsTest extends CIUnitTestCase
{
    use DatabaseTestTrait, FeatureTestTrait;
    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrateOnce = true;
    protected $seed = '\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce = false;
    private const SECRET = 'fixture-server-signing-secret';
    private array $previous = [];
    private string $token;
    private string $other;
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['MOBILE_PAYMENTS_ENABLED'=>'true','PAYMENT_API_KEY'=>'fixture-merchant-key','PAYMENT_WEBHOOK_SECRET'=>self::SECRET,'PAYMENT_API_URL'=>'https://payment.invalid/api/transactions'] as $name=>$value) {
            $this->previous[$name] = [getenv($name), $_ENV[$name]??null, $_SERVER[$name]??null];
            $this->envValue($name, $value);
        }
        $auth = new CustomerAuth();
        $this->token = $auth->issue($this->db->table('tb_pelanggan')->where('id_pelanggan',1)->get()->getRowArray())['token'];
        $this->other = $auth->issue($this->db->table('tb_pelanggan')->where('id_pelanggan',2)->get()->getRowArray())['token'];
    }
    protected function tearDown(): void
    {
        foreach ($this->previous as $name=>[$process,$environment,$server]) {
            putenv($process===false?$name:"$name=$process");
            if ($environment===null) unset($_ENV[$name]); else $_ENV[$name]=$environment;
            if ($server===null) unset($_SERVER[$name]); else $_SERVER[$name]=$server;
        }
        parent::tearDown();
    }
    private function envValue(string $name,string $value): void { putenv("$name=$value"); $_ENV[$name]=$_SERVER[$name]=$value; }
    private function api(string $method='GET',string $suffix='',array $body=[],?string $token=null) {
        return $this->withHeaders(['Authorization'=>'Bearer '.($token??$this->token),'Accept'=>'application/json'])->withBodyFormat('json')->call($method,'api/v1/transaksi/1/pembayaran'.$suffix,$body);
    }
    private function data($response): array { return json_decode($response->getJSON(),true)['data']; }
    private function payload(array $changes=[]): array { return array_replace(['transaction_id'=>'fixture-gateway-1','merchant_id'=>'M1','customer_ref'=>'LAU/20260522/0001','amount'=>35000,'method'=>'QRIS','status'=>'PENDING','mode'=>'SIMULATION','expired_at'=>gmdate('c',time()+900),'paid_at'=>gmdate('c'),'payment_detail'=>[]],$changes); }
    private function gateway(callable $handler): void {
        $mock=$this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $mock->method('request')->willReturnCallback(function($method,$url,$options) use($handler) {
            $data=$handler($method,$url,$options);
            $raw=json_encode(['success'=>true,'data'=>$data]);
            $response=new \CodeIgniter\HTTP\Response(config('App'));
            $response->setBody($raw)->setHeader('Content-Type','application/json')->setHeader('X-Webhook-Signature',hash_hmac('sha256',$raw,self::SECRET));
            return $response;
        });
        \Config\Services::injectMock('curlrequest',$mock);
    }
    private function pending(array $patch=[]): void { $this->gateway(fn()=>$this->payload($patch)); $this->api('POST','',['metode'=>($patch['method']??'QRIS')==='QRIS'?'QRIS':'TRANSFER'])->assertStatus(200); }
    public function testAllEndpointsAuthenticateAndIsolateBeforeProviderCalls(): void {
        $mock=$this->createMock(\CodeIgniter\HTTP\CURLRequest::class); $mock->expects($this->never())->method('request'); \Config\Services::injectMock('curlrequest',$mock);
        foreach ([['GET',''],['POST',''],['POST','/sinkronisasi'],['POST','/batal']] as [$method,$suffix]) {
            $this->api($method,$suffix,['metode'=>'QRIS'],'invalid')->assertStatus(401);
            $this->api($method,$suffix,['metode'=>'QRIS'],$this->other)->assertStatus(404);
        }
    }
    public function testDisabledAndInvalidMethodCannotCreate(): void {
        $this->envValue('MOBILE_PAYMENTS_ENABLED','false');
        $this->api('POST','',['metode'=>'QRIS'])->assertStatus(503);
        $this->envValue('MOBILE_PAYMENTS_ENABLED','true');
        foreach (['CASH',1,null] as $method) $this->api('POST','',['metode'=>$method])->assertStatus(422);
        $this->assertSame(0,$this->db->table('tb_payment_intent')->countAllResults());
    }
    public function testServerComputesRemainingAndReusesPendingInsteadOfCreatingTwice(): void {
        $this->db->table('tb_pembayaran')->insert(['id_transaksi'=>1,'tgl_bayar'=>date('Y-m-d'),'nominal_bayar'=>10000,'sisa_tagihan'=>25000,'id_metode_pembayaran'=>1]);
        $calls=0;
        $this->gateway(function($method,$url,$options) use(&$calls) {
            $calls++; $this->assertSame(25000,$options['json']['amount']); $this->assertSame('LAU/20260522/0001',$options['json']['customer_ref']); $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/',$options['headers']['Idempotency-Key']);
            return $this->payload(['amount'=>25000]);
        });
        $response=$this->api('POST','',['metode'=>'QRIS','amount'=>1,'status'=>'SUCCESS','id_pelanggan'=>2]); $response->assertStatus(200);
        $data=$this->data($response); $this->assertSame('PENDING',$data['status']); $this->assertSame(25000,$data['nominal']);
        $this->assertStringStartsWith('data:image/png;base64,',$data['payment_detail']['qr_image']);
        $this->api('POST','',['metode'=>'QRIS'])->assertStatus(200); $this->assertSame(1,$calls);
        $this->api('POST','',['metode'=>'TRANSFER'])->assertStatus(409);
        $this->assertSame(1,$this->db->table('tb_payment_intent')->countAllResults());
    }
    public function testLostCreateResponseKeepsSameRequestKeyAndCanRecover(): void {
        $key=null;
        $this->gateway(function($method,$url,$options) use(&$key) { $key=$options['headers']['Idempotency-Key']; throw new \RuntimeException('fixture timeout'); });
        $this->api('POST','',['metode'=>'QRIS'])->assertStatus(502);
        $this->assertSame('PROCESSING',$this->data($this->api())['status']);
        $this->gateway(function($method,$url,$options) use($key) { $this->assertSame($key,$options['headers']['Idempotency-Key']); return $this->payload(); });
        $this->api('POST','',['metode'=>'QRIS'])->assertStatus(200);
        $this->assertSame(1,$this->db->table('tb_payment_intent')->countAllResults());
    }
    public function testForgedOrMismatchedResponsesCannotSettle(): void {
        $this->pending();
        $this->gateway(fn()=>$this->payload(['status'=>'SUCCESS','amount'=>1]));
        $this->api('POST','/sinkronisasi')->assertStatus(502);
        $mock=$this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $response=new \CodeIgniter\HTTP\Response(config('App')); $response->setJSON(['success'=>true,'data'=>$this->payload(['status'=>'SUCCESS'])])->setHeader('X-Webhook-Signature',str_repeat('0',64));
        $mock->method('request')->willReturn($response); \Config\Services::injectMock('curlrequest',$mock);
        $this->api('POST','/sinkronisasi')->assertStatus(502);
        $this->assertSame(0,$this->db->table('tb_pembayaran')->countAllResults());
    }
    public function testSignedStatusRecoversLostCallbackExactlyOnceEvenWhenCreationDisabled(): void {
        $this->pending(); $this->envValue('MOBILE_PAYMENTS_ENABLED','false');
        $this->gateway(fn()=>$this->payload(['status'=>'SUCCESS']));
        $response=$this->api('POST','/sinkronisasi'); $response->assertStatus(200); $this->assertSame('SUCCESS',$this->data($response)['status']);
        $this->api('POST','/sinkronisasi')->assertStatus(200);
        $this->assertSame(1,$this->db->table('tb_pembayaran')->countAllResults());
        $this->assertNull($this->db->table('tb_payment_intent')->get()->getRowArray()['active_order_id']);
    }
    public function testCancelTimeoutKeepsActiveAndCashBlockedUntilProviderConfirms(): void {
        $this->pending();
        $this->gateway(function(){throw new \RuntimeException('timeout');});
        $this->api('POST','/batal')->assertStatus(502);
        $this->assertSame('PENDING',$this->data($this->api())['status']);
        $cash=(new TransaksiService())->prosesPembayaran(1,['nominal_bayar'=>35000,'id_metode_pembayaran'=>1]); $this->assertFalse($cash['success']);
        $this->gateway(fn()=>$this->payload(['status'=>'CANCELLED']));
        $response=$this->api('POST','/batal'); $response->assertStatus(200); $this->assertSame('NONE',$this->data($response)['status']);
        $this->assertTrue((new TransaksiService())->prosesPembayaran(1,['nominal_bayar'=>35000,'id_metode_pembayaran'=>1])['success']);
    }
    public function testCancelAfterRemotePaymentSettlesInsteadOfErasingIt(): void {
        $this->pending(); $this->gateway(fn()=>$this->payload(['status'=>'SUCCESS']));
        $result=$this->api('POST','/batal'); $result->assertStatus(200); $this->assertSame('SUCCESS',$this->data($result)['status']);
        $this->assertSame(1,$this->db->table('tb_pembayaran')->countAllResults());
    }
    public function testExpiryRequiresRemoteConfirmationBeforeAnotherAttempt(): void {
        $this->pending(['expired_at'=>gmdate('c',time()-5)]);
        $this->assertSame('EXPIRED',$this->data($this->api())['status']);
        $this->gateway(fn()=>$this->payload(['status'=>'EXPIRED','expired_at'=>gmdate('c',time()-5)]));
        $response=$this->api('POST','/sinkronisasi'); $response->assertStatus(200); $this->assertSame('NONE',$this->data($response)['status']);
        $this->assertSame(0,$this->db->table('tb_pembayaran')->countAllResults());
    }
    public function testTransferExposesOnlyDemoAccountAndLegacyIntentRequiresReview(): void {
        $this->pending(['method'=>'BANK_TRANSFER']);
        $this->assertSame('DEMO-NOT-A-BANK-ACCOUNT',$this->data($this->api())['payment_detail']['account_number']);
        $this->db->table('tb_payment_intent')->where('id_transaksi',1)->update(['mode'=>null]);
        $data=$this->data($this->api()); $this->assertSame('REVIEW',$data['status']); $this->assertSame([],$data['payment_detail']);
    }
    public function testCreateRetryRecoversRemoteSuccessAfterFirstResponseWasLost(): void {
        $this->gateway(function(){ throw new \RuntimeException('lost create response'); });
        $this->api('POST','',['metode'=>'QRIS'])->assertStatus(502);
        $this->gateway(fn()=>$this->payload(['status'=>'SUCCESS']));
        $result=$this->api('POST','',['metode'=>'QRIS']); $result->assertStatus(200);
        $this->assertSame('SUCCESS',$this->data($result)['status']);
        $this->assertSame(1,$this->db->table('tb_pembayaran')->countAllResults());
        $this->assertSame(1,$this->db->table('tb_payment_intent')->countAllResults());
    }
    public function testCashierCannotClaimCancellationWhenProviderStillPending(): void {
        $this->pending();
        $result=$this->withSession(['isLoggedIn'=>true,'id_karyawan'=>1,'level'=>'kasir'])
            ->withHeaders(['X-Requested-With'=>'XMLHttpRequest'])->withBodyFormat('')
            ->call('POST','transaksi/batalAPI/1',[csrf_token()=>csrf_hash()]);
        $result->assertSessionHas('error','Gateway belum mengonfirmasi pembatalan. Tagihan tetap aktif.');
        $this->assertSame('PENDING',$this->db->table('tb_payment_intent')->get()->getRowArray()['status']);
    }
    public function testUnknownLegacyReferenceIsReviewAndCannotOpenANewInvoice(): void {
        $this->db->table('tb_transaksi')->where('id_transaksi',1)->update(['payment_ref'=>'unknown-legacy-ref']);
        $this->assertSame('REVIEW',$this->data($this->api())['status']);
        $this->api('POST','',['metode'=>'QRIS'])->assertStatus(409);
        $this->assertSame(0,$this->db->table('tb_payment_intent')->countAllResults());
        $this->assertFalse((new TransaksiService())->prosesPembayaran(1,['nominal_bayar'=>35000,'id_metode_pembayaran'=>1])['success']);
    }

}
