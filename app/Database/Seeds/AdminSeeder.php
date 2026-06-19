<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // Add a default Admin user
        $data = [
            'id_level'       => 1, // Assuming 1 is 'admin' based on MasterSeeder
            'nama_karyawan'  => 'Administrator',
            'username'       => 'admin',
            'email'          => 'admin@sparkleen.com',
            'password'       => password_hash('admin123', PASSWORD_BCRYPT),
            'no_hp'          => '08123456789',
            'alamat'         => 'Kantor Pusat',
            'is_aktif'       => 1,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];
        
        // Insert only if 'admin' doesn't exist
        $exists = $this->db->table('tb_karyawan')->where('username', 'admin')->countAllResults();
        if ($exists == 0) {
            $this->db->table('tb_karyawan')->insert($data);
            echo "Admin user seeded successfully." . PHP_EOL;
        } else {
            echo "Admin user already exists. Skipping." . PHP_EOL;
        }
    }
}
