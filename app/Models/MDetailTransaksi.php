<?php

namespace App\Models;

use CodeIgniter\Model;

class MDetailTransaksi extends Model
{
    protected $table = 'tb_detail_transaksi';
    protected $primaryKey = 'id_detail';
    protected $allowedFields = ['id_transaksi', 'id_layanan', 'berat', 'harga_satuan', 'subtotal'];
}