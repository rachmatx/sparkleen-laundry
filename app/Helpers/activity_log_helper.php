<?php

if (! function_exists('log_aktivitas')) {
    /**
     * Catat aktivitas admin/pemilik ke tb_log_aktivitas.
     */
    function log_aktivitas(
        string $modul,
        string $aksi,
        ?int $idReferensi,
        ?string $namaReferensi,
        ?array $detail = null
    ): void {
        if (! session()->get('isLoggedIn')) {
            return;
        }

        $model = new \App\Models\MLogAktivitas();
        $model->insert([
            'id_karyawan'    => (int) session()->get('id_karyawan'),
            'nama_karyawan'  => session()->get('nama_karyawan'),
            'level_karyawan' => session()->get('level'),
            'modul'          => $modul,
            'aksi'           => $aksi,
            'id_referensi'   => $idReferensi,
            'nama_referensi' => $namaReferensi,
            'detail'         => $detail !== null ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null,
            'ip_address'     => service('request')->getIPAddress(),
        ]);
    }
}

if (! function_exists('detail_perubahan')) {
    /**
     * Bandingkan data lama vs baru; kembalikan array perubahan (dari → ke).
     */
    function detail_perubahan(array $sebelum, array $sesudah, array $fields): array
    {
        $perubahan = [];

        foreach ($fields as $field) {
            $lama = $sebelum[$field] ?? null;
            $baru = $sesudah[$field] ?? null;

            if ((string) $lama !== (string) $baru) {
                $perubahan[$field] = ['dari' => $lama, 'ke' => $baru];
            }
        }

        return $perubahan;
    }
}

if (! function_exists('label_modul_log')) {
    function label_modul_log(string $modul): string
    {
        return match ($modul) {
            'karyawan'   => 'Karyawan',
            'inventaris' => 'Inventaris',
            default      => ucfirst($modul),
        };
    }
}

if (! function_exists('label_aksi_log')) {
    function label_aksi_log(string $aksi): string
    {
        return match ($aksi) {
            'tambah'       => 'Tambah',
            'ubah'         => 'Ubah',
            'hapus'        => 'Hapus',
            'aktifkan'     => 'Aktifkan',
            'nonaktifkan'  => 'Nonaktifkan',
            default        => ucfirst($aksi),
        };
    }
}

if (! function_exists('badge_aksi_log')) {
    function badge_aksi_log(string $aksi): string
    {
        return match ($aksi) {
            'tambah'      => 'text-bg-success',
            'ubah'        => 'text-bg-warning',
            'hapus'       => 'text-bg-danger',
            'aktifkan'    => 'text-bg-info',
            'nonaktifkan' => 'text-bg-secondary',
            default       => 'text-bg-light',
        };
    }
}
