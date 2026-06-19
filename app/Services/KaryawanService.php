<?php

namespace App\Services;

use App\Models\MKaryawan;
use App\Models\MasterLevelKaryawanModel;

class KaryawanService
{
    protected $karyawanModel;
    protected $levelModel;

    public function __construct()
    {
        $this->karyawanModel = new MKaryawan();
        $this->levelModel    = new MasterLevelKaryawanModel();
    }

    /**
     * Validasi apakah session user (admin) diizinkan mengelola target level.
     * Admin hanya boleh mengelola kasir, tidak boleh admin/pemilik.
     *
     * @return string|null  Error message jika tidak diizinkan, null jika OK.
     */
    public function validateAdminAccess(string $sessionLevel, int $id_level): ?string
    {
        if ($sessionLevel === 'admin' && in_array($id_level, [1, 3])) {
            return 'Admin hanya dapat membuat/mengubah akun dengan level Kasir.';
        }

        return null;
    }

    /**
     * Validasi apakah admin diizinkan mengelola karyawan berdasarkan level-nya.
     *
     * @return string|null  Error message jika tidak diizinkan, null jika OK.
     */
    public function validateAdminManageTarget(string $sessionLevel, string $targetLevel): ?string
    {
        if ($sessionLevel === 'admin' && in_array($targetLevel, ['admin', 'pemilik'])) {
            return 'Admin hanya diizinkan untuk mengelola akun Kasir.';
        }

        return null;
    }

    /**
     * Siapkan data karyawan untuk insert/update.
     *
     * @param array $postData  Data dari request POST
     * @param bool  $isNew     Apakah ini insert baru (password wajib) atau update
     * @return array  Data yang sudah di-hash dan divalidasi
     */
    public function prepareKaryawanData(array $postData, bool $isNew): array
    {
        $data = [
            'nama_karyawan' => $postData['nama_karyawan'] ?? '',
            'username'      => $postData['username'] ?? '',
            'id_level'      => $postData['id_level'] ?? null,
            'no_telp'       => $postData['no_telp'] ?? '',
            'email'         => !empty($postData['email']) ? $postData['email'] : null,
            'gaji_pokok'    => $postData['gaji_pokok'] ?? 0,
        ];

        if ($isNew) {
            $data['id_karyawan'] = null;
        } else {
            $data['id_karyawan'] = $postData['id_karyawan'] ?? null;
        }

        $password = $postData['password'] ?? '';
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        return $data;
    }

    /**
     * Catat perubahan data karyawan ke log aktivitas.
     */
    public function logKaryawanChange(string $aksi, int $id, string $nama, array $detail): void
    {
        log_aktivitas('karyawan', $aksi, $id, $nama, $detail);
    }

    /**
     * Ambil data karyawan dengan join level.
     */
    public function getKaryawanWithLevel(int $id): ?array
    {
        return $this->karyawanModel
            ->select('tb_karyawan.*, tb_master_level_karyawan.nama_level as level')
            ->join('tb_master_level_karyawan', 'tb_master_level_karyawan.id = tb_karyawan.id_level')
            ->find($id);
    }

    /**
     * Ambil daftar karyawan dengan join level, sorted dan paginated.
     */
    public function getDaftarKaryawan(string $sort, string $dir, int $limit, int $offset): array
    {
        $items = $this->karyawanModel
            ->select('tb_karyawan.*, tb_master_level_karyawan.nama_level as level')
            ->join('tb_master_level_karyawan', 'tb_master_level_karyawan.id = tb_karyawan.id_level')
            ->orderBy($sort, $dir)
            ->findAll($limit + 1, $offset);

        $hasNext = count($items) > $limit;
        if ($hasNext) {
            array_pop($items);
        }

        return [
            'items'    => $items,
            'has_next' => $hasNext,
        ];
    }

    /**
     * Cari karyawan untuk AJAX search dengan keyword.
     */
    public function searchKaryawan(string $keyword, string $sort, string $dir): array
    {
        $query = $this->karyawanModel
            ->select('tb_karyawan.id_karyawan, tb_karyawan.nama_karyawan, tb_karyawan.username, tb_karyawan.no_telp, tb_karyawan.gaji_pokok, tb_karyawan.is_aktif, tb_karyawan.id_level, tb_karyawan.created_at, tb_karyawan.updated_at, tb_master_level_karyawan.nama_level as level')
            ->join('tb_master_level_karyawan', 'tb_master_level_karyawan.id = tb_karyawan.id_level')
            ->orderBy($sort, $dir);

        if (!empty($keyword)) {
            $query->groupStart()
                  ->like('nama_karyawan', $keyword)
                  ->orLike('username', $keyword)
                  ->orLike('no_telp', $keyword)
                  ->orLike('tb_master_level_karyawan.nama_level', $keyword)
                  ->groupEnd();
        }

        return $query->findAll(100);
    }

    /**
     * Simpan data karyawan (insert atau update).
     *
     * @return bool
     */
    public function saveKaryawan(array $data): bool
    {
        return $this->karyawanModel->save($data);
    }

    /**
     * Ambil error validasi model terakhir.
     */
    public function getErrors(): array
    {
        return $this->karyawanModel->errors();
    }

    /**
     * Ambil ID karyawan yang baru di-insert.
     */
    public function getInsertID(): int
    {
        return (int) $this->karyawanModel->getInsertID();
    }

    /**
     * Update status aktif karyawan.
     */
    public function updateStatus(int $id, int $statusBaru): void
    {
        $this->karyawanModel->update($id, ['is_aktif' => $statusBaru]);
    }

    /**
     * Ambil daftar semua level karyawan.
     */
    public function getLevelList(): array
    {
        return $this->levelModel->findAll();
    }

    /**
     * Tentukan ID karyawan mana yang boleh dikelola oleh session user.
     */
    public function getCanManageIds(array $items, string $sessionLevel): array
    {
        $canManageIds = [];

        foreach ($items as $k) {
            if ($sessionLevel === 'pemilik') {
                $canManageIds[] = (string) $k['id_karyawan'];
            } elseif ($sessionLevel === 'admin' && $k['level'] === 'kasir') {
                $canManageIds[] = (string) $k['id_karyawan'];
            }
        }

        return $canManageIds;
    }
}
