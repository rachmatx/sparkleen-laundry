<?php

namespace App\Libraries\Mobile;

final class CustomerOrders
{
    public const LABELS = ['menunggu' => 'Diterima', 'proses' => 'Dicuci', 'setrika' => 'Disetrika', 'selesai' => 'Siap Diambil', 'diambil' => 'Selesai'];

    public static function iso(?string $value): ?string
    {
        if ($value === null || $value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }
        return (new \DateTimeImmutable($value, new \DateTimeZone('Asia/Jakarta')))->format(DATE_ATOM);
    }

    public static function money(mixed $value): int
    {
        return (int) round((float) $value);
    }

    private function query(int $customerId)
    {
        return db_connect()->table('tb_transaksi t')->select('t.*, st.nama_status as status, sp.nama_status as status_pembayaran, mp.nama_metode as metode_pembayaran')
            ->join('tb_master_status_transaksi st', 'st.id = t.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = t.id_status_pembayaran', 'left')
            ->join('tb_master_metode_pembayaran mp', 'mp.id = t.id_metode_pembayaran', 'left')
            ->where('t.id_pelanggan', $customerId)->where('t.deleted_at', null);
    }

    private function scoped(int $customerId, string $scope)
    {
        $query = $this->query($customerId);
        if ($scope !== 'semua') {
            $query->whereIn('st.nama_status', $scope === 'aktif' ? ['menunggu', 'proses', 'setrika'] : ['selesai', 'diambil']);
        }
        return $query;
    }

    public function listing(int $customerId, string $scope, int $page, int $perPage): array
    {
        $total = $this->scoped($customerId, $scope)->countAllResults();
        $rows = $this->scoped($customerId, $scope)->orderBy('t.id_transaksi', 'DESC')->get($perPage, ($page - 1) * $perPage)->getResultArray();
        return ['items' => array_map(fn ($row) => $this->summary($row), $rows),
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => max(1, (int) ceil($total / $perPage))]];
    }

    public function own(int $customerId, int $id): array
    {
        $row = $this->query($customerId)->where('t.id_transaksi', $id)->get()->getRowArray();
        if ($row === null) {
            throw new ApiProblem('NOT_FOUND', 'Pesanan tidak ditemukan.', 404);
        }
        return $row;
    }

    public function summary(array $row): array
    {
        $status = $row['status'] ?? 'unknown';
        return ['id_transaksi' => (int) $row['id_transaksi'], 'no_nota' => $row['no_nota'],
            'tgl_masuk' => self::iso($row['tgl_masuk']), 'tgl_selesai_estimasi' => self::iso($row['tgl_selesai_estimasi']),
            'total_berat' => (float) $row['total_berat'], 'grand_total' => self::money($row['grand_total']),
            'status' => $status, 'status_label' => self::LABELS[$status] ?? 'Status belum tersedia'];
    }

    public function items(int $id): array
    {
        $rows = db_connect()->table('tb_detail_transaksi d')->select('d.*, l.nama_layanan')->join('tb_layanan l', 'l.id_layanan = d.id_layanan', 'left')->where('d.id_transaksi', $id)->orderBy('d.id_detail')->get()->getResultArray();
        return array_map(fn ($r) => ['id_detail' => (int) $r['id_detail'], 'id_layanan' => (int) $r['id_layanan'], 'nama_layanan' => $r['nama_layanan'] ?? 'Layanan',
            'berat' => (float) $r['berat'], 'harga_satuan' => self::money($r['harga_satuan']), 'subtotal' => self::money($r['subtotal'])], $rows);
    }

    public function payments(int $id): array
    {
        $rows = db_connect()->table('tb_pembayaran p')->select('p.*, m.nama_metode as metode')->join('tb_master_metode_pembayaran m', 'm.id = p.id_metode_pembayaran', 'left')->where('p.id_transaksi', $id)->orderBy('p.id_pembayaran')->get()->getResultArray();
        return array_map(fn ($r) => ['id_pembayaran' => (int) $r['id_pembayaran'], 'tgl_bayar' => self::iso($r['tgl_bayar']), 'nominal_bayar' => self::money($r['nominal_bayar']),
            'metode' => $r['metode'] ?? '', 'status' => (float) $r['sisa_tagihan'] <= 0 ? 'lunas' : 'cicil'], $rows);
    }

    public function detail(array $row): array
    {
        $id = (int) $row['id_transaksi'];
        $payments = $this->payments($id);
        $logs = db_connect()->table('tb_status_log')->where('id_transaksi', $id)->orderBy('tgl_ubah')->orderBy('id_log')->get()->getResultArray();
        $times = ['menunggu' => self::iso($row['tgl_masuk'])];
        foreach ($logs as $log) {
            $times[$log['status_baru']] = self::iso($log['tgl_ubah']);
        }
        $keys = array_keys(self::LABELS);
        $current = array_search($row['status'], $keys, true);
        $timeline = [];
        foreach (self::LABELS as $status => $label) {
            $index = array_search($status, $keys, true);
            $timeline[] = ['status' => $status, 'label' => $label, 'at' => $times[$status] ?? null,
                'state' => $current === false ? 'pending' : ($index < $current ? 'done' : ($index === $current ? 'current' : 'pending'))];
        }
        return $this->summary($row) + ['tgl_selesai_aktual' => self::iso($row['tgl_selesai_aktual']),
            'total_biaya' => self::money($row['total_biaya']), 'diskon' => self::money($row['diskon']),
            'status_pembayaran' => $row['status_pembayaran'] ?? 'belum', 'metode_pembayaran' => $row['metode_pembayaran'],
            'sisa_tagihan' => max(0, self::money($row['grand_total']) - array_sum(array_column($payments, 'nominal_bayar'))),
            'pin_pengambilan' => $row['pin_pengambilan'], 'items' => $this->items($id), 'pembayaran' => $payments, 'timeline' => $timeline];
    }

    public function nota(array $row): array
    {
        return ['outlet' => self::outlet(), 'no_nota' => $row['no_nota'], 'tanggal' => self::iso($row['tgl_masuk']),
            'items' => $this->items((int) $row['id_transaksi']), 'subtotal' => self::money($row['total_biaya']),
            'diskon' => self::money($row['diskon']), 'grand_total' => self::money($row['grand_total']),
            'metode' => $row['metode_pembayaran'], 'status_pembayaran' => $row['status_pembayaran'] ?? 'belum'];
    }

    public static function outlet(): array
    {
        $config = config('Outlet');
        return ['nama' => $config->nama, 'no_wa' => $config->noWa, 'alamat' => $config->alamat, 'jam_buka' => $config->jamBuka, 'jam_tutup' => $config->jamTutup];
    }
}
