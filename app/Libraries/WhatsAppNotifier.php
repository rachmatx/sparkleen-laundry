<?php

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;
use Config\WhatsApp as WhatsAppConfig;

class WhatsAppNotifier
{
    protected WhatsAppConfig $config;

    protected CURLRequest $client;

    public function __construct(?WhatsAppConfig $config = null)
    {
        $this->config = $config ?? config('WhatsApp');
        
        // Disable SSL verification on non-production environments
        $verifySsl = env('CI_ENVIRONMENT') === 'production';
        
        $this->client = \Config\Services::curlrequest([
            'timeout'         => 15,
            'connect_timeout' => 10,
            'verify'          => $verifySsl,
        ]);
    }

    /**
     * Kirim notifikasi order selesai ke pelanggan.
     *
     * @return array{success: bool, message: string, wa_link: ?string}
     */
    public function notifyOrderSelesai(array $transaksi, array $pelanggan): array
    {
        if (! $this->config->enabled) {
            return [
                'success' => false,
                'message' => 'Notifikasi WhatsApp dinonaktifkan.',
                'wa_link' => null,
            ];
        }

        $phone = $this->normalizePhone($pelanggan['no_telp'] ?? '');
        if ($phone === '') {
            return [
                'success' => false,
                'message' => 'Nomor telepon pelanggan tidak valid.',
                'wa_link' => null,
            ];
        }

        $text    = $this->buildOrderSelesaiMessage($transaksi, $pelanggan);
        $waLink  = $this->buildWaMeLink($phone, $text);
        $provider = strtolower($this->config->provider);
        $noNota   = $transaksi['no_nota'] ?? '-';

        if ($provider === 'link' || empty($this->config->apiToken)) {
            $this->logDelivery($noNota, $phone, $text, 'order_selesai', 'link', 'pending', empty($this->config->apiToken) ? 'Token API belum dikonfigurasi (Manual Link)' : 'Mode tautan manual (Link)');
            return [
                'success' => false,
                'message' => empty($this->config->apiToken)
                    ? 'Token API belum dikonfigurasi. Gunakan tombol WhatsApp untuk mengirim manual.'
                    : 'Mode tautan WhatsApp.',
                'wa_link' => $waLink,
            ];
        }

        if ($provider === 'fonnte') {
            return $this->sendViaFonnte($phone, $text, $waLink, $noNota, 'order_selesai');
        }

        if ($provider === 'wablas') {
            return $this->sendViaWablas($phone, $text, $waLink, $noNota, 'order_selesai');
        }

        $this->logDelivery($noNota, $phone, $text, 'order_selesai', $provider, 'gagal', 'Provider tidak dikenali');
        return [
            'success' => false,
            'message' => 'Provider WhatsApp tidak dikenali: ' . $provider,
            'wa_link' => $waLink,
        ];
    }

    /**
     * Kirim notifikasi order baru ke pelanggan.
     *
     * @return array{success: bool, message: string, wa_link: ?string}
     */
    public function notifyOrderBaru(array $transaksi, array $pelanggan): array
    {
        if (! $this->config->enabled) {
            return [
                'success' => false,
                'message' => 'Notifikasi WhatsApp dinonaktifkan.',
                'wa_link' => null,
            ];
        }

        $phone = $this->normalizePhone($pelanggan['no_telp'] ?? '');
        if ($phone === '') {
            return [
                'success' => false,
                'message' => 'Nomor telepon pelanggan tidak valid.',
                'wa_link' => null,
            ];
        }

        $text    = $this->buildOrderBaruMessage($transaksi, $pelanggan);
        $waLink  = $this->buildWaMeLink($phone, $text);
        $provider = strtolower($this->config->provider);
        $noNota   = $transaksi['no_nota'] ?? '-';

        if ($provider === 'link' || empty($this->config->apiToken)) {
            $this->logDelivery($noNota, $phone, $text, 'order_baru', 'link', 'pending', empty($this->config->apiToken) ? 'Token API belum dikonfigurasi (Manual Link)' : 'Mode tautan manual (Link)');
            return [
                'success' => false,
                'message' => empty($this->config->apiToken)
                    ? 'Token API belum dikonfigurasi. Gunakan tombol WhatsApp untuk mengirim manual.'
                    : 'Mode tautan WhatsApp.',
                'wa_link' => $waLink,
            ];
        }

        if ($provider === 'fonnte') {
            return $this->sendViaFonnte($phone, $text, $waLink, $noNota, 'order_baru');
        }

        if ($provider === 'wablas') {
            return $this->sendViaWablas($phone, $text, $waLink, $noNota, 'order_baru');
        }

        $this->logDelivery($noNota, $phone, $text, 'order_baru', $provider, 'gagal', 'Provider tidak dikenali');
        return [
            'success' => false,
            'message' => 'Provider WhatsApp tidak dikenali: ' . $provider,
            'wa_link' => $waLink,
        ];
    }

