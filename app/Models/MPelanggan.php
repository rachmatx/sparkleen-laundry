<?php

namespace App\Models;

use CodeIgniter\Model;

class MPelanggan extends Model
{
    protected $table      = 'tb_pelanggan';
    protected $primaryKey = 'id_pelanggan';

    protected $allowedFields = [
        'kode_pelanggan', 'nama_pelanggan', 'no_telp',
        'alamat', 'poin', 'total_transaksi', 'is_aktif'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validasi bawaan model
    protected $validationRules = [
        'id_pelanggan'   => 'permit_empty',
        'nama_pelanggan' => 'required|max_length[100]',
        'no_telp'        => 'required|max_length[20]|is_unique[tb_pelanggan.no_telp,id_pelanggan,{id_pelanggan}]',
        'alamat'         => 'required',
    ];

    protected $validationMessages = [
        'no_telp' => [
            'is_unique' => 'Nomor telepon ini sudah terdaftar pada pelanggan lain.',
        ],
    ];

    // Aktifkan validasi saat insert & update
    protected $skipValidation = false;

    // Auto-generate kode pelanggan
    public function generateKode(): string
    {
        $builder = $this->db->table($this->table);
        $builder->selectMax('id_pelanggan');
        $row = $builder->get()->getRow();
        $id  = ($row->id_pelanggan ?? 0) + 1;
        return 'PLG' . str_pad($id, 4, '0', STR_PAD_LEFT);
    }
}