<?php

namespace App\Controllers\Api\V1;

use App\Services\CustomerAddressService;
class AddressController extends BaseApiController
{
    public function index()
    {
        return $this->handle(function () {
            $r = (new CustomerAddressService())->listing((int) $this->customer()['id_pelanggan'], (int) ($this->request->getGet('page') ?? 1), (int) ($this->request->getGet('per_page') ?? 20));
            return $this->ok($r['items'], 200, $r['meta']);
        });
    }
    public function show($id)
    {
        return $this->handle(fn() => $this->ok((new CustomerAddressService())->get((int) $this->customer()['id_pelanggan'], (int) $id)));
    }
    public function create()
    {
        return $this->handle(fn() => $this->ok((new CustomerAddressService())->create((int) $this->customer()['id_pelanggan'], $this->input()), 201));
    }
    public function update($id)
    {
        return $this->handle(fn() => $this->ok((new CustomerAddressService())->update((int) $this->customer()['id_pelanggan'], (int) $id, $this->input())));
    }
    public function primary($id)
    {
        return $this->handle(fn() => $this->ok((new CustomerAddressService())->update((int) $this->customer()['id_pelanggan'], (int) $id, ['is_default' => true])));
    }
    public function delete($id)
    {
        return $this->handle(function () use ($id) {
            (new CustomerAddressService())->delete((int) $this->customer()['id_pelanggan'], (int) $id);
            return $this->response->setStatusCode(204)->setHeader('Cache-Control', 'no-store')->setBody('');
        });
    }
}
