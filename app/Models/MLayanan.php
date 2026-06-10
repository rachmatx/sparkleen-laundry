<?php

namespace App\Models;

use CodeIgniter\Model;

class MLayanan extends Model
{
    protected $table = 'tb_layanan';
    protected $primaryKey = 'id_layanan';
    protected $allowedFields = [
        'nama_layanan', 'harga_per_kg', 'estimasi_hari', 'is_aktif'
    ];

    protected $validationRules = [
        'nama_layanan'  => 'required|max_length[100]',
        'harga_per_kg'  => 'required|numeric|greater_than[0]',
        'estimasi_hari' => 'required|integer|greater_than[0]',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}