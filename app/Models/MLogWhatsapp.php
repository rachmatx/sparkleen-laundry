<?php

namespace App\Models;

use CodeIgniter\Model;

class MLogWhatsapp extends Model
{
    protected $table         = 'tb_log_whatsapp';
    protected $primaryKey    = 'id_log';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $allowedFields = [
        'no_nota',
        'no_tujuan',
        'isi_pesan',
        'tipe_notifikasi',
        'provider',
        'status',
        'respon_api',
    ];

    public function getDaftarLog(?string $status = null, ?string $tipe = null, ?string $dari = null, ?string $sampai = null, ?string $search = null, int $limit = 20, int $offset = 0): array
    {
        $builder = $this->orderBy('created_at', 'DESC');

        if ($status !== null && $status !== '') {
            $builder->where('status', $status);
        }
        if ($tipe !== null && $tipe !== '') {
            $builder->where('tipe_notifikasi', $tipe);
        }
        if ($dari !== null && $dari !== '') {
            $builder->where('DATE(created_at) >=', $dari);
        }
        if ($sampai !== null && $sampai !== '') {
            $builder->where('DATE(created_at) <=', $sampai);
        }
        if ($search !== null && $search !== '') {
            $builder->groupStart()
                ->like('no_nota', $search)
                ->orLike('no_tujuan', $search)
            ->groupEnd();
        }

        return $builder->findAll($limit, $offset);
    }
}
