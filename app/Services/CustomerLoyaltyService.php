<?php
namespace App\Services;
use App\Libraries\Mobile\ApiProblem;
use App\Libraries\Mobile\CustomerOrders;
use App\Libraries\Payments\PaymentContract;
final class CustomerLoyaltyService
{
    public const VALUE = 300;
    /** Called inside the transaction that changes the customer balance. */
    public function movement(int $customer,int $order,string $key,int $delta,int $balance,string $reason): void
    {
        if (!$delta) return;
        db_connect()->table('tb_point_movement')->insert(['id_pelanggan'=>$customer,'id_transaksi'=>$order,'event_key'=>$key,'delta'=>$delta,'balance_after'=>$balance,'reason'=>$reason,'created_at'=>date('Y-m-d H:i:s')]);
    }
    public function summary(int $customer,int $page,int $size): array
    {
        $row=db_connect()->table('tb_pelanggan')->where('id_pelanggan',$customer)->get()->getRowArray();
        $query=fn()=>db_connect()->table('tb_point_movement m')->select('m.*,t.no_nota')->join('tb_transaksi t','t.id_transaksi=m.id_transaksi')->where('m.id_pelanggan',$customer);
        $total=$query()->countAllResults();
        $rows=$query()->orderBy('m.id','DESC')->get($size,($page-1)*$size)->getResultArray();
        return ['data'=>['balance'=>(int)$row['poin'],'point_value'=>self::VALUE,'history'=>array_map(fn($r)=>['id'=>(int)$r['id'],'order_id'=>(int)$r['id_transaksi'],'no_nota'=>$r['no_nota'],'delta'=>(int)$r['delta'],'balance_after'=>(int)$r['balance_after'],'reason'=>$r['reason'],'created_at'=>CustomerOrders::iso($r['created_at'])],$rows)],'meta'=>['page'=>$page,'total'=>$total,'last_page'=>max(1,(int)ceil($total/$size))]];
    }
    private function lock(int $customer,int $id): array
    {
        $db=db_connect();
        $order=$db->query('SELECT * FROM tb_transaksi WHERE id_transaksi=? AND id_pelanggan=? AND deleted_at IS NULL FOR UPDATE',[$id,$customer])->getRowArray();
        if (!$order) throw new ApiProblem('NOT_FOUND','Pesanan tidak ditemukan.',404);
        // Same first lock as create-intent, cash, settlement and status changes.
        $paid=$db->query('SELECT id_pembayaran FROM tb_pembayaran WHERE id_transaksi=? FOR UPDATE',[$id])->getResultArray();
        $active=$db->query("SELECT id_intent FROM tb_payment_intent WHERE id_transaksi=? AND status IN ('CREATING','PENDING') FOR UPDATE",[$id])->getResultArray();
        $person=$db->query('SELECT * FROM tb_pelanggan WHERE id_pelanggan=? FOR UPDATE',[$customer])->getRowArray();
        if (!$person || !(int)$person['is_aktif']) throw new ApiProblem('ACCOUNT_INACTIVE','Akun tidak aktif.',403);
        return [$order,$person,count($paid)>0,count($active)>0];
    }
    private function adjustment(array $order,int $points): int
    {
        $old=(int)$order['poin_diperoleh'];
        $new=min($old,intdiv(PaymentContract::storedMoney($order['grand_total'])-$points*self::VALUE,10000));
        return $old-$new;
    }
    private function view(array $order,array $person,bool $paid,bool $active): array
    {
        $db=db_connect(); $id=(int)$order['id_transaksi']; $balance=(int)$person['poin'];
        $redeemed=$db->table('tb_point_redemption')->where('id_transaksi',$id)->get()->getRowArray();
        $reason=null;
        if ($redeemed || (int)$order['poin_ditebus']>0) $reason='Poin sudah digunakan pada pesanan ini.';
        elseif ($paid || (int)$order['id_status_pembayaran']!==3) $reason='Poin hanya bisa ditukar sebelum pembayaran pertama.';
        elseif ($active || $order['payment_ref']) $reason='Selesaikan atau batalkan tagihan online terlebih dahulu.';
        elseif ((int)$order['id_status_transaksi']===5) $reason='Pesanan sudah diambil.';
        elseif ((int)$order['grand_total']<=0) $reason='Tagihan sudah lunas.';
        $max=0;
        if ($reason===null) {
            $lo=0; $hi=min($balance,intdiv(PaymentContract::storedMoney($order['grand_total']),self::VALUE));
            while ($lo<$hi) { $mid=intdiv($lo+$hi+1,2); if ($mid+$this->adjustment($order,$mid)<=$balance) $lo=$mid; else $hi=$mid-1; }
            $max=$lo;
            if (!$max) $reason='Saldo poin belum cukup untuk penukaran.';
        }
        return ['order_id'=>$id,'no_nota'=>$order['no_nota'],'balance'=>$balance,'point_value'=>self::VALUE,'grand_total'=>PaymentContract::storedMoney($order['grand_total']),
            'earned_points'=>(int)$order['poin_diperoleh'],'max_points'=>$max,'can_redeem'=>$max>0,'reason'=>$reason,'redemption'=>$redeemed ? ['points'=>(int)$redeemed['points'],'earned_adjustment'=>(int)$redeemed['earned_adjustment'],'discount'=>(int)$redeemed['discount'],'created_at'=>CustomerOrders::iso($redeemed['created_at'])] : null];
    }
    public function quote(int $customer,int $id): array { return $this->atomic(fn()=>$this->view(...$this->lock($customer,$id))); }
    public function redeem(int $customer,int $id,array $body): array
    {
        $points=$body['points']??null; $key=$body['request_id']??null; $expected=$body['expected_grand_total']??null;
        if (!is_int($points)||$points<1||$points>1000000||!is_int($expected)||$expected<0||!is_string($key)||!preg_match('/^[a-zA-Z0-9_-]{16,64}$/D',$key)) throw new ApiProblem('VALIDATION_ERROR','Pilih jumlah poin dan muat ulang rincian pesanan.',422);
        return $this->atomic(function() use($customer,$id,$points,$key,$expected) {
            [$order,$person,$paid,$active]=$this->lock($customer,$id); $db=db_connect();
            $previous=$db->table('tb_point_redemption')->where('id_pelanggan',$customer)->where('request_key',$key)->get()->getRowArray();
            if ($previous) {
                if ((int)$previous['id_transaksi']!==$id || (int)$previous['points']!==$points) throw new ApiProblem('IDEMPOTENCY_CONFLICT','Permintaan yang sama memiliki isi berbeda.',409);
                return $this->view($order,$person,$paid,$active);
            }
            $quote=$this->view($order,$person,$paid,$active);
            if (!$quote['can_redeem'] || $points>$quote['max_points']) throw new ApiProblem('POINTS_UNAVAILABLE',$quote['reason']??'Saldo atau batas poin berubah. Muat ulang sebelum menukar.',409);
            if ($expected!==$quote['grand_total']) throw new ApiProblem('ORDER_CHANGED','Tagihan berubah. Muat ulang sebelum menukar poin.',409);
            $discount=$points*self::VALUE; $correction=$this->adjustment($order,$points); $balance=(int)$person['poin']-$points-$correction; $grand=$expected-$discount;
            $db->table('tb_pelanggan')->where('id_pelanggan',$customer)->update(['poin'=>$balance,'total_transaksi'=>max(0,(float)$person['total_transaksi']-$discount)]);
            $db->table('tb_transaksi')->where('id_transaksi',$id)->update(['diskon'=>PaymentContract::storedMoney($order['diskon'])+$discount,'grand_total'=>$grand,'poin_ditebus'=>$points,'poin_diperoleh'=>(int)$order['poin_diperoleh']-$correction,'id_status_pembayaran'=>$grand===0?1:3]);
            $db->table('tb_point_redemption')->insert(['id_pelanggan'=>$customer,'id_transaksi'=>$id,'request_key'=>$key,'points'=>$points,'earned_adjustment'=>$correction,'discount'=>$discount,'balance_after'=>$balance,'created_at'=>date('Y-m-d H:i:s')]);
            $this->movement($customer,$id,'redeem:'.$id,-$points,(int)$person['poin']-$points,'Penukaran poin');
            $this->movement($customer,$id,'earned-adjustment:'.$id,-$correction,$balance,'Penyesuaian poin setelah diskon');
            (new CustomerNotificationService())->publish($customer,'loyalty:'.$id,'loyalty','Poin berhasil ditukar',"Diskon Rp ".number_format($discount,0,',','.')." diterapkan pada nota {$order['no_nota']}.",'order',$id);
            return $this->view(...$this->lock($customer,$id));
        });
    }
    private function atomic(callable $action): mixed
    {
        $db=db_connect(); if (!$db->transBegin()) throw new \RuntimeException('Unable to begin loyalty operation.');
        try { $result=$action(); if (!$db->transStatus()||!$db->transCommit()) throw new \RuntimeException('Unable to commit loyalty operation.'); return $result; }
        catch(\Throwable $error) {
            $db->transRollback(); $db->resetTransStatus();
            if ($error instanceof \App\Libraries\Payments\PaymentProblem) throw new ApiProblem($error->problemCode,$error->getMessage(),$error->httpStatus);
            throw $error;
        }
    }
}
