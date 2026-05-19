<?php

namespace App\Controllers;

use App\Models\MTransaksi;
use App\Models\MPelanggan;

class Dashboard extends BaseController
{
    protected $transaksiModel;
    protected $pelangganModel;
    protected $db;

    public function __construct()
    {
        if (!session()->get('isLoggedIn')) {
            header('Location: ' . base_url('/login'));
            exit();
        }

        $this->transaksiModel = new MTransaksi();
        $this->pelangganModel = new MPelanggan();
        $this->db             = \Config\Database::connect();
    }

    public function index()
    {
        $today = date('Y-m-d');

        // Card summary
        $pendapatanHariIni = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('DATE(tgl_masuk)', $today)
            ->first();

        $transaksiHariIni = $this->transaksiModel
            ->where('DATE(tgl_masuk)', $today)
            ->countAllResults();

        $transaksiProses = $this->transaksiModel
            ->whereIn('status', ['menunggu', 'proses', 'setrika'])
            ->countAllResults();

        $jumlahPelanggan = $this->pelangganModel->countAll();

        // Grafik 7 hari terakhir
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

        // Top 5 pelanggan teraktif
        $topPelanggan = $this->db->table('tb_transaksi t')
            ->select('p.nama_pelanggan, COUNT(t.id_transaksi) as jumlah_transaksi, SUM(t.grand_total) as total_belanja')
            ->join('tb_pelanggan p', 'p.id_pelanggan = t.id_pelanggan')
            ->groupBy('t.id_pelanggan')
            ->orderBy('jumlah_transaksi', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        // 5 Transaksi terbaru
        $transaksiTerbaru = $this->transaksiModel
            ->select('tb_transaksi.id_transaksi, tb_transaksi.no_nota, tb_transaksi.grand_total, tb_transaksi.status, tb_transaksi.status_pembayaran, tb_transaksi.tgl_masuk, tb_pelanggan.nama_pelanggan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->orderBy('tb_transaksi.id_transaksi', 'DESC')
            ->limit(5)
            ->findAll();

        // Stok kritis (aman jika tabel belum ada)
        $stokKritis = [];
        try {
            $stokKritis = $this->db->table('tb_inventaris')
                ->where('stok <= stok_minimal', null, false)
                ->orderBy('stok', 'ASC')
                ->limit(5)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            $stokKritis = [];
        }

        $data = [
            'title'             => 'Dashboard',
            'activeMenu'        => 'dashboard',
            'pendapatanHariIni' => $pendapatanHariIni['total'] ?? 0,
            'transaksiHariIni'  => $transaksiHariIni,
            'transaksiProses'   => $transaksiProses,
            'jumlahPelanggan'   => $jumlahPelanggan,
            'chart7Hari'        => $chart7Hari,
            'topPelanggan'      => $topPelanggan,
            'transaksiTerbaru'  => $transaksiTerbaru,
            'stokKritis'        => $stokKritis,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('dashboard/index', $data)
             . view('backend/template/footer');
    }
}