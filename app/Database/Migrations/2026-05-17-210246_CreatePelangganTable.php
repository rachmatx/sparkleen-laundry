<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePelangganTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_pelanggan' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'kode_pelanggan' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'nama_pelanggan' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'no_telp' => [
                'type' => 'VARCHAR',
                'constraint' => 15,
            ],
            'alamat' => [
                'type' => 'TEXT',
            ],
            'poin' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'total_transaksi' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
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
        $this->forge->addKey('id_pelanggan', true);
        $this->forge->createTable('tb_pelanggan');
    }

    public function down()
    {
        $this->forge->dropTable('tb_pelanggan');
    }
}