<?php

namespace App\Models;

use CodeIgniter\Model;

class MTransaksi extends Model
{
    protected $table = 'tb_transaksi';
    protected $primaryKey = 'id_transaksi';
    protected $allowedFields = [
        'no_nota', 'id_pelanggan', 'id_kasir', 'tgl_masuk',
        'tgl_selesai_estimasi', 'tgl_selesai_aktual', 'total_berat',
        'total_biaya', 'diskon', 'grand_total', 'status', 'status_pembayaran'
    ];
    protected $useTimestamps = true;

    // Generate nomor nota otomatis
    public function generateNota()
    {
        $tanggal = date('Ymd');
        $builder = $this->db->table($this->table);
        $builder->selectMax('id_transaksi');
        $query = $builder->get();
        $row = $query->getRow();
        $urut = ($row->id_transaksi ?? 0) + 1;
        return 'LAU/' . $tanggal . '/' . str_pad($urut, 4, '0', STR_PAD_LEFT);
    }
}