<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class AuthThrottle extends BaseConfig
{
    /** Maksimal percobaan login gagal sebelum dikunci */
    public int $maxAttempts = 5;

    /** Durasi penguncian (detik) — default 1 menit */
    public int $lockoutSeconds = 60;

    /** Tampilkan sisa percobaan di pesan error */
    public bool $showRemainingAttempts = true;
}
