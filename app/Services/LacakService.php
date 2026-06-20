<?php

namespace App\Services;

use App\Models\MTransaksi;
use App\Models\MDetailTransaksi;
use App\Models\MStatusLog;
use Config\StatusTransaksi;

class LacakService
{
    protected $transaksiModel;
    protected $detailModel;

    public function __construct()
    {
        $this->transaksiModel = new MTransaksi();
        $this->detailModel    = new MDetailTransaksi();
    }

    /**
     * Cek rate limit pencarian berdasarkan IP.
     *
     * @return array{blocked: bool, sisa_detik: int, attempts: int}
     */
    public function checkRateLimit(string $ip): array
    {
        $cacheKey    = 'lacak_throttle_' . md5($ip);
        $maxAttempts = 15;
        $lockoutSecs = 60;

        $throttle = cache($cacheKey) ?? ['attempts' => 0, 'locked_until' => 0];

        if ($throttle['locked_until'] > time()) {
            return [
                'blocked'    => true,
                'sisa_detik' => $throttle['locked_until'] - time(),
                'attempts'   => $throttle['attempts'],
            ];
        }

        return [
            'blocked'    => false,
            'sisa_detik' => 0,
            'attempts'   => $throttle['attempts'],
        ];
    }

    /**
     * Catat attempt pencarian dan update lockout jika diperlukan.
     */
    public function recordAttempt(string $ip): void
    {
        $cacheKey    = 'lacak_throttle_' . md5($ip);
        $maxAttempts = 15;
        $lockoutSecs = 60;

        $throttle = cache($cacheKey) ?? ['attempts' => 0, 'locked_until' => 0];
        $throttle['attempts']++;

        if ($throttle['attempts'] >= $maxAttempts) {
            $throttle['locked_until'] = time() + $lockoutSecs;
        }

        cache()->save($cacheKey, $throttle, $lockoutSecs + 10);
    }

    /**
     * Cari transaksi berdasarkan nomor nota.
     *
     * @return array{transaksi: array|null, detail: array, status_log: array, error: string|null}
     */
    public function searchByNota(string $nota): array
    {
        if (empty($nota)) {
            return [
                'transaksi'  => null,
                'detail'     => [],
                'status_log' => [],
                'error'      => 'Silakan masukkan nomor nota transaksi Anda.',
            ];
        }

        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, tb_pelanggan.no_telp, st.nama_status as status, sp.nama_status as status_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->where('tb_transaksi.no_nota', $nota)
            ->first();

        if (!$transaksi) {
            return [
                'transaksi'  => null,
                'detail'     => [],
                'status_log' => [],
                'error'      => 'Nomor nota tidak ditemukan. Pastikan format nomor nota yang Anda masukkan sudah benar (contoh: LAU/20260522/0001).',
            ];
        }

        $detail = $this->detailModel
            ->select('tb_detail_transaksi.*, tb_layanan.nama_layanan')
            ->join('tb_layanan', 'tb_layanan.id_layanan = tb_detail_transaksi.id_layanan')
            ->where('id_transaksi', $transaksi['id_transaksi'])
            ->findAll();

        $statusLogModel = new MStatusLog();
        $statusLog = $statusLogModel
            ->select('tb_status_log.*, tb_karyawan.nama_karyawan')
            ->join('tb_karyawan', 'tb_karyawan.id_karyawan = tb_status_log.id_karyawan')
            ->where('id_transaksi', $transaksi['id_transaksi'])
            ->orderBy('tgl_ubah', 'DESC')
            ->findAll();

        // Masking data sensitif
        $transaksi['nama_pelanggan_masked'] = $this->maskName($transaksi['nama_pelanggan']);
        $transaksi['no_telp_masked']        = $this->maskPhone($transaksi['no_telp']);

        return [
            'transaksi'  => $transaksi,
            'detail'     => $detail,
            'status_log' => $statusLog,
            'error'      => null,
        ];
    }

