<?php

namespace App\Controllers\Api\V1;

use App\Libraries\Mobile\ApiProblem;
use App\Libraries\Mobile\CustomerAuth;

class AuthController extends BaseApiController
{
    public function login()
    {
        return $this->handle(function () {
            $data = $this->input();
            $phone = CustomerAuth::phone($this->text($data, 'no_telp', 9, 30));
            $auth = new CustomerAuth();
            $auth->throttle('login', $phone, $this->request->getIPAddress());
            $pin = $this->pin($data);
            $customer = $auth->findPhone($phone);
            if (!$customer || !(int) $customer['is_aktif'] || empty($customer['pin_hash']) || !password_verify($pin, $customer['pin_hash'])) {
                throw new ApiProblem('AUTH_INVALID_CREDENTIALS', 'Nomor telepon atau PIN salah.', 401);
            }
            return $this->ok($auth->issue($customer));
        });
    }

    public function register()
    {
        return $this->handle(function () {
            $data = $this->input();
            $name = $this->text($data, 'nama', 3, 100);
            $address = $this->text($data, 'alamat', 5, 1000);
            $phone = CustomerAuth::phone($this->text($data, 'no_telp', 9, 30));
            $auth = new CustomerAuth();
            $auth->throttle('register', $phone, $this->request->getIPAddress());
            $pin = $this->pin($data);
            if ($auth->findPhone($phone)) {
                throw new ApiProblem('PHONE_TAKEN', 'Nomor telepon sudah terdaftar. Gunakan masuk atau aktivasi PIN.', 409);
            }
            $db = db_connect();
            $row = ['kode_pelanggan' => (new \App\Models\MPelanggan())->generateKode(), 'nama_pelanggan' => $name,
                'no_telp' => $phone, 'alamat' => $address, 'poin' => 0, 'total_transaksi' => 0, 'is_aktif' => 1,
                'pin_hash' => password_hash($pin, PASSWORD_DEFAULT), 'pin_set_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
            for ($attempt = 0; $attempt < 3; $attempt++) {
                try {
                    $row['kode_pelanggan'] = (new \App\Models\MPelanggan())->generateKode();
                    $db->table('tb_pelanggan')->insert($row);
                    break;
                } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
                    if ($auth->findPhone($phone)) {
                        throw new ApiProblem('PHONE_TAKEN', 'Nomor telepon sudah terdaftar.', 409);
                    }
                    if ($attempt === 2) {
                        throw $e;
                    }
                }
            }
            $row['id_pelanggan'] = (int) $db->insertID();
            return $this->ok($auth->issue($row), 201);
        });
    }

    public function activate()
    {
        return $this->handle(function () {
            $data = $this->input();
            $phone = CustomerAuth::phone($this->text($data, 'no_telp', 9, 30));
            $auth = new CustomerAuth();
            $auth->throttle('activate', $phone, $this->request->getIPAddress());
            $pin = $this->pin($data);
            $nota = $this->text($data, 'no_nota', 1, 100);
            $customer = $auth->findPhone($phone);
            $db = db_connect();
            $latest = $customer ? $db->table('tb_transaksi')->where('id_pelanggan', $customer['id_pelanggan'])->where('deleted_at', null)->orderBy('id_transaksi', 'DESC')->get(1)->getRowArray() : null;
            if (!$customer || !(int) $customer['is_aktif'] || !$latest || !hash_equals($latest['no_nota'], $nota)) {
                throw new ApiProblem('ACTIVATION_FAILED', 'Nomor telepon atau nota terakhir tidak sesuai.', 422);
            }
            if (!empty($customer['pin_hash'])) {
                throw new ApiProblem('AUTH_ALREADY_ACTIVATED', 'Akun sudah memiliki PIN. Silakan masuk.', 409);
            }
            $db->table('tb_pelanggan')->where('id_pelanggan', $customer['id_pelanggan'])->where('pin_hash', null)->update([
                'pin_hash' => password_hash($pin, PASSWORD_DEFAULT), 'pin_set_at' => date('Y-m-d H:i:s'),
            ]);
            if ($db->affectedRows() !== 1) {
                throw new ApiProblem('AUTH_ALREADY_ACTIVATED', 'Akun sudah memiliki PIN. Silakan masuk.', 409);
            }
            return $this->ok($auth->issue($customer));
        });
    }

    public function logout()
    {
        return $this->handle(function () {
            $customer = $this->customer();
            db_connect()->table('tb_api_token')->where('id_token', $customer['id_token'])->update(['revoked_at' => date('Y-m-d H:i:s')]);
            return $this->response->setStatusCode(204)->setHeader('Cache-Control', 'no-store')->setBody('');
        });
    }
}
