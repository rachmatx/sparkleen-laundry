<?php

namespace App\Controllers;

use App\Models\MTransaksi;
use App\Models\MDetailTransaksi;

class Lacak extends BaseController
{
    protected $transaksiModel;
    protected $detailModel;

    public function __construct()
    {
        $this->transaksiModel = new MTransaksi();
        $this->detailModel    = new MDetailTransaksi();
    }

    public function index()
    {
        $notaInput = $this->request->getGet('nota');
        $transaksi = null;
        $detail    = [];
        $statusLog = [];
        $searched  = false;
        $errorMsg  = null;

        if ($notaInput !== null) {
            $searched = true;
            $nota = trim($notaInput);

            if (empty($nota)) {
                $errorMsg = 'Silakan masukkan nomor nota transaksi Anda.';
            } else {
                // Cari transaksi dengan join ke pelanggan
                $transaksi = $this->transaksiModel
                    ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, tb_pelanggan.no_telp, st.nama_status as status, sp.nama_status as status_pembayaran')
                    ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
                    ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
                    ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
                    ->where('tb_transaksi.no_nota', $nota)
                    ->first();

                if ($transaksi) {
                    // Ambil detail transaksi (layanan)
                    $detail = $this->detailModel
                        ->select('tb_detail_transaksi.*, tb_layanan.nama_layanan')
                        ->join('tb_layanan', 'tb_layanan.id_layanan = tb_detail_transaksi.id_layanan')
                        ->where('id_transaksi', $transaksi['id_transaksi'])
                        ->findAll();

                    // Ambil log status perubahan
                    $statusLog = $this->transaksiModel->db->table('tb_status_log')
                        ->select('tb_status_log.*, tb_karyawan.nama_karyawan')
                        ->join('tb_karyawan', 'tb_karyawan.id_karyawan = tb_status_log.id_karyawan')
                        ->where('id_transaksi', $transaksi['id_transaksi'])
                        ->orderBy('tgl_ubah', 'DESC')
                        ->get()
                        ->getResultArray();

                    // Masking data sensitif pelanggan
                    $transaksi['nama_pelanggan_masked'] = $this->maskName($transaksi['nama_pelanggan']);
                    $transaksi['no_telp_masked']        = $this->maskPhone($transaksi['no_telp']);
                } else {
                    $errorMsg = 'Nomor nota tidak ditemukan. Pastikan format nomor nota yang Anda masukkan sudah benar (contoh: LAU/20260522/0001).';
                }
            }
        }

        // Definisikan timeline status pelacakan
        $statusTimeline = [
            'menunggu' => [
                'step'    => 1,
                'percent' => 10,
                'label'   => 'Menunggu Antrean',
                'desc'    => 'Cucian Anda telah diterima dan masuk daftar antrean.',
                'icon'    => 'fa-clock',
                'color'   => '#6c757d'
            ],
            'proses' => [
                'step'    => 2,
                'percent' => 35,
                'label'   => 'Sedang Dicuci',
                'desc'    => 'Cucian Anda sedang diproses cuci dan pengeringan.',
                'icon'    => 'fa-soap',
                'color'   => '#0d6efd'
            ],
            'setrika' => [
                'step'    => 3,
                'percent' => 60,
                'label'   => 'Sedang Disetrika',
                'desc'    => 'Cucian Anda sedang disetrika dan dirapikan.',
                'icon'    => 'fa-shirt',
                'color'   => '#0dcaf0'
            ],
            'selesai' => [
                'step'    => 4,
                'percent' => 85,
                'label'   => 'Siap Diambil',
                'desc'    => 'Cucian Anda selesai disetrika, rapi, dan siap diambil!',
                'icon'    => 'fa-check-double',
                'color'   => '#198754'
            ],
            'diambil' => [
                'step'    => 5,
                'percent' => 100,
                'label'   => 'Sudah Diambil',
                'desc'    => 'Cucian telah diserahkan ke pelanggan. Terima kasih!',
                'icon'    => 'fa-handshake',
                'color'   => '#212529'
            ]
        ];

        $data = [
            'notaInput'      => $notaInput,
            'transaksi'      => $transaksi,
            'detail'         => $detail,
            'statusLog'      => $statusLog,
            'searched'       => $searched,
            'errorMsg'       => $errorMsg,
            'statusTimeline' => $statusTimeline,
        ];

        return view('lacak/index', $data);
    }

    /**
     * Menyamarkan nama pelanggan (misal: "Joko Susilo" -> "Jo****lo")
     */
    private function maskName($name)
    {
        $parts = explode(' ', trim($name));
        $maskedParts = [];
        foreach ($parts as $part) {
            $len = strlen($part);
            if ($len <= 2) {
                $maskedParts[] = $part[0] . str_repeat('*', $len - 1);
            } elseif ($len <= 4) {
                $maskedParts[] = $part[0] . str_repeat('*', $len - 2) . $part[$len - 1];
            } else {
                $maskedParts[] = substr($part, 0, 2) . str_repeat('*', $len - 4) . substr($part, -2);
            }
        }
        return implode(' ', $maskedParts);
    }

    /**
     * Menyamarkan nomor telepon (misal: "081234567890" -> "0812****7890")
     */
    private function maskPhone($phone)
    {
        $phone = preg_replace('/\D/', '', $phone);
        $len = strlen($phone);
        if ($len > 8) {
            return substr($phone, 0, 4) . str_repeat('*', $len - 8) . substr($phone, -4);
        } elseif ($len > 4) {
            return substr($phone, 0, 2) . str_repeat('*', $len - 4) . substr($phone, -2);
        } else {
            return str_repeat('*', $len);
        }
    }
}
