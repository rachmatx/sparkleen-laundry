<?php
namespace Tests\Feature;
use App\Libraries\Mobile\CustomerAuth;
use App\Libraries\Mobile\CustomerOrders;
use App\Services\CustomerLoyaltyService;
use App\Services\CustomerNotificationService;
use App\Services\TransaksiService;
use App\Services\PaymentSettlementService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
final class CustomerEngagementTest extends CIUnitTestCase
{
    use DatabaseTestTrait, FeatureTestTrait;
    protected $namespace=null;
    protected $DBGroup='tests';
    protected $migrateOnce=true;
    protected $seed='\Tests\Support\Database\Seeds\TestDataSeeder';
    protected $seedOnce=false;
    private string $token;
    private string $other;
    protected function setUp(): void {
        parent::setUp();
        $this->db->table('tb_pelanggan')->where('id_pelanggan',1)->update(['poin'=>100,'total_transaksi'=>35000]);
        $this->db->table('tb_transaksi')->where('id_transaksi',1)->update(['poin_diperoleh'=>3]);
        $auth=new CustomerAuth();
        $this->token=$auth->issue($this->db->table('tb_pelanggan')->where('id_pelanggan',1)->get()->getRowArray())['token'];
        $this->other=$auth->issue($this->db->table('tb_pelanggan')->where('id_pelanggan',2)->get()->getRowArray())['token'];
    }
    private function api(string $method,string $path,array $body=[],?string $token=null) {
        return $this->withHeaders(['Authorization'=>'Bearer '.($token??$this->token),'Accept'=>'application/json'])->withBodyFormat('json')->call($method,'api/v1/'.$path,$body);
    }
    private function data($r): array { return json_decode($r->getJSON(),true)['data']; }
    private function redeem(array $patch=[]): array { return array_replace(['points'=>20,'expected_grand_total'=>35000,'request_id'=>'fixture-redemption-request-1'],$patch); }
    private function person(): array { return $this->db->table('tb_pelanggan')->where('id_pelanggan',1)->get()->getRowArray(); }
    private function order(): array { return $this->db->table('tb_transaksi')->where('id_transaksi',1)->get()->getRowArray(); }
    private function notification(string $key='fixture',int $customer=1): int { (new CustomerNotificationService())->publish($customer,$key,'order','Pesanan diperbarui','Lihat pesanan.','order',1); return (int)$this->db->table('tb_customer_notification')->where('event_key',$key)->get()->getRowArray()['id']; }
    public function testAllRoutesAuthenticateAndOrderOperationsAreOwned(): void {
        foreach ([['GET','notifikasi'],['GET','notifikasi/ringkasan'],['POST','notifikasi/1/baca'],['POST','notifikasi/baca-semua'],['GET','loyalitas'],['GET','transaksi/1/poin'],['POST','transaksi/1/poin']] as [$method,$path]) $this->api($method,$path,[],'invalid')->assertStatus(401);
        $this->api('GET','transaksi/1/poin',[],$this->other)->assertStatus(404);
        $this->api('POST','transaksi/1/poin',$this->redeem(),$this->other)->assertStatus(404);
        $id=$this->notification(); $this->api('POST',"notifikasi/$id/baca",[],$this->other)->assertStatus(404);
        $this->assertSame([],$this->data($this->api('GET','notifikasi',[],$this->other)));
    }
    public function testReadAllHasCutoffAndNeverMarksAnotherAccountOrLaterNotification(): void {
        $id=$this->notification('first'); $this->notification('other',2); $later=$this->notification('later');
        $this->api('POST','notifikasi/baca-semua',['through_id'=>$id])->assertStatus(200);
        $this->assertSame(1,$this->data($this->api('GET','notifikasi/ringkasan'))['unread_count']);
        $this->assertSame(1,$this->data($this->api('GET','notifikasi/ringkasan',[],$this->other))['unread_count']);
        $this->api('POST',"notifikasi/$later/baca")->assertStatus(200); $this->api('POST',"notifikasi/$later/baca")->assertStatus(200);
        $this->assertSame(0,$this->data($this->api('GET','notifikasi/ringkasan'))['unread_count']);
        foreach ([null,'1',-1] as $bad) $this->api('POST','notifikasi/baca-semua',['through_id'=>$bad])->assertStatus(422);
    }
    public function testPaginationAndEventReplayAreDeterministic(): void {
        $this->notification('one'); $last=$this->notification('two'); $this->notification('one');
        $response=$this->api('GET','notifikasi?per_page=1'); $response->assertStatus(200);
        $this->assertSame($last,$this->data($response)[0]['id']);
        $this->assertSame(2,json_decode($response->getJSON(),true)['meta']['total']);
        foreach (['notifikasi?page=0','loyalitas?per_page=51'] as $path) $this->api('GET',$path)->assertStatus(422);
    }
    public function testCashierStatusTransitionCreatesOneNotificationWithoutPrivateData(): void {
        $service=new TransaksiService(); $input=['id_status_transaksi'=>4,'id_karyawan'=>1,'kirim_wa'=>'0'];
        $this->assertTrue($service->prosesUbahStatus(1,$input,'kasir')['success']);
        $this->assertTrue($service->prosesUbahStatus(1,$input,'kasir')['success']);
        $this->assertSame(1,$this->db->table('tb_customer_notification')->countAllResults());
        $this->assertSame(1,$this->db->table('tb_status_log')->countAllResults());
        $raw=$this->api('GET','notifikasi')->getJSON(); $this->assertStringContainsString('Siap Diambil',$raw);
        foreach (['pin_pengambilan','payment_detail','no_telp','pin_hash'] as $secret) $this->assertStringNotContainsString($secret,$raw);
    }
    public function testRedemptionAdjustsDiscountEarnedPointsBalanceAndNotaAtomically(): void {
        $quote=$this->api('GET','transaksi/1/poin'); $quote->assertStatus(200); $this->assertTrue($this->data($quote)['can_redeem']);
        $result=$this->api('POST','transaksi/1/poin',$this->redeem(['amount'=>1,'discount'=>999999,'id_pelanggan'=>2])); $result->assertStatus(200);
        $this->assertSame(79,$this->data($result)['balance']); $this->assertEquals(29000,$this->order()['grand_total']);
        $this->assertEquals(2,$this->order()['poin_diperoleh']); $this->assertEquals(29000,$this->person()['total_transaksi']);
        $nota=$this->data($this->api('GET','transaksi/1/nota')); $this->assertSame(6000,$nota['diskon']); $this->assertSame(29000,$nota['grand_total']);
        $history=$this->data($this->api('GET','loyalitas')); $this->assertSame([-1,-20],array_column($history['history'],'delta'));
        $this->assertSame(1,$this->db->table('tb_customer_notification')->countAllResults());
    }
    public function testReplayIsIdempotentButDifferentRequestOrPayloadCannotRedeemAgain(): void {
        $this->api('POST','transaksi/1/poin',$this->redeem())->assertStatus(200);
        $this->api('POST','transaksi/1/poin',$this->redeem())->assertStatus(200);
        $this->api('POST','transaksi/1/poin',$this->redeem(['request_id'=>'another-fixture-request']))->assertStatus(409);
        $this->api('POST','transaksi/1/poin',$this->redeem(['points'=>19]))->assertStatus(409);
        $this->assertSame(1,$this->db->table('tb_point_redemption')->countAllResults()); $this->assertEquals(79,$this->person()['poin']);
    }
    public function testInvalidInputsStaleQuoteAndInsufficientBalanceMakeNoChanges(): void {
        foreach ([['points'=>0],['points'=>true],['points'=>'20'],['points'=>20.5],['request_id'=>'short'],['expected_grand_total'=>1],['points'=>101]] as $patch) {
            $response=$this->api('POST','transaksi/1/poin',$this->redeem($patch)); $this->assertContains($response->response()->getStatusCode(),[409,422]);
        }
        $this->assertSame(0,$this->db->table('tb_point_redemption')->countAllResults()); $this->assertEquals(100,$this->person()['poin']);
    }
    public function testAnyRecordedPaymentOrUnresolvedGatewayBlocksRedemption(): void {
        $this->db->table('tb_pembayaran')->insert(['id_transaksi'=>1,'tgl_bayar'=>date('Y-m-d'),'nominal_bayar'=>1000,'sisa_tagihan'=>34000,'id_metode_pembayaran'=>1]);
        $this->api('POST','transaksi/1/poin',$this->redeem())->assertStatus(409);
        $this->db->table('tb_pembayaran')->emptyTable();
        foreach (['CREATING','PENDING'] as $state) {
            $this->db->table('tb_payment_intent')->truncate();
            $this->db->table('tb_payment_intent')->insert(['gateway_ref'=>$state==='CREATING'?null:'fixture-gateway','merchant_id'=>'M1','customer_ref'=>$this->order()['no_nota'],'id_transaksi'=>1,'active_order_id'=>1,'amount'=>35000,'method'=>'QRIS','status'=>$state,'created_at'=>date('Y-m-d H:i:s')]);
            $this->api('POST','transaksi/1/poin',$this->redeem())->assertStatus(409);
        }
        $this->db->table('tb_payment_intent')->truncate(); $this->db->table('tb_transaksi')->where('id_transaksi',1)->update(['payment_ref'=>'legacy-unknown']);
        $this->api('POST','transaksi/1/poin',$this->redeem())->assertStatus(409);
        $this->assertSame(0,$this->db->table('tb_point_redemption')->countAllResults());
    }
    public function testEarnedAdjustmentCannotMakeBalanceNegativeAndWholeBillDiscountIsLunas(): void {
        $this->db->table('tb_transaksi')->where('id_transaksi',1)->update(['grand_total'=>10000,'total_biaya'=>10000,'poin_diperoleh'=>1]);
        $this->db->table('tb_pelanggan')->where('id_pelanggan',1)->update(['poin'=>1]);
        $this->assertSame(0,$this->data($this->api('GET','transaksi/1/poin'))['max_points']);
        $this->db->table('tb_transaksi')->where('id_transaksi',1)->update(['grand_total'=>30000,'total_biaya'=>30000,'poin_diperoleh'=>3]);
        $this->db->table('tb_pelanggan')->where('id_pelanggan',1)->update(['poin'=>103]);
        $this->api('POST','transaksi/1/poin',$this->redeem(['points'=>100,'expected_grand_total'=>30000]))->assertStatus(200);
        $this->assertEquals(0,$this->person()['poin']); $this->assertEquals(1,$this->order()['id_status_pembayaran']);
        $this->assertSame(0,$this->db->table('tb_pembayaran')->countAllResults());
    }
    public function testNotificationFailureRollsBackPointsDiscountAndAudit(): void {
        $this->db->query("CREATE TRIGGER fixture_notification_failure BEFORE INSERT ON tb_customer_notification FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture notification failure'");
        try { $this->api('POST','transaksi/1/poin',$this->redeem())->assertStatus(500); }
        finally { $this->db->query('DROP TRIGGER fixture_notification_failure'); }
        $this->assertEquals(100,$this->person()['poin']); $this->assertEquals(35000,$this->order()['grand_total']);
        $this->assertSame(0,$this->db->table('tb_point_redemption')->countAllResults()); $this->assertSame(0,$this->db->table('tb_point_movement')->countAllResults());
        $this->api('POST','transaksi/1/poin',$this->redeem())->assertStatus(200);
    }
    public function testNewCashierSaleUsesFreshLockedBalanceRatherThanStaleCustomer(): void {
        $stale=$this->person(); $this->api('POST','transaksi/1/poin',$this->redeem())->assertStatus(200);
        $result=(new TransaksiService())->prosesTransaksiBaru(['poin_ditebus'=>10],1,[['id_layanan'=>1,'berat'=>5]],$stale);
        $this->assertTrue($result['success']); $this->assertEquals(72,$this->person()['poin']);
        $this->assertSame(2,$this->db->table('tb_customer_notification')->countAllResults());
    }
    public function testCashAndRepeatedSettlementProduceOneInboxEntryPerLedgerPayment(): void {
        $order=$this->order(); $order['payment_ref']='fixture-online';
        $this->db->table('tb_transaksi')->where('id_transaksi',1)->update(['payment_ref'=>'fixture-online','id_metode_pembayaran'=>2]);
        $payload=['transaction_id'=>'fixture-online','merchant_id'=>'M1','customer_ref'=>$order['no_nota'],'amount'=>35000,'method'=>'QRIS','status'=>'SUCCESS','paid_at'=>gmdate('c')];
        $service=new PaymentSettlementService(); $service->recordIntent($order,array_replace($payload,['status'=>'PENDING']),35000,'QRIS');
        $service->apply($payload); $service->apply($payload);
        $this->assertSame(1,$this->db->table('tb_customer_notification')->countAllResults());
        $this->assertSame(1,$this->db->table('tb_pembayaran')->countAllResults());
    }
    public function testConcurrentOrdersCannotSpendTheSameCustomerBalanceTwice(): void {
        $row=$this->order(); unset($row['id_transaksi']); $row['no_nota']='LAU/RACE/0002';
        $this->db->table('tb_transaksi')->insert($row); $second=(int)$this->db->insertID();
        $result=$this->race([
            ['kind'=>'redeem','order'=>1,'body'=>$this->redeem(['points'=>60])],
            ['kind'=>'redeem','order'=>$second,'body'=>$this->redeem(['points'=>60,'request_id'=>'fixture-race-request-2'])],
        ],true);
        $this->assertSame(1,count(array_filter($result,fn($r)=>$r['success'])));
        $this->assertSame(1,$this->db->table('tb_point_redemption')->countAllResults());
        $this->assertEquals(38,$this->person()['poin']);
    }
    public function testConcurrentIdenticalRedemptionIsAppliedOnce(): void {
        $input=['kind'=>'redeem','order'=>1,'body'=>$this->redeem()]; $results=$this->race([$input,$input]);
        $this->assertTrue($results[0]['success']); $this->assertTrue($results[1]['success']);
        $this->assertEquals(79,$this->person()['poin']);
        $this->assertSame(1,$this->db->table('tb_point_redemption')->countAllResults());
        $this->assertSame(1,$this->db->table('tb_customer_notification')->countAllResults());
    }
    public function testCashAndRedemptionRaceKeepsInvoiceAndLedgerConsistent(): void {
        $results=$this->race([['kind'=>'cash'],['kind'=>'redeem','order'=>1,'body'=>$this->redeem()]]);
        $this->assertTrue($results[0]['success']);
        $redeemed=$this->db->table('tb_point_redemption')->countAllResults()>0;
        $this->assertEquals($redeemed?79:100,$this->person()['poin']);
        $this->assertEquals($redeemed?29000:35000,$this->order()['grand_total']);
        $paid=$this->db->table('tb_pembayaran')->get()->getRowArray();
        $this->assertEquals($this->order()['grand_total'],$paid['nominal_bayar']);
        $this->assertEquals(0,$paid['sisa_tagihan']);
    }
    private function race(array $inputs,bool $customerLock=false): array {
        $children=[];$results=[];$this->db->transBegin();
        $this->db->query($customerLock?'SELECT id_pelanggan FROM tb_pelanggan WHERE id_pelanggan=1 FOR UPDATE':'SELECT id_transaksi FROM tb_transaksi WHERE id_transaksi=1 FOR UPDATE');
        try {
            foreach($inputs as $input) {
                $command=[PHP_BINARY];if($ini=php_ini_loaded_file())array_push($command,'-c',$ini);$command[]=ROOTPATH.'tests/_support/Workers/loyalty_race.php';
                $process=proc_open($command,[['pipe','r'],['pipe','w'],['pipe','w']],$pipes,ROOTPATH);$this->assertIsResource($process);stream_set_timeout($pipes[1],10);fwrite($pipes[0],json_encode($input)."\n");$children[]=[$process,$pipes];
            }
            foreach($children as [$process,$pipes])$this->assertSame("READY\n",fgets($pipes[1]));
            foreach($children as [$process,$pipes]){fwrite($pipes[0],"GO\n");fflush($pipes[0]);}
            foreach($children as [$process,$pipes])$this->assertSame("STARTING\n",fgets($pipes[1]));
            usleep(100000);$this->db->transCommit();
            foreach($children as [$process,$pipes])$results[]=json_decode(fgets($pipes[1]),true,32,JSON_THROW_ON_ERROR);
        } finally {
            $this->db->transRollback();foreach($children as [$process,$pipes]){foreach($pipes as $pipe)fclose($pipe);if(proc_get_status($process)['running'])proc_terminate($process);proc_close($process);}
        }
        return $results;
    }

    public function testFractionalLegacyMoneyIsAReviewConflictAndMakesNoChanges(): void {
        $this->db->table('tb_transaksi')->where('id_transaksi',1)->update(['grand_total'=>35000.50]);
        $this->api('GET','transaksi/1/poin')->assertStatus(409);
        $this->api('POST','transaksi/1/poin',$this->redeem())->assertStatus(409);
        $this->assertEquals(100,$this->person()['poin']);
    }
    public function testWhatsAppFailureDoesNotUndoCommittedStatusAndInbox(): void {
        $mock=$this->createMock(\App\Libraries\WhatsAppNotifier::class);
        $mock->expects($this->once())->method('notifyOrderSelesai')->willThrowException(new \RuntimeException('fixture network failure'));
        \Config\Services::injectMock('whatsapp',$mock);
        $result=(new TransaksiService())->prosesUbahStatus(1,['id_status_transaksi'=>4,'id_karyawan'=>1,'kirim_wa'=>'1'],'kasir');
        $this->assertTrue($result['success']); $this->assertFalse($result['wa_result']['success']);
        $this->assertEquals(4,$this->order()['id_status_transaksi']);
        $this->assertSame(1,$this->db->table('tb_customer_notification')->countAllResults());
    }

}
