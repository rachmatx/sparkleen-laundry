<?php

// app/Controllers/Dashboard.php

namespace App\Controllers;

use App\Models\MTransaksi;
use App\Models\MPelanggan;
use App\Models\MKaryawan;
use App\Models\MInventaris;

class Dashboard extends BaseController
{
    protected $transaksiModel;
    protected $pelangganModel;
    protected $karyawanModel;
    protected $inventarisModel;
    protected $db;

    public function __construct()
    {
        if (!session()->get('isLoggedIn')) {
            header('Location: ' . base_url('/login'));
            exit();
        }

        $this->transaksiModel  = new MTransaksi();
        $this->pelangganModel  = new MPelanggan();
        $this->karyawanModel   = new MKaryawan();
        $this->inventarisModel = new MInventaris();
        $this->db              = \Config\Database::connect();
    }

    public function index()
    {
        $level = session()->get('level');

        switch ($level) {
            case 'kasir':
                return $this->dashboardKasir();
            case 'admin':
                return $this->dashboardAdmin();
            case 'pemilik':
                return $this->dashboardOwner();
            default:
                return $this->dashboardKasir();
        }
    }

    // =========================================================================
    // DASHBOARD KASIR
    // =========================================================================
    private function dashboardKasir()
    {
        $today = date('Y-m-d');

        // Ringkasan pesanan masuk hari ini
        $pesananMasukHariIni = $this->transaksiModel
            ->where('DATE(tgl_masuk)', $today)
            ->countAllResults();

        // Status cucian yang perlu segera diproses
        $statusMenunggu = $this->transaksiModel
            ->where('status', 'menunggu')
            ->countAllResults();

        $statusProses = $this->transaksiModel
            ->where('status', 'proses')
            ->countAllResults();

        $statusSetrika = $this->transaksiModel
            ->where('status', 'setrika')
            ->countAllResults();

        $totalDiproses = $statusMenunggu + $statusProses + $statusSetrika;

        // 5 transaksi terbaru
        $transaksiTerbaru = $this->transaksiModel
            ->select('tb_transaksi.id_transaksi, tb_transaksi.no_nota, tb_transaksi.grand_total, tb_transaksi.status, tb_transaksi.status_pembayaran, tb_transaksi.tgl_masuk, tb_pelanggan.nama_pelanggan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->orderBy('tb_transaksi.id_transaksi', 'DESC')
            ->limit(5)
            ->findAll();

        $data = [
            'title'               => 'Dashboard Kasir',
            'activeMenu'          => 'dashboard',
            'pesananMasukHariIni' => $pesananMasukHariIni,
            'statusMenunggu'      => $statusMenunggu,
            'statusProses'        => $statusProses,
            'statusSetrika'       => $statusSetrika,
            'totalDiproses'       => $totalDiproses,
            'transaksiTerbaru'    => $transaksiTerbaru,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('dashboard/kasir', $data)
             . view('backend/template/footer');
    }

    // =========================================================================
    // DASHBOARD ADMIN
    // =========================================================================
    private function dashboardAdmin()
    {
        $today = date('Y-m-d');

        // Ringkasan hari ini
        $transaksiHariIni = $this->transaksiModel
            ->where('DATE(tgl_masuk)', $today)
            ->countAllResults();

        $pendapatanHariIni = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('DATE(tgl_masuk)', $today)
            ->first();

        // Grafik pendapatan 7 hari terakhir
        $chart7Hari = [];
        for ($i = 6; $i >= 0; $i--) {
            $tgl = date('Y-m-d', strtotime("-$i days"));

            $pendapatan = $this->transaksiModel
                ->select('SUM(grand_total) as total')
                ->where('DATE(tgl_masuk)', $tgl)
                ->first();

            $jumlahTrx = $this->transaksiModel
                ->where('DATE(tgl_masuk)', $tgl)
                ->countAllResults();

            $chart7Hari[] = [
                'tanggal'    => date('d/m', strtotime($tgl)),
                'pendapatan' => (int)($pendapatan['total'] ?? 0),
                'transaksi'  => $jumlahTrx,
            ];
        }

        // Stok inventaris menipis (stok <= stok_minimal)
        $stokMenipis = [];
        try {
            $stokMenipis = $this->inventarisModel->getStokKritis();
        } catch (\Throwable $e) {
            $stokMenipis = [];
        }

        // Log aktivitas terbaru dari tb_status_log
        $logAktivitas = [];
        try {
            $logAktivitas = $this->db->table('tb_status_log sl')
                ->select('sl.id_log, sl.status_lama, sl.status_baru, sl.tgl_ubah, k.nama_karyawan')
                ->join('tb_karyawan k', 'k.id_karyawan = sl.id_karyawan', 'left')
                ->orderBy('sl.tgl_ubah', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            $logAktivitas = [];
        }

        $data = [
            'title'              => 'Dashboard Admin',
            'activeMenu'         => 'dashboard',
            'transaksiHariIni'   => $transaksiHariIni,
            'pendapatanHariIni'  => $pendapatanHariIni['total'] ?? 0,
            'chart7Hari'         => $chart7Hari,
            'stokMenipis'        => $stokMenipis,
            'logAktivitas'       => $logAktivitas,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('dashboard/admin', $data)
             . view('backend/template/footer');
    }

    // =========================================================================
    // DASHBOARD OWNER (PEMILIK)
    // =========================================================================
    private function dashboardOwner()
    {
        $bulanIni  = date('Y-m');
        $bulanLalu = date('Y-m', strtotime('first day of last month'));
        $tahunIni  = date('Y');
        $bulanNum  = date('m');

        // Omzet bulan ini
        $omzetBulanIni = $this->db->table('tb_transaksi')
            ->selectSum('grand_total', 'total')
            ->where("DATE_FORMAT(tgl_masuk, '%Y-%m')", $bulanIni)
            ->get()->getRowArray();
        $omzetBulanIni = (int)($omzetBulanIni['total'] ?? 0);

        // Omzet bulan lalu
        $omzetBulanLalu = $this->db->table('tb_transaksi')
            ->selectSum('grand_total', 'total')
            ->where("DATE_FORMAT(tgl_masuk, '%Y-%m')", $bulanLalu)
            ->get()->getRowArray();
        $omzetBulanLalu = (int)($omzetBulanLalu['total'] ?? 0);

        // Growth persentase
        if ($omzetBulanLalu > 0) {
            $growthPersen = (($omzetBulanIni - $omzetBulanLalu) / $omzetBulanLalu) * 100;
        } else {
            $growthPersen = ($omzetBulanIni > 0) ? 100 : 0;
        }
        $growthNominal = $omzetBulanIni - $omzetBulanLalu;

        // Total gaji karyawan (semua karyawan aktif)
        $totalGajiKaryawan = 0;
        try {
            $gajiRow = $this->db->table('tb_karyawan')
                ->selectSum('gaji_pokok', 'total')
                ->get()->getRowArray();
            $totalGajiKaryawan = (int)($gajiRow['total'] ?? 0);
        } catch (\Throwable $e) {
            $totalGajiKaryawan = 0;
        }

        // Total harga beli inventaris yang digunakan bulan ini
        $totalBeliInventarisBulanIni = 0;
        try {
            $beliRow = $this->db->table('tb_penggunaan_inventaris pi')
                ->select('SUM(pi.jumlah_digunakan * inv.harga_beli) as total')
                ->join('tb_inventaris inv', 'inv.id_inventaris = pi.id_inventaris')
                ->where("DATE_FORMAT(pi.tgl_penggunaan, '%Y-%m')", $bulanIni)
                ->get()->getRowArray();
            $totalBeliInventarisBulanIni = (int)($beliRow['total'] ?? 0);
        } catch (\Throwable $e) {
            $totalBeliInventarisBulanIni = 0;
        }

        // Estimasi keuntungan bersih
        $keuntunganBersih = $omzetBulanIni - ($totalGajiKaryawan + $totalBeliInventarisBulanIni);

        // Status Piutang & Tagihan bulan ini
        $piutangStats = ['lunas' => 0, 'piutang' => 0];
        try {
            $piutangRow = $this->db->table('tb_transaksi')
                ->select("
                    SUM(CASE WHEN status_pembayaran = 'lunas' THEN grand_total ELSE 0 END) as total_lunas,
                    SUM(CASE WHEN status_pembayaran != 'lunas' THEN grand_total ELSE 0 END) as total_piutang
                ")
                ->where("DATE_FORMAT(tgl_masuk, '%Y-%m')", $bulanIni)
                ->get()->getRowArray();
            
            $piutangStats['lunas']   = (int)($piutangRow['total_lunas'] ?? 0);
            $piutangStats['piutang'] = (int)($piutangRow['total_piutang'] ?? 0);
        } catch (\Throwable $e) {
            $piutangStats = ['lunas' => 0, 'piutang' => 0];
        }

        // Perbandingan metode pembayaran bulan ini (pie chart)
        $metodePembayaran = [];
        try {
            $metodePembayaran = $this->db->table('tb_pembayaran p')
                ->select('p.metode_bayar, SUM(p.nominal_bayar) as total')
                ->join('tb_transaksi t', 't.id_transaksi = p.id_transaksi')
                ->where("DATE_FORMAT(t.tgl_masuk, '%Y-%m')", $bulanIni)
                ->groupBy('p.metode_bayar')
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            $metodePembayaran = [];
        }

        // Pertumbuhan pelanggan
        $totalPelanggan = $this->pelangganModel->countAll();
        $pelangganBaruBulanIni = 0;
        try {
            $pelangganBaruBulanIni = $this->db->table('tb_pelanggan')
                ->where("DATE_FORMAT(created_at, '%Y-%m')", $bulanIni)
                ->countAllResults();
        } catch (\Throwable $e) {
            $pelangganBaruBulanIni = 0;
        }

        // Top 5 layanan terlaris (berdasarkan pendapatan bulan ini)
        $topLayanan = [];
        try {
            $topLayanan = $this->db->table('tb_detail_transaksi dt')
                ->select('l.nama_layanan, SUM(dt.subtotal) as total_pendapatan, SUM(dt.berat) as total_berat, COUNT(dt.id_detail) as jumlah_order')
                ->join('tb_layanan l', 'l.id_layanan = dt.id_layanan')
                ->join('tb_transaksi t', 't.id_transaksi = dt.id_transaksi')
                ->where("DATE_FORMAT(t.tgl_masuk, '%Y-%m')", $bulanIni)
                ->groupBy('dt.id_layanan')
                ->orderBy('total_pendapatan', 'DESC')
                ->limit(5)
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            $topLayanan = [];
        }

        // Top 5 performa karyawan kasir (berdasarkan jumlah transaksi bulan ini)
        $topKasir = [];
        try {
            $topKasir = $this->db->table('tb_transaksi t')
                ->select('k.nama_karyawan, COUNT(t.id_transaksi) as jumlah_transaksi, SUM(t.grand_total) as total_omzet')
                ->join('tb_karyawan k', 'k.id_karyawan = t.id_kasir')
                ->where("DATE_FORMAT(t.tgl_masuk, '%Y-%m')", $bulanIni)
                ->groupBy('t.id_kasir')
                ->orderBy('jumlah_transaksi', 'DESC')
                ->limit(5)
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            $topKasir = [];
        }

        $data = [
            'title'                       => 'Dashboard Pemilik',
            'activeMenu'                  => 'dashboard',
            'omzetBulanIni'               => $omzetBulanIni,
            'omzetBulanLalu'              => $omzetBulanLalu,
            'growthPersen'                => round($growthPersen, 2),
            'growthNominal'               => $growthNominal,
            'totalGajiKaryawan'           => $totalGajiKaryawan,
            'totalBeliInventarisBulanIni' => $totalBeliInventarisBulanIni,
            'keuntunganBersih'            => $keuntunganBersih,
            'piutangStats'                => $piutangStats,
            'metodePembayaran'            => $metodePembayaran,
            'totalPelanggan'              => $totalPelanggan,
            'pelangganBaruBulanIni'       => $pelangganBaruBulanIni,
            'topLayanan'                  => $topLayanan,
            'topKasir'                    => $topKasir,
            'bulanIni'                    => date('F Y'),
            'bulanLaluLabel'              => date('F Y', strtotime('first day of last month')),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('dashboard/owner', $data)
             . view('backend/template/footer');
    }
}