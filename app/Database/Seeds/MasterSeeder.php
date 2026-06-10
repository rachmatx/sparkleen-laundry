<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MasterSeeder extends Seeder
{
    public function run()
    {
        // 1. tb_master_status_transaksi
        $dataStatusTransaksi = [
            ['nama_status' => 'menunggu'],
            ['nama_status' => 'proses'],
            ['nama_status' => 'setrika'],
            ['nama_status' => 'selesai'],
            ['nama_status' => 'diambil'],
        ];
        $this->db->table('tb_master_status_transaksi')->insertBatch($dataStatusTransaksi);

        // 2. tb_master_status_pembayaran
        $dataStatusPembayaran = [
            ['nama_status' => 'lunas'],
            ['nama_status' => 'cicil'],
            ['nama_status' => 'belum'],
        ];
        $this->db->table('tb_master_status_pembayaran')->insertBatch($dataStatusPembayaran);

        // 3. tb_master_metode_pembayaran
        $dataMetodePembayaran = [
            ['nama_metode' => 'cash'],
            ['nama_metode' => 'qris'],
            ['nama_metode' => 'transfer'],
        ];
        $this->db->table('tb_master_metode_pembayaran')->insertBatch($dataMetodePembayaran);

        // 4. tb_master_level_karyawan
        $dataLevelKaryawan = [
            ['nama_level' => 'admin'],
            ['nama_level' => 'kasir'],
            ['nama_level' => 'pemilik'],
        ];
        $this->db->table('tb_master_level_karyawan')->insertBatch($dataLevelKaryawan);

        // 5. tb_master_status_whatsapp
        $dataStatusWhatsapp = [
            ['nama_status' => 'sukses'],
            ['nama_status' => 'gagal'],
            ['nama_status' => 'pending'],
        ];
        $this->db->table('tb_master_status_whatsapp')->insertBatch($dataStatusWhatsapp);
    }
}
