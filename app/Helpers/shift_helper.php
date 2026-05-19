<?php

/**
 * Fungsi untuk mendeteksi shift berdasarkan waktu (DATETIME).
 *
 * @param string $datetime Format YYYY-MM-DD HH:MM:SS
 * @return string Nama shift beserta jamnya
 */
if (!function_exists('deteksi_shift')) {
    function deteksi_shift($datetime)
    {
        // Ekstrak jam dari datetime
        $waktu = strtotime($datetime);
        $jam = (int) date('H', $waktu);

        // Shift Pagi (07:00 - 14:59) -> Jam 7 sampai 14
        if ($jam >= 7 && $jam < 15) {
            return "Shift Pagi (07:00 - 15:00)";
        }
        // Shift Sore (15:00 - 21:59) -> Jam 15 sampai 21
        elseif ($jam >= 15 && $jam < 22) {
            return "Shift Sore (15:00 - 22:00)";
        }
        // Selain waktu di atas
        else {
            return "Shift Lainnya";
        }
    }
}
