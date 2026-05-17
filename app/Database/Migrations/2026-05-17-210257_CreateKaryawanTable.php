<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKaryawanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_karyawan' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nama_karyawan' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'unique' => true,
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'level' => [
                'type' => 'ENUM',
                'constraint' => ['admin', 'kasir', 'pemilik'],
                'default' => 'kasir',
            ],
            'no_telp' => [
                'type' => 'VARCHAR',
                'constraint' => 15,
            ],
            'gaji_pokok' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id_karyawan', true);
        $this->forge->createTable('tb_karyawan');
    }

    public function down()
    {
        $this->forge->dropTable('tb_karyawan');
    }
}