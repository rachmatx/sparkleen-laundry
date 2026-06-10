<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterMetodePembayaranModel extends Model
{
    protected $table            = 'tb_master_metode_pembayaran';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['nama_metode'];

    // Dates
    protected $useTimestamps = false;
}
