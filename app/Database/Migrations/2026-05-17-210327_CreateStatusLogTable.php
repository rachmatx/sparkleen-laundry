<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStatusLogTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_log' => [
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
            'status_lama' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'status_baru' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'tgl_ubah' => [
                'type' => 'DATETIME',
            ],
            'id_karyawan' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id_log', true);
        $this->forge->addForeignKey('id_transaksi', 'tb_transaksi', 'id_transaksi', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_karyawan', 'tb_karyawan', 'id_karyawan', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_status_log');
    }

    public function down()
    {
        $this->forge->dropTable('tb_status_log');
    }
}