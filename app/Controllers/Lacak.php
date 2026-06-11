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
        $telp      = trim($this->request->getGet('telp') ?? '');
        $transaksi = null;
        $detail    = [];
        $statusLog = [];
        $searched  = false;
        $errorMsg  = null;

        // --- Rate Limiting: maks 15 pencarian per IP per 60 detik ---
        $ip          = $this->request->getIPAddress();
        $cacheKey    = 'lacak_throttle_' . md5($ip);
        $maxAttempts = 15;
        $lockoutSecs = 60;

        $throttle = cache($cacheKey) ?? ['attempts' => 0, 'locked_until' => 0];

        if ($throttle['locked_until'] > time()) {
            $sisaDetik = $throttle['locked_until'] - time();
            $errorMsg  = "Terlalu banyak percobaan pencarian. Silakan coba lagi dalam {$sisaDetik} detik.";
            return view('lacak/index', [
                'notaInput'      => $notaInput,
                'telp'           => $telp,
                'transaksi'      => null,
                'detail'         => [],
                'statusLog'      => [],
                'searched'       => true,
                'errorMsg'       => $errorMsg,
                'statusTimeline' => $this->_getStatusTimeline(),
            ]);
        }
        // --- Akhir Rate Limiting Guard ---

        if ($notaInput !== null) {
            $searched = true;
            $nota = trim($notaInput);

            // Catat percobaan pencarian ke cache (hanya jika ada nota yang di-submit)
            $throttle['attempts']++;
            if ($throttle['attempts'] >= $maxAttempts) {
                $throttle['locked_until'] = time() + $lockoutSecs;
            }
            cache()->save($cacheKey, $throttle, $lockoutSecs + 10);

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
                    $statusLogModel = new \App\Models\MStatusLog();
                    $statusLog = $statusLogModel
                        ->select('tb_status_log.*, tb_karyawan.nama_karyawan')
                        ->join('tb_karyawan', 'tb_karyawan.id_karyawan = tb_status_log.id_karyawan')
                        ->where('id_transaksi', $transaksi['id_transaksi'])
                        ->orderBy('tgl_ubah', 'DESC')
                        ->findAll();

                    // Masking data sensitif pelanggan
                    $transaksi['nama_pelanggan_masked'] = $this->maskName($transaksi['nama_pelanggan']);
                    $transaksi['no_telp_masked']        = $this->maskPhone($transaksi['no_telp']);
                } else {
                    $errorMsg = 'Nomor nota tidak ditemukan. Pastikan format nomor nota yang Anda masukkan sudah benar (contoh: LAU/20260522/0001).';
                }
            }

        } elseif ($telp !== '') {
            // --- PENCARIAN BY NOMOR HP ---
            $searched = true;

            // Hitung juga sebagai attempt throttle
            $throttle['attempts']++;
            if ($throttle['attempts'] >= $maxAttempts) {
                $throttle['locked_until'] = time() + $lockoutSecs;
            }
            cache()->save($cacheKey, $throttle, $lockoutSecs + 10);

            // Normalisasi: strip non-digit, ganti awalan 0 → 62
            $telp_normalized = preg_replace('/\D/', '', $telp);
            if (str_starts_with($telp_normalized, '0')) {
                $telp_normalized = '62' . substr($telp_normalized, 1);
            }

            if (strlen($telp_normalized) < 9) {
                $errorMsg = 'Nomor HP tidak valid. Masukkan minimal 9 digit.';
            } else {
                // Cari pelanggan berdasarkan no_telp (coba exact + awalan 08 → 628)
                $pelanggan = $this->transaksiModel->db->table('tb_pelanggan')
                    ->groupStart()
                        ->like('no_telp', $telp_normalized, 'none')
                        ->orLike('no_telp', $telp, 'none')
                    ->groupEnd()
                    ->where('is_aktif', 1)
                    ->get()->getRowArray();

                if (!$pelanggan) {
                    $errorMsg = 'Nomor HP tidak terdaftar. Pastikan nomor yang Anda masukkan sudah benar.';
                } else {
                    // Ambil transaksi terbaru yang belum selesai/diambil milik pelanggan ini
                    $transaksi = $this->transaksiModel
                        ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, tb_pelanggan.no_telp, st.nama_status as status, sp.nama_status as status_pembayaran')
                        ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
                        ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
                        ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
                        ->where('tb_transaksi.id_pelanggan', $pelanggan['id_pelanggan'])
                        ->whereNotIn('tb_transaksi.id_status_transaksi', [5]) // 5 = diambil
                        ->orderBy('tb_transaksi.id_transaksi', 'DESC')
                        ->first();

                    if (!$transaksi) {
                        // Coba transaksi apapun (termasuk yang sudah diambil)
                        $transaksi = $this->transaksiModel
                            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, tb_pelanggan.no_telp, st.nama_status as status, sp.nama_status as status_pembayaran')
                            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
                            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
                            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
                            ->where('tb_transaksi.id_pelanggan', $pelanggan['id_pelanggan'])
                            ->orderBy('tb_transaksi.id_transaksi', 'DESC')
                            ->first();
                    }

                    if ($transaksi) {
                        $detail = $this->detailModel
                            ->select('tb_detail_transaksi.*, tb_layanan.nama_layanan')
                            ->join('tb_layanan', 'tb_layanan.id_layanan = tb_detail_transaksi.id_layanan')
                            ->where('id_transaksi', $transaksi['id_transaksi'])
                            ->findAll();

                        $statusLogModel = new \App\Models\MStatusLog();
                        $statusLog = $statusLogModel
                            ->select('tb_status_log.*, tb_karyawan.nama_karyawan')
                            ->join('tb_karyawan', 'tb_karyawan.id_karyawan = tb_status_log.id_karyawan')
                            ->where('id_transaksi', $transaksi['id_transaksi'])
                            ->orderBy('tgl_ubah', 'DESC')
                            ->findAll();

                        $transaksi['nama_pelanggan_masked'] = $this->maskName($transaksi['nama_pelanggan']);
                        $transaksi['no_telp_masked']        = $this->maskPhone($transaksi['no_telp']);
                    } else {
                        $errorMsg = 'Belum ada transaksi untuk nomor HP ini.';
                    }
                }
            }
        }

        $data = [
            'notaInput'      => $notaInput,
            'telp'           => $telp,
            'transaksi'      => $transaksi,
            'detail'         => $detail,
            'statusLog'      => $statusLog,
            'searched'       => $searched,
            'errorMsg'       => $errorMsg,
            'statusTimeline' => $this->_getStatusTimeline(),
        ];

        return view('lacak/index', $data);
    }

    /**
     * Mengembalikan array definisi timeline status pelacakan (5 tahap).
     * Digunakan oleh index() dan blok rate-limit early-return.
     */
    private function _getStatusTimeline(): array
    {
        return [
            'menunggu' => [
                'step'    => 1,
                'percent' => 10,
                'label'   => 'Menunggu Antrean',
                'desc'    => 'Cucian Anda telah diterima dan masuk daftar antrean.',
                'icon'    => 'fa-clock',
                'color'   => '#6c757d',
            ],
            'proses' => [
                'step'    => 2,
                'percent' => 35,
                'label'   => 'Sedang Dicuci',
                'desc'    => 'Cucian Anda sedang diproses cuci dan pengeringan.',
                'icon'    => 'fa-soap',
                'color'   => '#0d6efd',
            ],
            'setrika' => [
                'step'    => 3,
                'percent' => 60,
                'label'   => 'Sedang Disetrika',
                'desc'    => 'Cucian Anda sedang disetrika dan dirapikan.',
                'icon'    => 'fa-shirt',
                'color'   => '#0dcaf0',
            ],
            'selesai' => [
                'step'    => 4,
                'percent' => 85,
                'label'   => 'Siap Diambil',
                'desc'    => 'Cucian Anda selesai disetrika, rapi, dan siap diambil!',
                'icon'    => 'fa-check-double',
                'color'   => '#198754',
            ],
            'diambil' => [
                'step'    => 5,
                'percent' => 100,
                'label'   => 'Sudah Diambil',
                'desc'    => 'Cucian telah diserahkan ke pelanggan. Terima kasih!',
                'icon'    => 'fa-handshake',
                'color'   => '#212529',
            ],
        ];
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
