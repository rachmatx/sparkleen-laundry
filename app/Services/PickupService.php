<?php

namespace App\Services;

use App\Libraries\Mobile\ApiProblem;
use App\Libraries\Mobile\CustomerOrders;

final class PickupService
{
    public const SLOTS = ['pagi' => '08:00', 'siang' => '12:00', 'sore' => '15:00'];
    public const ACTIVE = ['menunggu', 'diproses'];

    private function invalid(string $field, string $message): never
    {
        throw new ApiProblem('VALIDATION_ERROR', 'Periksa data penjemputan.', 422, [$field => [$message]]);
    }

    public function weight(mixed $value, string $field = 'berat_perkiraan'): float
    {
        if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value < 0.1 || $value > 100) {
            $this->invalid($field, 'Berat harus antara 0,1 dan 100 kg.');
        }
        return round((float) $value, 2);
    }

    public function create(int $customerId, array $data): array
    {
        $serviceId = $data['id_layanan'] ?? null;
        if (!is_int($serviceId) || $serviceId < 1) $this->invalid('id_layanan', 'Pilih layanan yang tersedia.');
        $weight = $this->weight($data['berat_perkiraan'] ?? null);
        $address = $data['alamat_jemput'] ?? null;
        if (!is_string($address) || mb_strlen(trim($address)) < 10 || mb_strlen(trim($address)) > 500) {
            $this->invalid('alamat_jemput', 'Alamat harus 10–500 karakter.');
        }
        $note = $data['catatan'] ?? '';
        if (!is_string($note) || mb_strlen(trim($note)) > 255) $this->invalid('catatan', 'Catatan maksimal 255 karakter.');
        $date = $data['tgl_jemput'] ?? null;
        $slot = $data['slot_jemput'] ?? null;
        if (!is_string($slot) || !isset(self::SLOTS[$slot])) $this->invalid('slot_jemput', 'Pilih pagi, siang, atau sore.');
        $zone = new \DateTimeZone('Asia/Jakarta');
        $now = new \DateTimeImmutable('now', $zone);
        $day = is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) ? \DateTimeImmutable::createFromFormat('!Y-m-d', $date, $zone) : false;
        if (!$day || $day->format('Y-m-d') !== $date || $day > $now->modify('+30 days')) $this->invalid('tgl_jemput', 'Pilih tanggal valid hingga 30 hari ke depan.');
        $scheduled = new \DateTimeImmutable($day->format('Y-m-d') . ' ' . self::SLOTS[$slot], $zone);
        if ($scheduled <= $now) $this->invalid('tgl_jemput', 'Slot penjemputan sudah lewat. Pilih jadwal berikutnya.');

        return $this->atomic(function ($db) use ($customerId, $serviceId, $weight, $address, $note, $slot, $scheduled) {
            $customer = $db->query('SELECT * FROM tb_pelanggan WHERE id_pelanggan = ? FOR UPDATE', [$customerId])->getRowArray();
            if (!$customer || !(int) $customer['is_aktif']) throw new ApiProblem('AUTH_INVALID_TOKEN', 'Akun tidak aktif.', 401);
            $active = $db->query("SELECT id_permintaan FROM tb_permintaan_jemput WHERE active_customer_id = ? FOR UPDATE", [$customerId])->getRowArray();
            if ($active) throw new ApiProblem('ACTIVE_REQUEST_EXISTS', 'Masih ada permintaan jemput yang menunggu atau sedang diproses.', 409);
            $service = $db->table('tb_layanan')->where('id_layanan', $serviceId)->where('is_aktif', 1)->get()->getRowArray();
            if (!$service) $this->invalid('id_layanan', 'Layanan tidak tersedia.');
            $estimate = (int) round((float) $service['harga_per_kg'] * $weight);
            if ($estimate > 99999999) $this->invalid('berat_perkiraan', 'Estimasi biaya melebihi batas transaksi. Hubungi outlet.');
            $db->table('tb_permintaan_jemput')->insert([
                'id_pelanggan' => $customerId, 'active_customer_id' => $customerId,
                'id_layanan' => $serviceId, 'nama_layanan' => $service['nama_layanan'],
                'berat_perkiraan' => $weight, 'estimasi_harga' => $estimate,
                'estimasi_hari' => max(0, (int) $service['estimasi_hari']), 'alamat_jemput' => trim($address),
                'tgl_jemput' => $scheduled->format('Y-m-d H:i:s'), 'slot_jemput' => $slot,
                'catatan' => trim($note) === '' ? null : trim($note), 'status' => 'menunggu',
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return $this->get((int) $db->insertID(), $customerId);
        });
    }

    public function get(int $id, ?int $customerId = null): array
    {
        $query = db_connect()->table('tb_permintaan_jemput')->where('id_permintaan', $id);
        if ($customerId !== null) $query->where('id_pelanggan', $customerId);
        $row = $query->get()->getRowArray();
        if (!$row) throw new ApiProblem('NOT_FOUND', 'Permintaan tidak ditemukan.', 404);
        return $this->dto($row);
    }

    public function listing(int $customerId, string $scope, int $page, int $perPage): array
    {
        if (!in_array($scope, ['aktif', 'selesai', 'semua'], true) || $page < 1 || $page > 1000000 || $perPage < 1 || $perPage > 50) {
            throw new ApiProblem('VALIDATION_ERROR', 'Filter atau pagination tidak valid.', 422);
        }
        $query = db_connect()->table('tb_permintaan_jemput')->where('id_pelanggan', $customerId);
        if ($scope === 'aktif') $query->whereIn('status', self::ACTIVE);
        if ($scope === 'selesai') $query->whereIn('status', ['dikonfirmasi', 'ditolak', 'dibatalkan']);
        $total = (clone $query)->countAllResults();
        $rows = $query->orderBy('id_permintaan', 'DESC')->get($perPage, ($page - 1) * $perPage)->getResultArray();
        return ['items' => array_map($this->dto(...), $rows), 'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => max(1, (int) ceil($total / $perPage))]];
    }

    public function cancel(int $id, int $customerId): array
    {
        return $this->atomic(function ($db) use ($id, $customerId) {
            $db->query('SELECT id_pelanggan FROM tb_pelanggan WHERE id_pelanggan = ? FOR UPDATE', [$customerId]);
            $row = $db->query('SELECT * FROM tb_permintaan_jemput WHERE id_permintaan = ? AND id_pelanggan = ? FOR UPDATE', [$id, $customerId])->getRowArray();
            if (!$row) throw new ApiProblem('NOT_FOUND', 'Permintaan tidak ditemukan.', 404);
            if ($row['status'] === 'dibatalkan') return $this->dto($row);
            if ($row['status'] !== 'menunggu') throw new ApiProblem('REQUEST_NOT_CANCELLABLE', 'Permintaan sudah diproses. Hubungi outlet untuk perubahan.', 409);
            $db->table('tb_permintaan_jemput')->where('id_permintaan', $id)->update(['status' => 'dibatalkan', 'active_customer_id' => null, 'updated_at' => date('Y-m-d H:i:s')]);
            return $this->get($id, $customerId);
        });
    }

    public function act(int $id, int $employeeId, string $action, array $data = []): array
    {
        if (!in_array($action, ['proses', 'tolak', 'konfirmasi'], true)) throw new ApiProblem('VALIDATION_ERROR', 'Tindakan tidak dikenal.', 422);
        $lookup = db_connect()->table('tb_permintaan_jemput')->select('id_pelanggan')->where('id_permintaan', $id)->get()->getRowArray();
        if (!$lookup) throw new ApiProblem('NOT_FOUND', 'Permintaan tidak ditemukan.', 404);
        return $this->atomic(function ($db) use ($id, $employeeId, $action, $data, $lookup) {
            // Customer → request is the common lock order for create/cancel/cashier.
            $customer = $db->query('SELECT * FROM tb_pelanggan WHERE id_pelanggan = ? FOR UPDATE', [$lookup['id_pelanggan']])->getRowArray();
            $row = $db->query('SELECT * FROM tb_permintaan_jemput WHERE id_permintaan = ? FOR UPDATE', [$id])->getRowArray();
            if (!$row) throw new ApiProblem('NOT_FOUND', 'Permintaan tidak ditemukan.', 404);
            $employee = $db->table('tb_karyawan k')->select('k.*, l.nama_level')->join('tb_master_level_karyawan l', 'l.id = k.id_level')->where('k.id_karyawan', $employeeId)->get()->getRowArray();
            if (!$employee || !(int) $employee['is_aktif'] || !in_array($employee['nama_level'], ['admin', 'kasir'], true)) throw new ApiProblem('FORBIDDEN', 'Tindakan hanya untuk kasir atau admin aktif.', 403);
            if ($action === 'konfirmasi' && $row['status'] === 'dikonfirmasi') return $this->dto($row);
            if ($action === 'proses' && $row['status'] === 'diproses') return $this->dto($row);
            $updates = ['id_karyawan' => $employeeId, 'updated_at' => date('Y-m-d H:i:s')];
            if ($action === 'proses') {
                if ($row['status'] !== 'menunggu') throw new ApiProblem('INVALID_REQUEST_STATE', 'Permintaan tidak lagi menunggu.', 409);
                $updates['status'] = 'diproses';
            } elseif ($action === 'tolak') {
                if (!in_array($row['status'], self::ACTIVE, true)) throw new ApiProblem('INVALID_REQUEST_STATE', 'Permintaan sudah ditutup.', 409);
                $reason = $data['alasan_penolakan'] ?? null;
                if (!is_string($reason) || mb_strlen(trim($reason)) < 3 || mb_strlen(trim($reason)) > 255) $this->invalid('alasan_penolakan', 'Isi alasan penolakan 3–255 karakter.');
                $updates += ['status' => 'ditolak', 'active_customer_id' => null, 'alasan_penolakan' => trim($reason)];
            } else {
                if ($row['status'] !== 'diproses') throw new ApiProblem('INVALID_REQUEST_STATE', 'Proses penjemputan sebelum menerima cucian.', 409);
                if (!$customer || !(int) $customer['is_aktif']) throw new ApiProblem('VALIDATION_ERROR', 'Pelanggan tidak aktif. Hubungi admin.', 422);
                $weight = $this->weight($data['berat_aktual'] ?? null, 'berat_aktual');
                $service = $db->table('tb_layanan')->where('id_layanan', $row['id_layanan'])->where('is_aktif', 1)->get()->getRowArray();
                if (!$service) $this->invalid('id_layanan', 'Layanan sudah tidak tersedia. Tolak permintaan dan hubungi pelanggan.');
                if ($weight * (float) $service['harga_per_kg'] > 99999999) $this->invalid('berat_aktual', 'Total biaya melebihi batas transaksi.');
                $result = (new TransaksiService())->prosesTransaksiBaru([
                    'poin_ditebus' => 0,
                    'tgl_selesai_estimasi' => date('Y-m-d H:i:s', strtotime('+' . max(0, (int) $service['estimasi_hari']) . ' days')),
                ], $employeeId, [['id_layanan' => $row['id_layanan'], 'berat' => $weight]], $customer);
                if (!$result['success']) throw new \RuntimeException('Unable to create pickup transaction.');
                // The sale service's nested transaction does not commit this outer unit.
                $updates += ['status' => 'dikonfirmasi', 'active_customer_id' => null, 'id_transaksi' => $result['id_transaksi']];
            }
            $db->table('tb_permintaan_jemput')->where('id_permintaan', $id)->update($updates);
            $db->table('tb_log_aktivitas')->insert([
                'id_karyawan' => $employeeId, 'nama_karyawan' => $employee['nama_karyawan'], 'level_karyawan' => $employee['nama_level'],
                'modul' => 'Penjemputan', 'aksi' => $action, 'id_referensi' => $id, 'nama_referensi' => 'Permintaan #' . $id,
                'detail' => 'Permintaan #' . $id . ': ' . $updates['status'], 'created_at' => date('Y-m-d H:i:s'),
            ]);
            return $this->get($id);
        });
    }

    private function atomic(callable $action): mixed
    {
        $db = db_connect();
        if (!$db->transBegin()) throw new \RuntimeException('Unable to begin pickup operation.');
        try {
            $result = $action($db);
            if (!$db->transStatus() || !$db->transCommit()) throw new \RuntimeException('Pickup commit failed.');
            return $result;
        } catch (\Throwable $error) {
            $db->transRollback();
            $db->resetTransStatus();
            throw $error;
        }
    }

    private function dto(array $row): array
    {
        return [
            'id_permintaan' => (int) $row['id_permintaan'], 'status' => $row['status'],
            'id_layanan' => (int) $row['id_layanan'], 'nama_layanan' => $row['nama_layanan'],
            'berat_perkiraan' => (float) $row['berat_perkiraan'], 'estimasi_harga' => (int) $row['estimasi_harga'],
            'estimasi_hari' => (int) $row['estimasi_hari'], 'alamat_jemput' => $row['alamat_jemput'],
            'tgl_jemput' => CustomerOrders::iso($row['tgl_jemput']), 'slot_jemput' => $row['slot_jemput'],
            'catatan' => $row['catatan'], 'id_transaksi' => $row['id_transaksi'] === null ? null : (int) $row['id_transaksi'],
            'alasan_penolakan' => $row['alasan_penolakan'], 'created_at' => CustomerOrders::iso($row['created_at']),
        ];
    }
}
