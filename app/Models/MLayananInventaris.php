<?php

namespace App\Models;

use CodeIgniter\Model;

class MLayananInventaris extends Model
{
    protected $table = 'tb_layanan_inventaris';
    protected $primaryKey = 'id_layanan_inventaris';
    protected $allowedFields = [
        'id_layanan', 'id_inventaris', 'jumlah_default'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Mendapatkan resep berdasarkan layanan beserta detail barang inventaris
    public function getResepByLayanan($id_layanan)
    {
        return $this->select('tb_layanan_inventaris.*, tb_inventaris.nama_barang, tb_inventaris.satuan, tb_inventaris.stok')
            ->join('tb_inventaris', 'tb_inventaris.id_inventaris = tb_layanan_inventaris.id_inventaris')
            ->where('tb_layanan_inventaris.id_layanan', $id_layanan)
            ->findAll();
    }
}
