<?php
namespace App\Controllers\Api\V1;
use App\Libraries\Mobile\ApiProblem;
use App\Libraries\Payments\PaymentProblem;
use App\Services\CustomerPaymentService;
class PembayaranController extends BaseApiController
{
    private function perform(callable $action)
    {
        return $this->handle(function () use ($action) {
            try { return $this->ok($action(new CustomerPaymentService(), (int) $this->customer()['id_pelanggan'])); }
            catch (PaymentProblem $error) { throw new ApiProblem($error->problemCode, $error->getMessage(), $error->httpStatus); }
        });
    }
    public function show($id) { return $this->perform(fn ($service, $customer) => $service->state((int) $id, $customer)); }
    public function create($id)
    {
        return $this->perform(function ($service, $customer) use ($id) {
            $body = $this->input();
            if (!is_string($body['metode'] ?? null)) throw new ApiProblem('VALIDATION_ERROR', 'Pilih metode pembayaran.', 422);
            return $service->start((int) $id, $customer, $body['metode']);
        });
    }
    public function sync($id) { return $this->perform(fn ($service, $customer) => $service->sync((int) $id, $customer)); }
    public function cancel($id) { return $this->perform(fn ($service, $customer) => $service->sync((int) $id, $customer, true)); }
}
