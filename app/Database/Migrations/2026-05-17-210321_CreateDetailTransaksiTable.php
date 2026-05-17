<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDetailTransaksiTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_detail' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_transaksi' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'id_layanan' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'berat' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'harga_satuan' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'subtotal' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
        ]);
        $this->forge->addKey('id_detail', true);
        $this->forge->addForeignKey('id_transaksi', 'tb_transaksi', 'id_transaksi', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_layanan', 'tb_layanan', 'id_layanan', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_detail_transaksi');
    }

    public function down()
    {
        $this->forge->dropTable('tb_detail_transaksi');
    }
}