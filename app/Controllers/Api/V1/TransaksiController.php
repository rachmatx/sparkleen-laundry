<?php

namespace App\Controllers\Api\V1;

use App\Libraries\Mobile\ApiProblem;
use App\Libraries\Mobile\CustomerOrders;

class TransaksiController extends BaseApiController
{
    public function index()
    {
        return $this->handle(function () {
            $customer = $this->customer();
            $scope = $this->request->getGet('status') ?? 'aktif';
            if (!in_array($scope, ['aktif', 'selesai', 'semua'], true)) {
                throw new ApiProblem('VALIDATION_ERROR', 'Filter pesanan tidak valid.', 422, ['status' => ['Gunakan aktif, selesai, atau semua.']]);
            }
            $page = $this->request->getGet('page') ?? '1';
            $perPage = $this->request->getGet('per_page') ?? '20';
            if (filter_var($page, FILTER_VALIDATE_INT) === false || filter_var($perPage, FILTER_VALIDATE_INT) === false || (int) $page < 1 || (int) $page > 100000 || (int) $perPage < 1 || (int) $perPage > 100) {
                throw new ApiProblem('VALIDATION_ERROR', 'Pagination tidak valid.', 422);
            }
            $result = (new CustomerOrders())->listing((int) $customer['id_pelanggan'], $scope, (int) $page, (int) $perPage);
            return $this->ok($result['items'], 200, $result['meta']);
        });
    }

    public function show(int $id)
    {
        return $this->handle(function () use ($id) {
            $orders = new CustomerOrders();
            $row = $orders->own((int) $this->customer()['id_pelanggan'], $id);
            return $this->ok($orders->detail($row));
        });
    }

    public function nota(int $id)
    {
        return $this->handle(function () use ($id) {
            $orders = new CustomerOrders();
            $row = $orders->own((int) $this->customer()['id_pelanggan'], $id);
            return $this->ok($orders->nota($row));
        });
    }
}
