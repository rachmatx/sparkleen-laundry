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