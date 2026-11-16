<?php
namespace App\Controllers\Api\V1;
use App\Libraries\Mobile\ApiProblem;
use App\Services\CustomerNotificationService;
use App\Services\CustomerLoyaltyService;
class EngagementController extends BaseApiController
{
    private function pagination(): array {
        $page=filter_var($this->request->getGet('page')??1,FILTER_VALIDATE_INT);
        $size=filter_var($this->request->getGet('per_page')??20,FILTER_VALIDATE_INT);
        if (!$page||$page<1||$page>1000000||!$size||$size<1||$size>50) throw new ApiProblem('VALIDATION_ERROR','Pagination tidak valid.',422);
        return [$page,$size];
    }
    public function notifications() { return $this->handle(function() { $r=(new CustomerNotificationService())->listing((int)$this->customer()['id_pelanggan'],...$this->pagination()); return $this->ok($r['items'],200,$r['meta']); }); }
    public function unread() { return $this->handle(fn()=>$this->ok((new CustomerNotificationService())->summary((int)$this->customer()['id_pelanggan']))); }
    public function read($id) { return $this->handle(fn()=>$this->ok((new CustomerNotificationService())->read((int)$this->customer()['id_pelanggan'],(int)$id))); }
    public function readAll() { return $this->handle(fn()=>$this->ok((new CustomerNotificationService())->readThrough((int)$this->customer()['id_pelanggan'],$this->input()['through_id']??null))); }
    public function loyalty() { return $this->handle(function() { $r=(new CustomerLoyaltyService())->summary((int)$this->customer()['id_pelanggan'],...$this->pagination()); return $this->ok($r['data'],200,$r['meta']); }); }
    public function quote($id) { return $this->handle(fn()=>$this->ok((new CustomerLoyaltyService())->quote((int)$this->customer()['id_pelanggan'],(int)$id))); }
    public function redeem($id) { return $this->handle(fn()=>$this->ok((new CustomerLoyaltyService())->redeem((int)$this->customer()['id_pelanggan'],(int)$id,$this->input()))); }
}
