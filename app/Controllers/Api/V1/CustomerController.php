<?php

namespace App\Controllers\Api\V1;

use App\Libraries\Mobile\ApiProblem;
use App\Libraries\Mobile\CustomerAuth;
use App\Libraries\Mobile\CustomerOrders;

class CustomerController extends BaseApiController
{
    public function me()
    {
        return $this->handle(fn () => $this->ok(CustomerAuth::profile($this->customer())));
    }

    public function update()
    {
        return $this->handle(function () {
            $customer = $this->customer();
            $data = $this->input();
            $updates = [];
            if (array_key_exists('nama', $data)) {
                $updates['nama_pelanggan'] = $this->text($data, 'nama', 3, 100);
            }
            if (array_key_exists('alamat', $data)) {
                $updates['alamat'] = $this->text($data, 'alamat', 5, 1000);
            }
            if ($updates === []) {
                throw new ApiProblem('VALIDATION_ERROR', 'Isi nama atau alamat yang ingin diubah.', 422);
            }
            $updates['updated_at'] = date('Y-m-d H:i:s');
            db_connect()->table('tb_pelanggan')->where('id_pelanggan', $customer['id_pelanggan'])->update($updates);
            return $this->ok(CustomerAuth::profile(array_replace($customer, $updates)));
        });
    }

    public function layanan()
    {
        return $this->handle(function () {
            $rows = db_connect()->table('tb_layanan')->where('is_aktif', 1)->orderBy('nama_layanan')->get()->getResultArray();
            return $this->ok(array_map(fn ($r) => ['id_layanan' => (int) $r['id_layanan'], 'nama_layanan' => $r['nama_layanan'], 'harga_per_kg' => CustomerOrders::money($r['harga_per_kg']), 'estimasi_hari' => (int) $r['estimasi_hari']], $rows));
        });
    }

    public function outlet()
    {
        return $this->handle(fn () => $this->ok(CustomerOrders::outlet()));
    }
}