    /**
     * Cari transaksi berdasarkan nomor telepon.
     *
     * @return array{transaksi: array|null, detail: array, status_log: array, error: string|null}
     */
    public function searchByPhone(string $telp): array
    {
        $telp_normalized = preg_replace('/\D/', '', $telp);
        if (str_starts_with($telp_normalized, '0')) {
            $telp_normalized = '62' . substr($telp_normalized, 1);
        }

        if (strlen($telp_normalized) < 9) {
            return [
                'transaksi'  => null,
                'detail'     => [],
                'status_log' => [],
                'error'      => 'Nomor HP tidak valid. Masukkan minimal 9 digit.',
            ];
        }

        // Cari pelanggan berdasarkan no_telp
        $db = \Config\Database::connect();
        $pelanggan = $db->table('tb_pelanggan')
            ->groupStart()
                ->like('no_telp', $telp_normalized, 'none')
                ->orLike('no_telp', $telp, 'none')
            ->groupEnd()
            ->where('is_aktif', 1)
            ->get()->getRowArray();

        if (!$pelanggan) {
            return [
                'transaksi'  => null,
                'detail'     => [],
                'status_log' => [],
                'error'      => 'Nomor HP tidak terdaftar. Pastikan nomor yang Anda masukkan sudah benar.',
            ];
        }

        // Ambil transaksi terbaru yang belum diambil
        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, tb_pelanggan.no_telp, st.nama_status as status, sp.nama_status as status_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->where('tb_transaksi.id_pelanggan', $pelanggan['id_pelanggan'])
            ->whereNotIn('tb_transaksi.id_status_transaksi', [StatusTransaksi::DIAMBIL])
            ->orderBy('tb_transaksi.id_transaksi', 'DESC')
            ->first();

        // Hitung total nota aktif lainnya (untuk banner info jika >1)
        $totalAktif = $this->transaksiModel
            ->where('id_pelanggan', $pelanggan['id_pelanggan'])
            ->whereNotIn('id_status_transaksi', [StatusTransaksi::DIAMBIL])
            ->countAllResults();

        // Ambil ringkasan nota aktif lainnya (untuk daftar pilihan)
        $daftarNotaAktif = $this->transaksiModel
            ->select('tb_transaksi.id_transaksi, tb_transaksi.no_nota, tb_transaksi.tgl_masuk, tb_transaksi.tgl_selesai_estimasi, st.nama_status as status')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->where('tb_transaksi.id_pelanggan', $pelanggan['id_pelanggan'])
            ->whereNotIn('tb_transaksi.id_status_transaksi', [StatusTransaksi::DIAMBIL])
            ->orderBy('tb_transaksi.id_transaksi', 'DESC')
            ->limit(5)
            ->findAll();

        // Fallback: ambil transaksi apapun (termasuk yang sudah diambil)
        if (!$transaksi) {
            $transaksi = $this->transaksiModel
                ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, tb_pelanggan.no_telp, st.nama_status as status, sp.nama_status as status_pembayaran')
                ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
                ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
                ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
                ->where('tb_transaksi.id_pelanggan', $pelanggan['id_pelanggan'])
                ->orderBy('tb_transaksi.id_transaksi', 'DESC')
                ->first();
        }

        if (!$transaksi) {
            return [
                'transaksi'  => null,
                'detail'     => [],
                'status_log' => [],
                'error'      => 'Belum ada transaksi untuk nomor HP ini.',
            ];
        }

        $detail = $this->detailModel
            ->select('tb_detail_transaksi.*, tb_layanan.nama_layanan')
            ->join('tb_layanan', 'tb_layanan.id_layanan = tb_detail_transaksi.id_layanan')
            ->where('id_transaksi', $transaksi['id_transaksi'])
            ->findAll();

        $statusLogModel = new MStatusLog();
        $statusLog = $statusLogModel
            ->select('tb_status_log.*, tb_karyawan.nama_karyawan')
            ->join('tb_karyawan', 'tb_karyawan.id_karyawan = tb_status_log.id_karyawan')
            ->where('id_transaksi', $transaksi['id_transaksi'])
            ->orderBy('tgl_ubah', 'DESC')
            ->findAll();

        $transaksi['nama_pelanggan_masked'] = $this->maskName($transaksi['nama_pelanggan']);
        $transaksi['no_telp_masked']        = $this->maskPhone($transaksi['no_telp']);
        $transaksi['total_nota_aktif']     = $totalAktif;
        $transaksi['has_other_nota']       = ($totalAktif > 1);

        return [
            'transaksi'        => $transaksi,
            'detail'           => $detail,
            'status_log'       => $statusLog,
            'error'            => null,
            'daftar_nota_aktif' => $daftarNotaAktif,
        ];
    }

    /**
     * Menyamarkan nama pelanggan (misal: "Joko Susilo" -> "Jo****lo")
     */
    public function maskName(string $name): string
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
    public function maskPhone(string $phone): string
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

    /**
     * Definisi timeline status pelacakan (5 tahap).
     */
    public function getStatusTimeline(): array
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
}
