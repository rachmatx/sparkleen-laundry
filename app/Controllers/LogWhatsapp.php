<?php

namespace App\Controllers;

use App\Models\MLogWhatsapp;

class LogWhatsapp extends BaseController
{
    protected MLogWhatsapp $logModel;

    public function __construct()
    {


        $this->logModel = new MLogWhatsapp();
    }

    public function index()
    {
        $status = $this->request->getGet('status');
        $tipe   = $this->request->getGet('tipe');
        $dari   = $this->request->getGet('dari');
        $sampai = $this->request->getGet('sampai');
        $search = $this->request->getGet('search');

        $page = (int)($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $items = $this->logModel->getDaftarLog($status, $tipe, $dari, $sampai, $search, $limit + 1, $offset);
        $hasNext = count($items) > $limit;
        if ($hasNext) {
            array_pop($items);
        }
        $hasPrev = $page > 1;

        $data = [
            'title'      => 'Log Pengiriman WhatsApp',
            'activeMenu' => 'log_whatsapp',
            'logs'       => $items,
            'page'       => $page,
            'hasPrev'    => $hasPrev,
            'hasNext'    => $hasNext,
            'limit'      => $limit,
            'filter'     => [
                'status' => $status ?? '',
                'tipe'   => $tipe ?? '',
                'dari'   => $dari ?? '',
                'sampai' => $sampai ?? '',
                'search' => $search ?? '',
            ],
            'summary'    => [
                'sukses'  => $this->logModel->where('id_status_whatsapp', 2)->where('DATE(created_at)', date('Y-m-d'))->countAllResults(),
                'gagal'   => $this->logModel->where('id_status_whatsapp', 3)->where('DATE(created_at)', date('Y-m-d'))->countAllResults(),
                'pending' => $this->logModel->where('id_status_whatsapp', 1)->where('DATE(created_at)', date('Y-m-d'))->countAllResults(),
            ]
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('log_whatsapp/index', $data)
             . view('backend/template/footer');
    }
}
