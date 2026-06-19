<?php

namespace App\Controllers;

use App\Services\LacakService;

class Lacak extends BaseController
{
    protected $lacakService;

    public function __construct()
    {
        $this->lacakService = new LacakService();
    }

    public function index()
    {
        $notaInput = $this->request->getGet('nota');
        $telp      = trim($this->request->getGet('telp') ?? '');
        $transaksi = null;
        $detail    = [];
        $statusLog = [];
        $searched  = false;
        $errorMsg  = null;

        $ip = $this->request->getIPAddress();

        // Rate limiting check
        $rateLimit = $this->lacakService->checkRateLimit($ip);

        if ($rateLimit['blocked']) {
            $errorMsg = "Terlalu banyak percobaan pencarian. Silakan coba lagi dalam {$rateLimit['sisa_detik']} detik.";
            return view('lacak/index', [
                'notaInput'      => $notaInput,
                'telp'           => $telp,
                'transaksi'      => null,
                'detail'         => [],
                'statusLog'      => [],
                'searched'       => true,
                'errorMsg'       => $errorMsg,
                'statusTimeline' => $this->lacakService->getStatusTimeline(),
            ]);
        }

        if ($notaInput !== null) {
            $searched = true;
            $nota = trim($notaInput);

            // Record attempt
            $this->lacakService->recordAttempt($ip);

            $result   = $this->lacakService->searchByNota($nota);
            $transaksi = $result['transaksi'];
            $detail    = $result['detail'];
            $statusLog = $result['status_log'];
            $errorMsg  = $result['error'];

        } elseif ($telp !== '') {
            $searched = true;

            // Record attempt
            $this->lacakService->recordAttempt($ip);

            $result   = $this->lacakService->searchByPhone($telp);
            $transaksi = $result['transaksi'];
            $detail    = $result['detail'];
            $statusLog = $result['status_log'];
            $errorMsg  = $result['error'];
            $daftarNotaAktif = $result['daftar_nota_aktif'] ?? [];
        }

        $data = [
            'notaInput'        => $notaInput,
            'telp'             => $telp,
            'transaksi'        => $transaksi,
            'detail'           => $detail,
            'statusLog'        => $statusLog,
            'searched'         => $searched,
            'errorMsg'         => $errorMsg,
            'statusTimeline'   => $this->lacakService->getStatusTimeline(),
            'rateLimitBlocked' => $rateLimit['blocked'],
            'sisaDetik'        => $rateLimit['sisa_detik'] ?? 0,
            'daftarNotaAktif'  => $daftarNotaAktif ?? [],
        ];

        return view('lacak/index', $data);
    }
}
