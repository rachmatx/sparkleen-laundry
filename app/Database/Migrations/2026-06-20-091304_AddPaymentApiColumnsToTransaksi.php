<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentApiColumnsToTransaksi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tb_transaksi', [
            'payment_ref' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'default' => null,
                'after' => 'grand_total'
            ],
            'payment_detail' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
                'after' => 'payment_ref'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_transaksi', ['payment_ref', 'payment_detail']);
    }
}
