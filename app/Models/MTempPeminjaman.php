<?php

namespace App\Models;

use CodeIgniter\Model;

class MTempPeminjaman extends Model
{
    protected $table = 'tb_temp_peminjaman';
    protected $primaryKey = 'id_temp';
    protected $allowedFields = ['id_buku', 'id_pelanggan', 'id_kasir', 'berat', 'id_layanan'];
}