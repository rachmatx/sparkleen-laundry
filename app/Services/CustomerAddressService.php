<?php

namespace App\Services;

use App\Libraries\Mobile\ApiProblem;
final class CustomerAddressService
{
    private function text(mixed $value, string $field, int $min, int $max): string
    {
        if (!is_string($value) || mb_strlen(trim($value)) < $min || mb_strlen(trim($value)) > $max) {
            throw new ApiProblem('VALIDATION_ERROR', 'Periksa alamat Anda.', 422, [$field => ["Isi {$field} sepanjang {$min}–{$max} karakter."]]);
        }
        return trim($value);
    }
    private function row($db, int $customer, int $id): array
    {
        $row = $db->table('tb_alamat_pelanggan')->where('id_pelanggan', $customer)->where('id_alamat', $id)->get()->getRowArray();
        if (!$row) {
            throw new ApiProblem('NOT_FOUND', 'Alamat tidak ditemukan.', 404);
        }
        return $row;
    }
    private function dto(array $r): array
    {
        return ['id_alamat' => (int) $r['id_alamat'], 'label' => $r['label'], 'alamat' => $r['alamat'], 'patokan' => $r['patokan'], 'is_default' => $r['utama_pelanggan_id'] !== null];
    }
    private function makeDefault($db, int $customer, int $id): void
    {
        $db->table('tb_alamat_pelanggan')->where('id_pelanggan', $customer)->update(['utama_pelanggan_id' => null]);
        $db->table('tb_alamat_pelanggan')->where('id_pelanggan', $customer)->where('id_alamat', $id)->update(['utama_pelanggan_id' => $customer]);
    }
    private function mirror($db, int $customer): void
    {
        $main = $db->table('tb_alamat_pelanggan')->where('utama_pelanggan_id', $customer)->get()->getRowArray();
        $db->table('tb_pelanggan')->where('id_pelanggan', $customer)->update(['alamat' => $main['alamat'] ?? '', 'updated_at' => date('Y-m-d H:i:s')]);
    }
    private function legacy($db, array $person): void
    {
        $main = $db->table('tb_alamat_pelanggan')->where('utama_pelanggan_id', $person['id_pelanggan'])->get()->getRowArray();
        if ($main) {
            // Keep edits made by the cashier/older clients on the same default.
            if ($main['alamat'] !== $person['alamat'] && trim((string) $person['alamat']) !== '') {
                $db->table('tb_alamat_pelanggan')->where('id_alamat', $main['id_alamat'])->update(['alamat' => $person['alamat'], 'updated_at' => date('Y-m-d H:i:s')]);
            }
            return;
        }
        if (trim((string) $person['alamat']) === '' || $db->table('tb_alamat_pelanggan')->where('id_pelanggan', $person['id_pelanggan'])->countAllResults() > 0) {
            return;
        }
        $db->table('tb_alamat_pelanggan')->insert(['id_pelanggan' => $person['id_pelanggan'], 'label' => 'Alamat utama', 'alamat' => $person['alamat'], 'utama_pelanggan_id' => $person['id_pelanggan'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
    }
    private function atomic(int $customer, callable $action): mixed
    {
        $db = db_connect();
        if (!$db->transBegin()) {
            throw new \RuntimeException('Unable to begin address operation.');
        }
        try {
            // Parent lock serializes create/default/delete with pickup creation.
            $person = $db->query('SELECT * FROM tb_pelanggan WHERE id_pelanggan=? FOR UPDATE', [$customer])->getRowArray();
            if (!$person || !(int) $person['is_aktif']) {
                throw new ApiProblem('AUTH_INVALID_TOKEN', 'Akun tidak aktif.', 401);
            }
            $this->legacy($db, $person);
            $result = $action($db);
            if (!$db->transStatus() || !$db->transCommit()) {
                throw new \RuntimeException('Address commit failed.');
            }
            return $result;
        } catch (\Throwable $e) {
            $db->transRollback();
            $db->resetTransStatus();
            throw $e;
        }
    }
    public function listing(int $customer, int $page = 1, int $perPage = 20): array
    {
        if ($page < 1 || $page > 1000000 || $perPage < 1 || $perPage > 20) {
            throw new ApiProblem('VALIDATION_ERROR', 'Halaman tidak valid.', 422);
        }
        return $this->atomic($customer, function ($db) use ($customer, $page, $perPage) {
            $q = $db->table('tb_alamat_pelanggan')->where('id_pelanggan', $customer);
            $total = (clone $q)->countAllResults();
            $rows = $q->orderBy('utama_pelanggan_id', 'DESC')->orderBy('id_alamat', 'ASC')->get($perPage, ($page - 1) * $perPage)->getResultArray();
            return ['items' => array_map(fn($r) => $this->dto($r), $rows), 'meta' => ['page' => $page, 'last_page' => max(1, (int) ceil($total / $perPage)), 'total' => $total]];
        });
    }
    private function values(array $data): array
    {
        if (array_key_exists('is_default', $data) && !is_bool($data['is_default'])) {
            throw new ApiProblem('VALIDATION_ERROR', 'Pilihan alamat utama tidak valid.', 422, ['is_default' => ['Gunakan true atau false.']]);
        }
        $landmark = $this->text($data['patokan'] ?? '', 'patokan', 0, 255);
        return ['label' => $this->text($data['label'] ?? null, 'label', 1, 40), 'alamat' => $this->text($data['alamat'] ?? null, 'alamat', 10, 1000), 'patokan' => $landmark === '' ? null : $landmark];
    }
    public function get(int $customer, int $id): array
    {
        return $this->atomic($customer, fn($db) => $this->dto($this->row($db, $customer, $id)));
    }
    public function create(int $customer, array $data): array
    {
        return $this->atomic($customer, function ($db) use ($customer, $data) {
            $count = $db->table('tb_alamat_pelanggan')->where('id_pelanggan', $customer)->countAllResults();
            if ($count >= 50) {
                throw new ApiProblem('ADDRESS_LIMIT', 'Maksimal 50 alamat tersimpan.', 422);
            }
            $db->table('tb_alamat_pelanggan')->insert($this->values($data) + ['id_pelanggan' => $customer, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            $id = (int) $db->insertID();
            if ($count === 0 || ($data['is_default'] ?? false)) {
                $this->makeDefault($db, $customer, $id);
            }
            $this->mirror($db, $customer);
            return $this->dto($this->row($db, $customer, $id));
        });
    }
    public function update(int $customer, int $id, array $data): array
    {
        return $this->atomic($customer, function ($db) use ($customer, $id, $data) {
            $old = $this->row($db, $customer, $id);
            $values = $this->values(array_replace($old, ['patokan' => $old['patokan'] ?? ''], $data));
            $db->table('tb_alamat_pelanggan')->where('id_alamat', $id)->where('id_pelanggan', $customer)->update($values + ['updated_at' => date('Y-m-d H:i:s')]);
            if (($data['is_default'] ?? false) === true) {
                $this->makeDefault($db, $customer, $id);
            }
            $this->mirror($db, $customer);
            return $this->dto($this->row($db, $customer, $id));
        });
    }
    public function delete(int $customer, int $id): void
    {
        $this->atomic($customer, function ($db) use ($customer, $id) {
            $old = $this->row($db, $customer, $id);
            $db->table('tb_alamat_pelanggan')->where('id_alamat', $id)->where('id_pelanggan', $customer)->delete();
            if ($old['utama_pelanggan_id'] !== null) {
                $next = $db->table('tb_alamat_pelanggan')->where('id_pelanggan', $customer)->orderBy('id_alamat', 'ASC')->get(1)->getRowArray();
                if ($next) {
                    $this->makeDefault($db, $customer, (int) $next['id_alamat']);
                }
            }
            $this->mirror($db, $customer);
        });
    }
    public function updateProfile(int $customer, array $updates): array
    {
        return $this->atomic($customer, function ($db) use ($customer, $updates) {
            if (array_key_exists('alamat', $updates)) {
                $main = $db->table('tb_alamat_pelanggan')->where('utama_pelanggan_id', $customer)->get()->getRowArray();
                if ($main) {
                    $db->table('tb_alamat_pelanggan')->where('id_alamat', $main['id_alamat'])->update(['alamat' => $updates['alamat'], 'updated_at' => date('Y-m-d H:i:s')]);
                } else {
                    $db->table('tb_alamat_pelanggan')->insert(['id_pelanggan' => $customer, 'label' => 'Alamat utama', 'alamat' => $updates['alamat'], 'utama_pelanggan_id' => $customer, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                }
            }
            $db->table('tb_pelanggan')->where('id_pelanggan', $customer)->update($updates + ['updated_at' => date('Y-m-d H:i:s')]);
            return $db->table('tb_pelanggan')->where('id_pelanggan', $customer)->get()->getRowArray();
        });
    }
}