    /**
     * Kirim notifikasi order diambil ke pelanggan.
     *
     * @return array{success: bool, message: string, wa_link: ?string}
     */
    public function notifyOrderDiambil(array $transaksi, array $pelanggan): array
    {
        if (! $this->config->enabled) {
            return [
                'success' => false,
                'message' => 'Notifikasi WhatsApp dinonaktifkan.',
                'wa_link' => null,
            ];
        }

        $phone = $this->normalizePhone($pelanggan['no_telp'] ?? '');
        if ($phone === '') {
            return [
                'success' => false,
                'message' => 'Nomor telepon pelanggan tidak valid.',
                'wa_link' => null,
            ];
        }

        $text    = $this->buildOrderDiambilMessage($transaksi, $pelanggan);
        $waLink  = $this->buildWaMeLink($phone, $text);
        $provider = strtolower($this->config->provider);
        $noNota   = $transaksi['no_nota'] ?? '-';

        if ($provider === 'link' || empty($this->config->apiToken)) {
            $this->logDelivery($noNota, $phone, $text, 'order_diambil', 'link', 'pending', empty($this->config->apiToken) ? 'Token API belum dikonfigurasi (Manual Link)' : 'Mode tautan manual (Link)');
            return [
                'success' => false,
                'message' => empty($this->config->apiToken)
                    ? 'Token API belum dikonfigurasi. Gunakan tombol WhatsApp untuk mengirim manual.'
                    : 'Mode tautan WhatsApp.',
                'wa_link' => $waLink,
            ];
        }

        if ($provider === 'fonnte') {
            return $this->sendViaFonnte($phone, $text, $waLink, $noNota, 'order_diambil');
        }

        if ($provider === 'wablas') {
            return $this->sendViaWablas($phone, $text, $waLink, $noNota, 'order_diambil');
        }

        $this->logDelivery($noNota, $phone, $text, 'order_diambil', $provider, 'gagal', 'Provider tidak dikenali');
        return [
            'success' => false,
            'message' => 'Provider WhatsApp tidak dikenali: ' . $provider,
            'wa_link' => $waLink,
        ];
    }

    public function buildOrderBaruMessage(array $transaksi, array $pelanggan): string
    {
        $nama   = $pelanggan['nama_pelanggan'] ?? 'Pelanggan';
        $nota   = $transaksi['no_nota'] ?? '-';
        $total  = number_format((float) ($transaksi['grand_total'] ?? 0), 0, ',', '.');
        $berat  = $transaksi['total_berat'] ?? 0;
        $estimasi = date('d-m-Y H:i', strtotime($transaksi['tgl_selesai_estimasi'] ?? ''));
        $pin    = $transaksi['pin_pengambilan'] ?? '-';
        $toko   = $this->config->businessName;

        return "Halo {$nama},\n\n"
            . "Terima kasih telah mencuci di *{$toko}*.\n"
            . "Pesanan Anda telah kami terima dengan detail berikut:\n\n"
            . "*No. Nota:* {$nota}\n"
            . "*Berat:* {$berat} kg\n"
            . "*Total Biaya:* Rp {$total}\n"
            . "*Estimasi Selesai:* {$estimasi}\n\n"
            . "🔒 *PIN PENGAMBILAN: {$pin}*\n"
            . "_Harap tunjukkan PIN ini kepada Kasir saat mengambil cucian. Jangan berikan kepada siapapun!_\n\n"
            . "Anda dapat memeriksa status cucian Anda secara real-time melalui tautan berikut:\n"
            . base_url('lacak') . "?kode={$nota}\n\n"
            . "Anda akan menerima notifikasi kembali jika cucian Anda sudah siap diambil.\n\n"
            . '_Pesan otomatis dari sistem._';
    }

    public function buildOrderDiambilMessage(array $transaksi, array $pelanggan): string
    {
        $nama   = $pelanggan['nama_pelanggan'] ?? 'Pelanggan';
        $nota   = $transaksi['no_nota'] ?? '-';
        $toko   = $this->config->businessName;

        return "Halo {$nama},\n\n"
            . "Pesanan Anda dengan nomor nota *{$nota}* telah *DIAMBIL*.\n\n"
            . "Terima kasih banyak atas kepercayaan Anda mencuci di *{$toko}*. Kami menantikan kedatangan Anda kembali!\n\n"
            . '_Pesan otomatis dari sistem._';
    }

