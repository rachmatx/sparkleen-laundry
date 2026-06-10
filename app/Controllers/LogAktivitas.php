<?php

namespace App\Controllers;

use App\Models\MLogAktivitas;

class LogAktivitas extends BaseController
{
    protected MLogAktivitas $logModel;

    public function __construct()
    {


        $this->logModel = new MLogAktivitas();
    }

    public function index()
    {
        $modul  = $this->request->getGet('modul');
        $aksi   = $this->request->getGet('aksi');
        $dari   = $this->request->getGet('dari');
        $sampai = $this->request->getGet('sampai');
        $search = $this->request->getGet('search');

        $page = (int)($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;
        $limit = 30;
        $offset = ($page - 1) * $limit;

        $items = $this->logModel->getDaftarLog($modul, $aksi, $dari, $sampai, $search, $limit + 1, $offset);
        $hasNext = count($items) > $limit;
        if ($hasNext) {
            array_pop($items);
        }
        $hasPrev = $page > 1;

        $data = [
            'title'      => 'Log Aktivitas',
            'activeMenu' => 'log_aktivitas',
            'logs'       => $items,
            'page'       => $page,
            'hasPrev'    => $hasPrev,
            'hasNext'    => $hasNext,
            'limit'      => $limit,
            'filter'     => [
                'modul'  => $modul ?? '',
                'aksi'   => $aksi ?? '',
                'dari'   => $dari ?? '',
                'sampai' => $sampai ?? '',
                'search' => $search ?? '',
            ],
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('log_aktivitas/index', $data)
             . view('backend/template/footer');
    }
}
