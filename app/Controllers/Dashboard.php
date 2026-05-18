<?php

namespace App\Controllers;

use App\Models\MTransaksi;
use App\Models\MPelanggan;

class Dashboard extends BaseController
{
    protected $transaksiModel;
    protected $pelangganModel;

    public function __construct()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        
        $this->transaksiModel = new MTransaksi();
        $this->pelangganModel = new MPelanggan();
    }

    public function index()
    {
        $today = date('Y-m-d');
        
        $pendapatanHariIni = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('DATE(tgl_masuk)', $today)
            ->first();
        
        $transaksiHariIni = $this->transaksiModel
            ->where('DATE(tgl_masuk)', $today)
            ->countAllResults();
        
        $transaksiProses = $this->transaksiModel
            ->where('status', 'proses')
            ->countAllResults();
        
        $jumlahPelanggan = $this->pelangganModel->countAll();
        
        $data = [
            'title' => 'Dashboard',
            'activeMenu' => 'dashboard',
            'pendapatanHariIni' => $pendapatanHariIni['total'] ?? 0,
            'transaksiHariIni' => $transaksiHariIni,
            'transaksiProses' => $transaksiProses,
            'jumlahPelanggan' => $jumlahPelanggan
        ];
        
        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('dashboard/index', $data)
             . view('backend/template/footer');
    }
}