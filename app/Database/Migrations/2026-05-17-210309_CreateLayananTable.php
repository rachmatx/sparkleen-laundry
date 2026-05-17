<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLayananTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_layanan' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nama_layanan' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'harga_per_kg' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'estimasi_hari' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 2,
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
        $this->forge->addKey('id_layanan', true);
        $this->forge->createTable('tb_layanan');
    }

    public function down()
    {
        $this->forge->dropTable('tb_layanan');
    }
}