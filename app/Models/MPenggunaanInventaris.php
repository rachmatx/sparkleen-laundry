<?php

namespace App\Models;

use CodeIgniter\Model;

class MPenggunaanInventaris extends Model
{
    protected $table      = 'tb_penggunaan_inventaris';
    protected $primaryKey = 'id_penggunaan';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_transaksi',
        'id_inventaris',
        'jumlah_pakai',
        'keterangan',
    ];

    /**
     * Ambil penggunaan inventaris berdasarkan id transaksi
     */
    public function getByTransaksi(int $id_transaksi): array
    {
        return $this->select('tb_penggunaan_inventaris.*, tb_inventaris.nama_barang, tb_inventaris.satuan')
                    ->join('tb_inventaris', 'tb_inventaris.id_inventaris = tb_penggunaan_inventaris.id_inventaris')
                    ->where('id_transaksi', $id_transaksi)
                    ->findAll();
    }
}
