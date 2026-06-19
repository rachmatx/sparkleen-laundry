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

        $today = date('Y-m-d');

        // Single query with conditional counts
        $db = \Config\Database::connect();
        $menunggu = \Config\StatusTransaksi::MENUNGGU;
        $proses   = \Config\StatusTransaksi::PROSES;
        $setrika  = \Config\StatusTransaksi::SETRIKA;

        $row = $db->table('tb_transaksi')
            ->select("
                COUNT(CASE WHEN DATE(tgl_masuk) = '{$today}' THEN 1 END) as pesanan_hari_ini,
                COUNT(CASE WHEN id_status_transaksi = {$menunggu} THEN 1 END) as menunggu,
                COUNT(CASE WHEN id_status_transaksi = {$proses} THEN 1 END) as proses,
                COUNT(CASE WHEN id_status_transaksi = {$setrika} THEN 1 END) as setrika
            ")
            ->where('deleted_at IS NULL')
            ->get()->getRowArray();

        $pesananMasukHariIni = (int)($row['pesanan_hari_ini'] ?? 0);
        $statusMenunggu      = (int)($row['menunggu'] ?? 0);
        $statusProses        = (int)($row['proses'] ?? 0);
        $statusSetrika       = (int)($row['setrika'] ?? 0);

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