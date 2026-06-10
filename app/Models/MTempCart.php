<?php

namespace App\Models;

use CodeIgniter\Model;

class MTempCart extends Model
{
    protected $table = 'tb_temp_keranjang';
    protected $primaryKey = 'id_temp';
    protected $allowedFields = ['id_pelanggan', 'id_kasir', 'berat', 'id_layanan'];
}
