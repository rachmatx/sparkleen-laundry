<?php

namespace App\Models;

use CodeIgniter\Model;

class MKaryawan extends Model
{
    protected $table            = 'tb_karyawan';
    protected $primaryKey       = 'id_karyawan';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama_karyawan', 
        'username', 
        'password', 
        'level', 
        'no_telp', 
        'gaji_pokok'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}