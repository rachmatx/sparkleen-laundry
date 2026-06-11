<?php

// app/Controllers/Dashboard.php

namespace App\Controllers;

use App\Services\DashboardService;

class Dashboard extends BaseController
{
    protected $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
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
        $data = array_merge([
            'title'      => 'Dashboard Kasir',
            'activeMenu' => 'dashboard',
        ], $this->dashboardService->getKasirData());

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('dashboard/kasir', $data)
             . view('backend/template/footer');
    }

    /**
     * API endpoint untuk polling live-counter dashboard kasir.
     * Dipanggil via AJAX setiap 30 detik oleh kasir.php.
     * Route: GET /dashboard/api/status-kasir  (dilindungi AuthFilter)
     */
    public function apiStatusKasir()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        $transaksiModel = new \App\Models\MTransaksi();
        $today = date('Y-m-d');

        $pesananMasukHariIni = $transaksiModel->where('DATE(tgl_masuk)', $today)->countAllResults();
        $statusMenunggu      = $transaksiModel->where('id_status_transaksi', 1)->countAllResults();
        $statusProses        = $transaksiModel->where('id_status_transaksi', 2)->countAllResults();
        $statusSetrika       = $transaksiModel->where('id_status_transaksi', 3)->countAllResults();

        return $this->response->setJSON([
            'pesananMasukHariIni' => $pesananMasukHariIni,
            'statusMenunggu'      => $statusMenunggu,
            'statusProses'        => $statusProses,
            'statusSetrika'       => $statusSetrika,
            'totalDiproses'       => $statusMenunggu + $statusProses + $statusSetrika,
            'timestamp'           => date('H:i:s'),
        ]);
    }

    // =========================================================================
    // DASHBOARD ADMIN
    // =========================================================================
    private function dashboardAdmin()
    {
        $data = array_merge([
            'title'      => 'Dashboard Admin',
            'activeMenu' => 'dashboard',
        ], $this->dashboardService->getAdminData());

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
        $data = array_merge([
            'title'      => 'Dashboard Pemilik',
            'activeMenu' => 'dashboard',
        ], $this->dashboardService->getOwnerData());

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('dashboard/owner', $data)
             . view('backend/template/footer');
    }
}