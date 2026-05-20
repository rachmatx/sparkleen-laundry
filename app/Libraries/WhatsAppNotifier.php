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
        $this->client = \Config\Services::curlrequest([
            'timeout'         => 15,
            'connect_timeout' => 10,
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

        if ($provider === 'link' || empty($this->config->apiToken)) {
            return [
                'success' => false,
                'message' => empty($this->config->apiToken)
                    ? 'Token API belum dikonfigurasi. Gunakan tombol WhatsApp untuk mengirim manual.'
                    : 'Mode tautan WhatsApp.',
                'wa_link' => $waLink,
            ];
        }

        if ($provider === 'fonnte') {
            return $this->sendViaFonnte($phone, $text, $waLink);
        }

        if ($provider === 'wablas') {
            return $this->sendViaWablas($phone, $text, $waLink);
        }

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

        if ($provider === 'link' || empty($this->config->apiToken)) {
            return [
                'success' => false,
                'message' => empty($this->config->apiToken)
                    ? 'Token API belum dikonfigurasi. Gunakan tombol WhatsApp untuk mengirim manual.'
                    : 'Mode tautan WhatsApp.',
                'wa_link' => $waLink,
            ];
        }

        if ($provider === 'fonnte') {
            return $this->sendViaFonnte($phone, $text, $waLink);
        }

        if ($provider === 'wablas') {
            return $this->sendViaWablas($phone, $text, $waLink);
        }

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

        if ($provider === 'link' || empty($this->config->apiToken)) {
            return [
                'success' => false,
                'message' => empty($this->config->apiToken)
                    ? 'Token API belum dikonfigurasi. Gunakan tombol WhatsApp untuk mengirim manual.'
                    : 'Mode tautan WhatsApp.',
                'wa_link' => $waLink,
            ];
        }

        if ($provider === 'fonnte') {
            return $this->sendViaFonnte($phone, $text, $waLink);
        }

        if ($provider === 'wablas') {
            return $this->sendViaWablas($phone, $text, $waLink);
        }

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
        $toko   = $this->config->businessName;

        return "Halo {$nama},\n\n"
            . "Terima kasih telah mencuci di *{$toko}*.\n"
            . "Pesanan Anda telah kami terima dengan detail berikut:\n\n"
            . "*No. Nota:* {$nota}\n"
            . "*Berat:* {$berat} kg\n"
            . "*Total Biaya:* Rp {$total}\n"
            . "*Estimasi Selesai:* {$estimasi}\n\n"
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
        $toko   = $this->config->businessName;

        return "Halo {$nama},\n\n"
            . "Laundry Anda dengan nomor nota *{$nota}* sudah *SELESAI* dan siap diambil.\n\n"
            . "Total: Rp {$total}\n"
            . "Berat: {$berat} kg\n\n"
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
    protected function sendViaFonnte(string $phone, string $message, string $waLink): array
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

            $body   = json_decode($response->getBody(), true);
            $status = $response->getStatusCode();

            if ($status >= 200 && $status < 300 && ($body['status'] ?? true) !== false) {
                return [
                    'success' => true,
                    'message' => 'Notifikasi WhatsApp berhasil dikirim ke pelanggan.',
                    'wa_link' => $waLink,
                ];
            }

            $err = is_array($body) ? ($body['reason'] ?? $body['message'] ?? json_encode($body)) : (string) $response->getBody();

            return [
                'success' => false,
                'message' => 'Gagal kirim via Fonnte: ' . $err,
                'wa_link' => $waLink,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Fonnte WA: ' . $e->getMessage());

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
    protected function sendViaWablas(string $phone, string $message, string $waLink): array
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

            $body   = json_decode($response->getBody(), true);
            $status = $response->getStatusCode();

            if ($status >= 200 && $status < 300 && ($body['status'] ?? true) !== false) {
                return [
                    'success' => true,
                    'message' => 'Notifikasi WhatsApp berhasil dikirim ke pelanggan.',
                    'wa_link' => $waLink,
                ];
            }

            $err = is_array($body) ? ($body['message'] ?? json_encode($body)) : (string) $response->getBody();

            return [
                'success' => false,
                'message' => 'Gagal kirim via Wablas: ' . $err,
                'wa_link' => $waLink,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Wablas WA: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal menghubungi API Wablas. Gunakan tautan manual.',
                'wa_link' => $waLink,
            ];
        }
    }
}
