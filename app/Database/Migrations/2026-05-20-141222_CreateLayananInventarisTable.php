<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLayananInventarisTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_layanan_inventaris' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_layanan' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'id_inventaris' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'jumlah_default' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,4',
                'default'    => 0.0000,
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
        $this->forge->addKey('id_layanan_inventaris', true);
        $this->forge->addForeignKey('id_layanan', 'tb_layanan', 'id_layanan', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_inventaris', 'tb_inventaris', 'id_inventaris', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_layanan_inventaris');
    }

    public function down()
    {
        $this->forge->dropTable('tb_layanan_inventaris');
    }
}
