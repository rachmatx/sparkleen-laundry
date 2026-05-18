<?php

namespace App\Models;

use CodeIgniter\Model;

class MPelanggan extends Model
{
    protected $table = 'tb_pelanggan';
    protected $primaryKey = 'id_pelanggan';
    protected $allowedFields = [
        'kode_pelanggan', 'nama_pelanggan', 'no_telp', 
        'alamat', 'poin', 'total_transaksi'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Auto-generate kode pelanggan
    public function generateKode()
    {
        $builder = $this->db->table($this->table);
        $builder->selectMax('id_pelanggan');
        $query = $builder->get();
        $row = $query->getRow();
        $id = $row->id_pelanggan + 1;
        return 'PLG' . str_pad($id, 4, '0', STR_PAD_LEFT);
    }
}