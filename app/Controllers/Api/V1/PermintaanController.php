<?php

namespace App\Controllers\Api\V1;

use App\Services\PickupService;

class PermintaanController extends BaseApiController
{
    public function create()
    {
        return $this->handle(fn () => $this->ok((new PickupService())->create((int) $this->customer()['id_pelanggan'], $this->input()), 201));
    }

    public function index()
    {
        return $this->handle(function () {
            $result = (new PickupService())->listing((int) $this->customer()['id_pelanggan'],
                (string) ($this->request->getGet('status') ?? 'semua'),
                filter_var($this->request->getGet('page') ?? 1, FILTER_VALIDATE_INT) ?: 0,
                filter_var($this->request->getGet('per_page') ?? 20, FILTER_VALIDATE_INT) ?: 0);
            return $this->ok($result['items'], 200, $result['meta']);
        });
    }

    public function show($id)
    {
        return $this->handle(fn () => $this->ok((new PickupService())->get((int) $id, (int) $this->customer()['id_pelanggan'])));
    }

    public function cancel($id)
    {
        return $this->handle(fn () => $this->ok((new PickupService())->cancel((int) $id, (int) $this->customer()['id_pelanggan'])));
    }
}
