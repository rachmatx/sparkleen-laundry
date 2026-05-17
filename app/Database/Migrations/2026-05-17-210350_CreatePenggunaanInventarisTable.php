<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePenggunaanInventarisTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_penggunaan' => [
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
            'id_inventaris' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'jumlah_pakai' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'keterangan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id_penggunaan', true);
        $this->forge->addForeignKey('id_transaksi', 'tb_transaksi', 'id_transaksi', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_inventaris', 'tb_inventaris', 'id_inventaris', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_penggunaan_inventaris');
    }

    public function down()
    {
        $this->forge->dropTable('tb_penggunaan_inventaris');
    }
}