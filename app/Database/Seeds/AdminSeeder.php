<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // Get the actual id_level for 'pemilik' dynamically to avoid auto-increment shift issues
        $pemilikLevel = $this->db->table('tb_master_level_karyawan')
                                 ->where('nama_level', 'pemilik')
                                 ->get()
                                 ->getRowArray();
        $id_level = $pemilikLevel ? $pemilikLevel['id'] : 3;

        // Add a default Pemilik (Owner) user
        $data = [
            'id_level'       => $id_level,
            'nama_karyawan'  => 'Pemilik Laundry',
            'username'       => 'pemilik',
            'email'          => 'pemilik@sparkleen.com',
            'password'       => password_hash('pemilik123', PASSWORD_BCRYPT),
            'no_telp'        => '08123456789',
            'is_aktif'       => 1,
            'gaji_pokok'     => 0,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];
        
        // Insert only if 'pemilik' doesn't exist
        $exists = $this->db->table('tb_karyawan')->where('username', 'pemilik')->countAllResults();
        if ($exists == 0) {
            $this->db->table('tb_karyawan')->insert($data);
            echo "Pemilik user seeded successfully." . PHP_EOL;
        } else {
            echo "Pemilik user already exists. Skipping." . PHP_EOL;
        }
    }
}
