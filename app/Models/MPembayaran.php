<?php

namespace App\Models;

use CodeIgniter\Model;

class MPembayaran extends Model
{
    protected $table = 'tb_pembayaran';
    protected $primaryKey = 'id_pembayaran';
    protected $allowedFields = [
        'id_transaksi', 'tgl_bayar', 'nominal_bayar', 
        'sisa_tagihan', 'id_metode_pembayaran', 'bukti_foto'
    ];
    protected $useTimestamps = false;

    public function getPembayaranByTransaksi($id_transaksi)
    {
        return $this->select('tb_pembayaran.*, mm.nama_metode as metode_bayar')
                    ->join('tb_master_metode_pembayaran mm', 'mm.id = tb_pembayaran.id_metode_pembayaran', 'left')
                    ->where('id_transaksi', $id_transaksi)
                    ->orderBy('tgl_bayar', 'DESC')
                    ->findAll();
    }
}