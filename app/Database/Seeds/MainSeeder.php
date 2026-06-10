<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MainSeeder extends Seeder
{
    public function run()
    {
        // 1. Bersihkan data dummy pelanggan (yang kodenya bukan PLG...)
        $this->db->table('tb_pelanggan')
            ->notLike('kode_pelanggan', 'PLG', 'after')
            ->delete();

        // 2. Isi data default Layanan (jika masih kosong)
        if ($this->db->table('tb_layanan')->countAllResults() == 0) {
            $dataLayanan = [
                [
                    'nama_layanan'  => 'Cuci Komplit (Cuci + Setrika)',
                    'harga_per_kg'  => 6000,
                    'estimasi_hari' => 2,
                    'is_aktif'      => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                ],
                [
                    'nama_layanan'  => 'Cuci Kering Saja',
                    'harga_per_kg'  => 4500,
                    'estimasi_hari' => 1,
                    'is_aktif'      => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                ],
                [
                    'nama_layanan'  => 'Setrika Saja',
                    'harga_per_kg'  => 4000,
                    'estimasi_hari' => 1,
                    'is_aktif'      => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                ],
                [
                    'nama_layanan'  => 'Express (1 Hari Selesai)',
                    'harga_per_kg'  => 10000,
                    'estimasi_hari' => 1,
                    'is_aktif'      => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                ]
            ];
            $this->db->table('tb_layanan')->insertBatch($dataLayanan);
        }

        // 3. Isi data default Inventaris (jika masih kosong)
        if ($this->db->table('tb_inventaris')->countAllResults() == 0) {
            $dataInventaris = [
                [
                    'nama_barang'  => 'Deterjen Cair',
                    'stok'         => 50,
                    'satuan'       => 'Liter',
                    'harga_beli'   => 15000,
                    'stok_minimal' => 5,
                    'is_aktif'     => 1,
                    'created_at'   => date('Y-m-d H:i:s'),
                ],
                [
                    'nama_barang'  => 'Pewangi Pakaian',
                    'stok'         => 30,
                    'satuan'       => 'Liter',
                    'harga_beli'   => 20000,
                    'stok_minimal' => 5,
                    'is_aktif'     => 1,
                    'created_at'   => date('Y-m-d H:i:s'),
                ],
                [
                    'nama_barang'  => 'Plastik Laundry Besar',
                    'stok'         => 100,
                    'satuan'       => 'Pcs',
                    'harga_beli'   => 1000,
                    'stok_minimal' => 20,
                    'is_aktif'     => 1,
                    'created_at'   => date('Y-m-d H:i:s'),
                ]
            ];
            $this->db->table('tb_inventaris')->insertBatch($dataInventaris);
        }

        // 4. Panggil seeder lain untuk Karyawan & 20 Dummy Pelanggan
        $this->call('KaryawanSeeder');
        $this->call('PelangganSeeder');
    }
}
