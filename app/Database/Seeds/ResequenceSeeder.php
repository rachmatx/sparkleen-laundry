<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ResequenceSeeder extends Seeder
{
    public function run()
    {
        $pelanggan = $this->db->table('tb_pelanggan')->orderBy('id_pelanggan', 'ASC')->get()->getResult();

        $counter = 1;
        foreach ($pelanggan as $p) {
            $kode = 'PLG' . str_pad($counter, 4, '0', STR_PAD_LEFT);
            $this->db->table('tb_pelanggan')
               ->where('id_pelanggan', $p->id_pelanggan)
               ->update(['kode_pelanggan' => $kode]);
            $counter++;
        }
        
        echo "Resequenced " . count($pelanggan) . " pelanggan.\n";
    }
}
