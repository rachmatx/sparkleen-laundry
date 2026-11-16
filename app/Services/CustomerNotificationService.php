<?php
namespace App\Services;
use App\Libraries\Mobile\ApiProblem;
use App\Libraries\Mobile\CustomerOrders;
final class CustomerNotificationService
{
    /** Caller writes the business event and this inbox row in the same transaction. */
    public function publish(int $customer, string $key, string $category, string $title, string $body, string $target, ?int $targetId): void
    {
        if (!in_array($target,['order','pickup','loyalty'],true)) throw new \InvalidArgumentException('Unknown notification destination.');
        db_connect()->query('INSERT INTO tb_customer_notification (id_pelanggan,event_key,category,title,body,target_type,target_id,created_at) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id=id',
            [$customer,$key,$category,$title,$body,$target,$targetId,date('Y-m-d H:i:s')]);
    }
    public function summary(int $customer): array
    {
        $row=db_connect()->query('SELECT COUNT(*) AS unread_count, COALESCE(MAX(id),0) AS latest_unread_id FROM tb_customer_notification WHERE id_pelanggan=? AND read_at IS NULL',[$customer])->getRowArray();
        $latest=db_connect()->table('tb_customer_notification')->selectMax('id')->where('id_pelanggan',$customer)->get()->getRowArray();
        return ['unread_count'=>(int)$row['unread_count'],'latest_id'=>(int)($latest['id']??0)];
    }
    public function listing(int $customer,int $page,int $size): array
    {
        $total=db_connect()->table('tb_customer_notification')->where('id_pelanggan',$customer)->countAllResults();
        $rows=db_connect()->table('tb_customer_notification')->where('id_pelanggan',$customer)->orderBy('id','DESC')->get($size,($page-1)*$size)->getResultArray();
        $meta=$this->summary($customer);
        // The cutoff comes from rows already returned, never from a newer arrival.
        $meta['latest_id']=(int)($rows[0]['id']??0);
        return ['items'=>array_map(fn($r)=>['id'=>(int)$r['id'],'category'=>$r['category'],'title'=>$r['title'],'body'=>$r['body'],'target_type'=>$r['target_type'],'target_id'=>$r['target_id']===null?null:(int)$r['target_id'],'read_at'=>CustomerOrders::iso($r['read_at']),'created_at'=>CustomerOrders::iso($r['created_at'])],$rows),
            'meta'=>['page'=>$page,'last_page'=>max(1,(int)ceil($total/$size)),'total'=>$total]+$meta];
    }
    public function read(int $customer,int $id): array
    {
        $row=db_connect()->table('tb_customer_notification')->where('id',$id)->where('id_pelanggan',$customer)->get()->getRowArray();
        if (!$row) throw new ApiProblem('NOT_FOUND','Notifikasi tidak ditemukan.',404);
        db_connect()->table('tb_customer_notification')->where('id',$id)->where('id_pelanggan',$customer)->where('read_at',null)->update(['read_at'=>date('Y-m-d H:i:s')]);
        return $this->summary($customer);
    }
    public function readThrough(int $customer,mixed $id): array
    {
        if (!is_int($id)||$id<0||$id>4294967295) throw new ApiProblem('VALIDATION_ERROR','Batas notifikasi tidak valid.',422);
        db_connect()->table('tb_customer_notification')->where('id_pelanggan',$customer)->where('id <=',$id)->where('read_at',null)->update(['read_at'=>date('Y-m-d H:i:s')]);
        return $this->summary($customer);
    }
}
