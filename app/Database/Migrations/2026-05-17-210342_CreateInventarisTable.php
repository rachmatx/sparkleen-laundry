<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInventarisTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_inventaris' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nama_barang' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'stok' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'satuan' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'harga_beli' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'stok_minimal' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 10,
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
        $this->forge->addKey('id_inventaris', true);
        $this->forge->createTable('tb_inventaris');
    }

    public function down()
    {
        $this->forge->dropTable('tb_inventaris');
    }
}