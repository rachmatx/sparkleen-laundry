<?php

namespace App\Controllers;

use App\Models\MLogAktivitas;

class LogAktivitas extends BaseController
{
    protected MLogAktivitas $logModel;

    public function __construct()
    {
        if (session()->get('level') !== 'pemilik') {
            session()->setFlashdata('error', 'Hanya Pemilik yang dapat melihat log aktivitas.');
            header('Location: ' . base_url('/dashboard'));
            exit();
        }

        $this->logModel = new MLogAktivitas();
    }

    public function index()
    {
        $modul  = $this->request->getGet('modul');
        $aksi   = $this->request->getGet('aksi');
        $dari   = $this->request->getGet('dari');
        $sampai = $this->request->getGet('sampai');

        $data = [
            'title'      => 'Log Aktivitas',
            'activeMenu' => 'log_aktivitas',
            'logs'       => $this->logModel->getDaftarLog($modul, $aksi, $dari, $sampai),
            'filter'     => [
                'modul'  => $modul ?? '',
                'aksi'   => $aksi ?? '',
                'dari'   => $dari ?? '',
                'sampai' => $sampai ?? '',
            ],
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('log_aktivitas/index', $data)
             . view('backend/template/footer');
    }
}
