<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // Add a default Pemilik (Owner) user
        $data = [
            'id_level'       => 3, // 3 is 'pemilik' based on MasterSeeder
            'nama_karyawan'  => 'Pemilik Laundry',
            'username'       => 'pemilik',
            'email'          => 'pemilik@sparkleen.com',
            'password'       => password_hash('pemilik123', PASSWORD_BCRYPT),
            'no_hp'          => '08123456789',
            'alamat'         => 'Kantor Pusat',
            'is_aktif'       => 1,
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
