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
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}