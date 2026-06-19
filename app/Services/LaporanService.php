<?php

namespace App\Services;

use App\Models\MTransaksi;

class LaporanService
{
    protected $transaksiModel;

    public function __construct()
    {
        $this->transaksiModel = new MTransaksi();
    }

    /**
     * Mapping nama bulan Indonesia.
     */
    public function getNamaBulan(string $bulan): string
    {
        $namaBulan = [
            '01' => 'Januari',   '02' => 'Februari',  '03' => 'Maret',
            '04' => 'April',     '05' => 'Mei',        '06' => 'Juni',
            '07' => 'Juli',      '08' => 'Agustus',    '09' => 'September',
            '10' => 'Oktober',   '11' => 'November',   '12' => 'Desember',
        ];

        return $namaBulan[$bulan] ?? $bulan;
    }

    // =========================================================================
    // LAPORAN BULANAN
    // =========================================================================

    /**
     * Ambil data transaksi bulanan dengan pagination.
     * Mengembalikan array hasil + has_next flag.
     */
    public function getTransaksiBulanan(int $bulan, int $tahun, int $limit = 20, int $offset = 0): array
    {
        $items = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, st.nama_status as status, sp.nama_status as status_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->orderBy('tgl_masuk', 'ASC')
            ->findAll($limit + 1, $offset);

        $hasNext = count($items) > $limit;
        if ($hasNext) {
            array_pop($items);
        }

        return [
            'items'    => $items,
            'has_next' => $hasNext,
        ];
    }

    /**
     * Ambil semua transaksi bulanan tanpa pagination (untuk export).
     */
    public function getAllTransaksiBulanan(int $bulan, int $tahun): array
    {
        return $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, st.nama_status as status, sp.nama_status as status_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->findAll();
    }

    /**
     * Ringkasan bulanan: total pendapatan, jumlah transaksi, pendapatan per hari.
     */
    public function getSummaryBulanan(int $bulan, int $tahun): array
    {
        $totalPendapatan = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->first();

        $pendapatanPerHari = $this->transaksiModel
            ->select('DATE(tgl_masuk) as tanggal, SUM(grand_total) as total')
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->groupBy('DATE(tgl_masuk)')
            ->orderBy('tanggal', 'ASC')
            ->findAll();

        $jumlahTransaksi = $this->transaksiModel
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->countAllResults();

        return [
            'total_pendapatan'   => $totalPendapatan['total'] ?? 0,
            'jumlah_transaksi'   => $jumlahTransaksi,
            'pendapatan_per_hari'=> $pendapatanPerHari,
        ];
    }

    // =========================================================================
    // LAPORAN HARIAN
    // =========================================================================

    /**
     * Ambil semua transaksi pada tanggal tertentu (untuk export).
     */
    public function getAllTransaksiHarian(string $tanggal): array
    {
        return $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, st.nama_status as status, sp.nama_status as status_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->where('DATE(tgl_masuk)', $tanggal)
            ->orderBy('tgl_masuk', 'ASC')
            ->findAll();
    }

    /**
     * Ringkasan harian: total pendapatan + jumlah transaksi pada tanggal tertentu.
     */
    public function getSummaryHarian(string $tanggal): array
    {
        $totalPendapatan = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('DATE(tgl_masuk)', $tanggal)
            ->first();

        $jumlahTransaksi = $this->transaksiModel
            ->where('DATE(tgl_masuk)', $tanggal)
            ->countAllResults();

        return [
            'total_pendapatan' => $totalPendapatan['total'] ?? 0,
            'jumlah_transaksi' => $jumlahTransaksi,
        ];
    }

    // =========================================================================
    // LAPORAN RENTANG TANGGAL
    // =========================================================================

    /**
     * Ambil data transaksi rentang tanggal dengan pagination.
     */
    public function getTransaksiRentang(string $tgl_awal, string $tgl_akhir, int $limit = 20, int $offset = 0): array
    {
        $items = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, st.nama_status as status, sp.nama_status as status_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->where('DATE(tgl_masuk) >=', $tgl_awal)
            ->where('DATE(tgl_masuk) <=', $tgl_akhir)
            ->orderBy('tgl_masuk', 'ASC')
            ->findAll($limit + 1, $offset);

        $hasNext = count($items) > $limit;
        if ($hasNext) {
            array_pop($items);
        }

        return [
            'items'    => $items,
            'has_next' => $hasNext,
        ];
    }

    /**
     * Ambil semua transaksi rentang tanggal tanpa pagination (untuk export).
     */
    public function getAllTransaksiRentang(string $tgl_awal, string $tgl_akhir): array
    {
        return $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, st.nama_status as status, sp.nama_status as status_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->where('DATE(tgl_masuk) >=', $tgl_awal)
            ->where('DATE(tgl_masuk) <=', $tgl_akhir)
            ->findAll();
    }

    /**
     * Ringkasan rentang tanggal: total pendapatan, total berat, jumlah transaksi.
     */
    public function getSummaryRentang(string $tgl_awal, string $tgl_akhir): array
    {
        $totalPendapatan = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('DATE(tgl_masuk) >=', $tgl_awal)
            ->where('DATE(tgl_masuk) <=', $tgl_akhir)
            ->first();

        $totalBerat = $this->transaksiModel
            ->select('SUM(total_berat) as berat')
            ->where('DATE(tgl_masuk) >=', $tgl_awal)
            ->where('DATE(tgl_masuk) <=', $tgl_akhir)
            ->first();

        $jumlahTransaksi = $this->transaksiModel
            ->where('DATE(tgl_masuk) >=', $tgl_awal)
            ->where('DATE(tgl_masuk) <=', $tgl_akhir)
            ->countAllResults();

        return [
            'total_pendapatan' => $totalPendapatan['total'] ?? 0,
            'total_berat'      => $totalBerat['berat'] ?? 0,
            'jumlah_transaksi' => $jumlahTransaksi,
        ];
    }
}
