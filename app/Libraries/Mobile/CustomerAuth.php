<?php

namespace App\Libraries\Mobile;

final class CustomerAuth
{
    public static function phone(string $phone): string
    {
        $phone = preg_replace('/[\s()+-]/', '', $phone);
        if (!preg_match('/^(08|62)[0-9]{7,13}$/D', $phone)) {
            throw new ApiProblem('VALIDATION_ERROR', 'Nomor telepon tidak valid.', 422, ['no_telp' => ['Gunakan nomor telepon Indonesia yang valid.']]);
        }
        $normalized = str_starts_with($phone, '0') ? '62' . substr($phone, 1) : $phone;
        if (strlen($normalized) > 15) {
            throw new ApiProblem('VALIDATION_ERROR', 'Nomor telepon terlalu panjang.', 422, ['no_telp' => ['Nomor telepon maksimal 15 digit setelah normalisasi.']]);
        }
        return $normalized;
    }

    public function findPhone(string $phone): ?array
    {
        $rows = db_connect()->table('tb_pelanggan')->whereIn('no_telp', [$phone, '0' . substr($phone, 2)])->get()->getResultArray();
        if (count($rows) > 1) {
            throw new ApiProblem('PHONE_CONFLICT', 'Data nomor telepon ganda. Hubungi outlet untuk memperbaikinya.', 409);
        }
        return $rows[0] ?? null;
    }

    public function throttle(string $action, string $phone, string $ip): void
    {
        $db = db_connect();
        // Atomic counters across PHP workers, with no phone/PIN in the table.
        foreach ([['ip:' . $ip, 30], [$action . ':' . $phone . ':' . $ip, 5]] as [$key, $limit]) {
            $hash = hash('sha256', $key);
            $now = time();
            $db->query('INSERT INTO tb_api_auth_attempts (bucket_hash, attempts, window_start) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE attempts = IF(window_start <= ?, 1, attempts + 1), window_start = IF(window_start <= ?, VALUES(window_start), window_start)', [$hash, $now, $now - 900, $now - 900]);
            $row = $db->table('tb_api_auth_attempts')->where('bucket_hash', $hash)->get()->getRowArray();
            if ((int) $row['attempts'] > $limit) {
                $retry = (string) max(1, 900 - ($now - (int) $row['window_start']));
                service('response')->setHeader('Retry-After', $retry);
                throw new ApiProblem('RATE_LIMITED', 'Terlalu banyak percobaan. Coba kembali nanti.', 429, ['retry_after' => [$retry]]);
            }
        }
    }

    public function issue(array $customer): array
    {
        $plain = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');
        db_connect()->table('tb_api_token')->insert([
            'id_pelanggan' => $customer['id_pelanggan'], 'token_hash' => hash('sha256', $plain),
            'created_at' => $now, 'expires_at' => date('Y-m-d H:i:s', time() + 30 * 86400),
        ]);
        db_connect()->table('tb_pelanggan')->where('id_pelanggan', $customer['id_pelanggan'])->update(['last_login_at' => $now]);
        return ['token' => $plain, 'expires_at' => CustomerOrders::iso(date('Y-m-d H:i:s', time() + 30 * 86400)), 'pelanggan' => self::profile($customer)];
    }

    public function fromHeader(string $header, bool $extend = false): array
    {
        if (!preg_match('/^Bearer ([a-f0-9]{64})$/Di', $header, $match)) {
            throw new ApiProblem('AUTH_INVALID_TOKEN', 'Sesi tidak valid. Silakan masuk kembali.', 401);
        }
        $row = db_connect()->table('tb_api_token t')->select('p.*, t.id_token')
            ->join('tb_pelanggan p', 'p.id_pelanggan = t.id_pelanggan')
            ->where('t.token_hash', hash('sha256', $match[1]))->where('t.revoked_at', null)
            ->where('t.expires_at >', date('Y-m-d H:i:s'))->where('p.is_aktif', 1)->get()->getRowArray();
        if ($row === null) {
            throw new ApiProblem('AUTH_INVALID_TOKEN', 'Sesi berakhir. Silakan masuk kembali.', 401);
        }
        if ($extend) {
            db_connect()->table('tb_api_token')->where('id_token', $row['id_token'])->update([
                'last_used_at' => date('Y-m-d H:i:s'), 'expires_at' => date('Y-m-d H:i:s', time() + 30 * 86400),
            ]);
        }
        return $row;
    }

    public static function profile(array $row): array
    {
        return ['id_pelanggan' => (int) $row['id_pelanggan'], 'kode_pelanggan' => $row['kode_pelanggan'],
            'nama' => $row['nama_pelanggan'], 'no_telp' => $row['no_telp'], 'alamat' => $row['alamat'],
            'poin' => (int) $row['poin'], 'total_transaksi' => (int) $row['total_transaksi']];
    }
}
