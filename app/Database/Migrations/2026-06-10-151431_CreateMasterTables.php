<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMasterTables extends Migration
{
    public function up()
    {
        // 1. tb_master_status_transaksi
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_status' => ['type' => 'VARCHAR', 'constraint' => 50],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tb_master_status_transaksi');

        // 2. tb_master_status_pembayaran
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_status' => ['type' => 'VARCHAR', 'constraint' => 50],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tb_master_status_pembayaran');

        // 3. tb_master_metode_pembayaran
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_metode' => ['type' => 'VARCHAR', 'constraint' => 50],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tb_master_metode_pembayaran');

        // 4. tb_master_level_karyawan
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_level'  => ['type' => 'VARCHAR', 'constraint' => 50],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tb_master_level_karyawan');

        // 5. tb_master_status_whatsapp
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_status' => ['type' => 'VARCHAR', 'constraint' => 50],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tb_master_status_whatsapp');
    }

    public function down()
    {
        $this->forge->dropTable('tb_master_status_transaksi');
        $this->forge->dropTable('tb_master_status_pembayaran');
        $this->forge->dropTable('tb_master_metode_pembayaran');
        $this->forge->dropTable('tb_master_level_karyawan');
        $this->forge->dropTable('tb_master_status_whatsapp');
    }
}
