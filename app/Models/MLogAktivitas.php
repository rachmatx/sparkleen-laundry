<?php

namespace App\Models;

use CodeIgniter\Model;

class MLogAktivitas extends Model
{
    protected $table         = 'tb_log_aktivitas';
    protected $primaryKey    = 'id_log';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $allowedFields = [
        'id_karyawan',
        'nama_karyawan',
        'level_karyawan',
        'modul',
        'aksi',
        'id_referensi',
        'nama_referensi',
        'detail',
        'ip_address',
    ];

    public function getDaftarLog(?string $modul = null, ?string $aksi = null, ?string $dari = null, ?string $sampai = null): array
    {
        $builder = $this->orderBy('created_at', 'DESC');

        if ($modul !== null && $modul !== '') {
            $builder->where('modul', $modul);
        }
        if ($aksi !== null && $aksi !== '') {
            $builder->where('aksi', $aksi);
        }
        if ($dari !== null && $dari !== '') {
            $builder->where('DATE(created_at) >=', $dari);
        }
        if ($sampai !== null && $sampai !== '') {
            $builder->where('DATE(created_at) <=', $sampai);
        }

        return $builder->findAll(500);
    }
}
