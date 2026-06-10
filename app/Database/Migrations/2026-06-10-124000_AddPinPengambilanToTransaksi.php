<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPinPengambilanToTransaksi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tb_transaksi', [
            'pin_pengambilan' => [
                'type'       => 'VARCHAR',
                'constraint' => 4,
                'null'       => true,
                'after'      => 'no_nota',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_transaksi', 'pin_pengambilan');
    }
}
