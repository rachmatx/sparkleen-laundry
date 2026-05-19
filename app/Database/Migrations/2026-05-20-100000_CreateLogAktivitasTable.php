<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLogAktivitasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_log' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_karyawan' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nama_karyawan' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'level_karyawan' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'kasir', 'pemilik'],
            ],
            'modul' => [
                'type'       => 'ENUM',
                'constraint' => ['karyawan', 'inventaris'],
            ],
            'aksi' => [
                'type'       => 'ENUM',
                'constraint' => ['tambah', 'ubah', 'hapus', 'aktifkan', 'nonaktifkan'],
            ],
            'id_referensi' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'nama_referensi' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'detail' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id_log', true);
        $this->forge->addKey('modul');
        $this->forge->addKey('created_at');
        $this->forge->addKey('id_karyawan');
        $this->forge->createTable('tb_log_aktivitas');
    }

    public function down()
    {
        $this->forge->dropTable('tb_log_aktivitas');
    }
}
