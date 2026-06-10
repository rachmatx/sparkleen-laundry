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
        'id_status_whatsapp',
        'respon_api',
    ];

    public function getDaftarLog(?string $status = null, ?string $tipe = null, ?string $dari = null, ?string $sampai = null, ?string $search = null, int $limit = 20, int $offset = 0): array
    {
        $builder = $this->select('tb_log_whatsapp.*, sw.nama_status as status')
                        ->join('tb_master_status_whatsapp sw', 'sw.id = tb_log_whatsapp.id_status_whatsapp', 'left')
                        ->orderBy('tb_log_whatsapp.created_at', 'DESC');

        if ($status !== null && $status !== '') {
            $builder->where('sw.nama_status', $status);
        }
        if ($tipe !== null && $tipe !== '') {
            $builder->where('tb_log_whatsapp.tipe_notifikasi', $tipe);
        }
        if ($dari !== null && $dari !== '') {
            $builder->where('DATE(tb_log_whatsapp.created_at) >=', $dari);
        }
        if ($sampai !== null && $sampai !== '') {
            $builder->where('DATE(tb_log_whatsapp.created_at) <=', $sampai);
        }
        if ($search !== null && $search !== '') {
            $builder->groupStart()
                ->like('tb_log_whatsapp.no_nota', $search)
                ->orLike('tb_log_whatsapp.no_tujuan', $search)
            ->groupEnd();
        }

        return $builder->findAll($limit, $offset);
    }
}
