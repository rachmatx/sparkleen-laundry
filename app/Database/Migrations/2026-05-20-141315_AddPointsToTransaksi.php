<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPointsToTransaksi extends Migration
{
    public function up()
    {
        $fields = [
            'poin_ditebus' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'after'      => 'diskon',
            ],
            'poin_diperoleh' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'after'      => 'poin_ditebus',
            ],
        ];
        $this->forge->addColumn('tb_transaksi', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_transaksi', 'poin_ditebus');
        $this->forge->dropColumn('tb_transaksi', 'poin_diperoleh');
    }
}
