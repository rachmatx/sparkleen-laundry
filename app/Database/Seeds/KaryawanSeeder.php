<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KaryawanSeeder extends Seeder
{
    public function run()
    {
        if ($this->db->table('tb_karyawan')->countAllResults() > 0) {
            return; // Jangan jalankan jika sudah ada datanya
        }

        $data = [
            [
                'nama_karyawan' => 'Owner / Pemilik',
                'username'      => 'pemilik',
                'password'      => password_hash('password123', PASSWORD_DEFAULT),
                'level'         => 'pemilik',
                'no_telp'       => '08111111111',
                'email'         => 'owner@sparkleen.com',
                'gaji_pokok'    => 0,
                'is_aktif'      => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'nama_karyawan' => 'Admin Sistem',
                'username'      => 'admin',
                'password'      => password_hash('admin123', PASSWORD_DEFAULT),
                'level'         => 'admin',
                'no_telp'       => '08222222222',
                'email'         => 'admin@sparkleen.com',
                'gaji_pokok'    => 3000000,
                'is_aktif'      => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'nama_karyawan' => 'Siti Kasir',
                'username'      => 'kasir1',
                'password'      => password_hash('kasir123', PASSWORD_DEFAULT),
                'level'         => 'kasir',
                'no_telp'       => '08333333333',
                'email'         => 'kasir1@sparkleen.com',
                'gaji_pokok'    => 2000000,
                'is_aktif'      => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('tb_karyawan')->insertBatch($data);
    }
}
