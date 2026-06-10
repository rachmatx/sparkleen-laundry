<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterLevelKaryawanModel extends Model
{
    protected $table            = 'tb_master_level_karyawan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['nama_level'];

    // Dates
    protected $useTimestamps = false;
}
