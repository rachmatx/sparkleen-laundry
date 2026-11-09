<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentIntents extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_intent' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'gateway_ref' => ['type' => 'VARCHAR', 'constraint' => 100],
            'merchant_id' => ['type' => 'VARCHAR', 'constraint' => 100],
            'customer_ref' => ['type' => 'VARCHAR', 'constraint' => 100],
            'id_transaksi' => ['type' => 'INT', 'unsigned' => true],
            'amount' => ['type' => 'BIGINT', 'unsigned' => true],
            'method' => ['type' => 'VARCHAR', 'constraint' => 20],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PENDING'],
            'id_pembayaran' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'applied_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_intent', true);
        $this->forge->addUniqueKey('gateway_ref');
        $this->forge->addUniqueKey('id_pembayaran');
        $this->forge->addKey('id_transaksi');
        $this->forge->addForeignKey('id_transaksi', 'tb_transaksi', 'id_transaksi', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('id_pembayaran', 'tb_pembayaran', 'id_pembayaran', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('tb_payment_intent');
        $this->db->resetDataCache();
    }

    public function down()
    {
        $this->forge->dropTable('tb_payment_intent', true);
        $this->db->resetDataCache();
    }
}
