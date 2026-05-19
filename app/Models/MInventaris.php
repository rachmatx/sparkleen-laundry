<?php

namespace App\Models;

use CodeIgniter\Model;

class MInventaris extends Model
{
    protected $table         = 'tb_inventaris';
    protected $primaryKey    = 'id_inventaris';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'nama_barang',
        'stok',
        'satuan',
        'harga_beli',
        'stok_minimal',
        'is_aktif',
    ];

    /**
     * Ambil semua inventaris, tandai stok kritis via PHP
     */
    public function getSemuaInventaris(): array
    {
        $data = $this->orderBy('nama_barang', 'ASC')->findAll();

        // Tambahkan flag stok_kritis di PHP (menghindari masalah kompatibilitas SQL)
        foreach ($data as &$row) {
            $row['stok_kritis'] = ($row['stok'] <= $row['stok_minimal']) ? 1 : 0;
        }

        return $data;
    }

    /**
     * Ambil inventaris dengan stok di bawah/sama minimal
     */
    public function getStokKritis(): array
    {
        $semua = $this->orderBy('stok', 'ASC')->findAll();

        return array_filter($semua, function ($row) {
            return $row['stok'] <= $row['stok_minimal'];
        });
    }

    /**
     * Kurangi stok, pastikan tidak minus
     */
    public function kurangiStok(int $id, float $jumlah): bool
    {
        $item = $this->find($id);
        if (!$item) {
            return false;
        }

        $stokBaru = max(0, $item['stok'] - $jumlah);
        return (bool) $this->update($id, ['stok' => $stokBaru]);
    }

    /**
     * Tambah stok
     */
    public function tambahStok(int $id, float $jumlah): bool
    {
        $item = $this->find($id);
        if (!$item) {
            return false;
        }

        return (bool) $this->update($id, ['stok' => $item['stok'] + $jumlah]);
    }
}
