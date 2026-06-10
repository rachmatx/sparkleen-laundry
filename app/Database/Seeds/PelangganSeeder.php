<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Faker\Factory;

class PelangganSeeder extends Seeder
{
    public function run()
    {
        $faker = Factory::create('id_ID');

        // Cek kode pelanggan terakhir untuk melanjutkan
        $builder = $this->db->table('tb_pelanggan');
        $builder->select('kode_pelanggan');
        $builder->orderBy('LENGTH(kode_pelanggan)', 'DESC');
        $builder->orderBy('kode_pelanggan', 'DESC');
        $builder->limit(1);
        $row = $builder->get()->getRow();

        if ($row && $row->kode_pelanggan) {
            $number = (int) preg_replace('/[^0-9]/', '', $row->kode_pelanggan);
            $nextNumber = $number + 1;
        } else {
            $nextNumber = 1;
        }

        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $kode = 'PLG' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $data[] = [
                'kode_pelanggan'  => $kode,
                'nama_pelanggan'  => $faker->name,
                'no_telp'         => '08' . $faker->randomNumber(9, true),
                'alamat'          => $faker->address,
                'poin'            => $faker->numberBetween(0, 50),
                'total_transaksi' => $faker->numberBetween(50000, 1000000),
                'is_aktif'        => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ];
            $nextNumber++;
        }

        $this->db->table('tb_pelanggan')->insertBatch($data);
    }
}
