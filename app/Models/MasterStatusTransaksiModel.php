<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterStatusTransaksiModel extends Model
{
    protected $table            = 'tb_master_status_transaksi';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['nama_status'];

    // Dates
    protected $useTimestamps = false;
}
