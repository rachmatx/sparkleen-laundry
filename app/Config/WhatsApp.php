<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class WhatsApp extends BaseConfig
{
    /** Aktifkan fitur notifikasi WhatsApp */
    public bool $enabled = true;

    /**
     * Provider pengiriman:
     * - fonnte  : API Fonnte (https://fonnte.com)
     * - wablas  : API Wablas
     * - link    : Hanya buat tautan wa.me (tanpa kirim otomatis)
     */
    public string $provider = 'fonnte';

    /** Token API (Fonnte / Wablas) */
    public string $apiToken = '';

    /** URL endpoint API (kosongkan untuk default provider) */
    public string $apiUrl = 'https://api.fonnte.com/send';

    /** Nama laundry di pesan */
    public string $businessName = 'Sparkleen Laundry';

    public function __construct()
    {
        parent::__construct();

        $this->enabled      = filter_var(env('whatsapp.enabled', $this->enabled), FILTER_VALIDATE_BOOLEAN);
        $this->provider     = (string) env('whatsapp.provider', $this->provider);
        $this->apiToken     = (string) env('whatsapp.apiToken', $this->apiToken);
        $this->apiUrl       = (string) env('whatsapp.apiUrl', $this->apiUrl);
        $this->businessName = (string) env('whatsapp.businessName', $this->businessName);
    }
}
