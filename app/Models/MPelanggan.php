<?php
namespace App\Models;
use CodeIgniter\Model;

class MPelanggan extends Model
{
    protected $table            = 'tb_pelanggan';
    protected $primaryKey       = 'id_pelanggan';
    protected $allowedFields    = ['kode_pelanggan', 'nama_pelanggan', 'no_telp', 'alamat', 'poin', 'total_transaksi'];
    protected $useTimestamps    = true;

    public function generateKode()
    {
        $lastData = $this->orderBy('id_pelanggan', 'DESC')->first();
        if (!$lastData) return 'PLG0001';
        $lastNumber = (int) substr($lastData['kode_pelanggan'], 3);
        return 'PLG' . sprintf('%04d', $lastNumber + 1);
    }
}