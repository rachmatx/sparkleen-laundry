<?php

namespace App\Models;

use CodeIgniter\Model;

class MPembayaran extends Model
{
    protected $table = 'tb_pembayaran';
    protected $primaryKey = 'id_pembayaran';
    protected $allowedFields = [
        'id_transaksi', 'tgl_bayar', 'nominal_bayar', 
        'sisa_tagihan', 'metode_bayar', 'bukti_foto'
    ];
    protected $useTimestamps = false;
}