    public function buildOrderSelesaiMessage(array $transaksi, array $pelanggan): string
    {
        $nama   = $pelanggan['nama_pelanggan'] ?? 'Pelanggan';
        $nota   = $transaksi['no_nota'] ?? '-';
        $total  = number_format((float) ($transaksi['grand_total'] ?? 0), 0, ',', '.');
        $berat  = $transaksi['total_berat'] ?? 0;
        $pin    = $transaksi['pin_pengambilan'] ?? '-';
        $toko   = $this->config->businessName;

        return "Halo {$nama},\n\n"
            . "Laundry Anda dengan nomor nota *{$nota}* sudah *SELESAI* dan siap diambil.\n\n"
            . "Total: Rp {$total}\n"
            . "Berat: {$berat} kg\n\n"
            . "🔒 *JANGAN LUPA: PIN PENGAMBILAN ANDA ADALAH {$pin}*\n"
            . "Harap tunjukkan PIN ini kepada Kasir saat mengambil pakaian Anda.\n\n"
            . "Terima kasih telah menggunakan layanan {$toko}.\n\n"
            . '_Pesan otomatis dari sistem._';
    }

    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    public function buildWaMeLink(string $phone, string $message): string
    {
        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }

    /**
     * @return array{success: bool, message: string, wa_link: ?string}
     */
    protected function sendViaFonnte(string $phone, string $message, string $waLink, string $noNota, string $tipe): array
    {
        $url = $this->config->apiUrl !== '' ? $this->config->apiUrl : 'https://api.fonnte.com/send';

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => $this->config->apiToken,
                ],
                'form_params' => [
                    'target'  => $phone,
                    'message' => $message,
                ],
                'http_errors' => false,
            ]);

            $rawBody = (string) $response->getBody();
            $body   = json_decode($rawBody, true);
            $status = $response->getStatusCode();

            if ($status >= 200 && $status < 300 && ($body['status'] ?? true) !== false) {
                $this->logDelivery($noNota, $phone, $message, $tipe, 'fonnte', 'sukses', $rawBody);
                return [
                    'success' => true,
                    'message' => 'Notifikasi WhatsApp berhasil dikirim ke pelanggan.',
                    'wa_link' => $waLink,
                ];
            }

            $err = is_array($body) ? ($body['reason'] ?? $body['message'] ?? json_encode($body)) : $rawBody;
            $this->logDelivery($noNota, $phone, $message, $tipe, 'fonnte', 'gagal', $err);

            return [
                'success' => false,
                'message' => 'Gagal kirim via Fonnte: ' . $err,
                'wa_link' => $waLink,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Fonnte WA: ' . $e->getMessage());
            $this->logDelivery($noNota, $phone, $message, $tipe, 'fonnte', 'gagal', 'Exception: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal menghubungi API Fonnte. Gunakan tautan manual.',
                'wa_link' => $waLink,
            ];
        }
    }

    /**
     * @return array{success: bool, message: string, wa_link: ?string}
     */
    protected function sendViaWablas(string $phone, string $message, string $waLink, string $noNota, string $tipe): array
    {
        $url = $this->config->apiUrl !== '' ? $this->config->apiUrl : 'https://solo.wablas.com/api/send-message';

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => $this->config->apiToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'phone'   => $phone,
                    'message' => $message,
                ],
                'http_errors' => false,
            ]);

            $rawBody = (string) $response->getBody();
            $body   = json_decode($rawBody, true);
            $status = $response->getStatusCode();

            if ($status >= 200 && $status < 300 && ($body['status'] ?? true) !== false) {
                $this->logDelivery($noNota, $phone, $message, $tipe, 'wablas', 'sukses', $rawBody);
                return [
                    'success' => true,
                    'message' => 'Notifikasi WhatsApp berhasil dikirim ke pelanggan.',
                    'wa_link' => $waLink,
                ];
            }

            $err = is_array($body) ? ($body['message'] ?? json_encode($body)) : $rawBody;
            $this->logDelivery($noNota, $phone, $message, $tipe, 'wablas', 'gagal', $err);

            return [
                'success' => false,
                'message' => 'Gagal kirim via Wablas: ' . $err,
                'wa_link' => $waLink,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Wablas WA: ' . $e->getMessage());
            $this->logDelivery($noNota, $phone, $message, $tipe, 'wablas', 'gagal', 'Exception: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal menghubungi API Wablas. Gunakan tautan manual.',
                'wa_link' => $waLink,
            ];
        }
    }

    private function logDelivery(string $noNota, string $phone, string $message, string $tipe, string $provider, string $status, ?string $responApi): void
    {
        try {
            $statusMap = [
                'pending' => 1,
                'sukses'  => 2,
                'gagal'   => 3,
            ];
            $idStatus = $statusMap[$status] ?? 1;

            $db = \Config\Database::connect();
            $db->table('tb_log_whatsapp')->insert([
                'no_nota'            => $noNota,
                'no_tujuan'          => $phone,
                'isi_pesan'          => $message,
                'tipe_notifikasi'    => $tipe,
                'provider'           => $provider,
                'id_status_whatsapp' => $idStatus,
                'respon_api'         => $responApi,
                'created_at'         => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menulis log WhatsApp: ' . $e->getMessage());
        }
    }
}
