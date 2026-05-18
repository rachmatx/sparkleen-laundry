<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTempPeminjamanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_temp' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_pelanggan' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'id_kasir' => [
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
        ]);
        $this->forge->addKey('id_temp', true);
        $this->forge->createTable('tb_temp_peminjaman');
    }

    public function down()
    {
        $this->forge->dropTable('tb_temp_peminjaman');
    }
}