<?php

namespace App\Models;

use CodeIgniter\Model;

class MStatusLog extends Model
{
    protected $table            = 'tb_status_log';
    protected $primaryKey       = 'id_log';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_transaksi',
        'id_karyawan',
        'status_lama',
        'status_baru',
        'keterangan',
        'tgl_ubah'
    ];

    // Dates
    protected $useTimestamps = false;
}
