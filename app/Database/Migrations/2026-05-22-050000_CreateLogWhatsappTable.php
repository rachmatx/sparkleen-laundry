<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLogWhatsappTable extends Migration
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
            'no_nota' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'no_tujuan' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'isi_pesan' => [
                'type' => 'TEXT',
            ],
            'tipe_notifikasi' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['sukses', 'gagal', 'pending'],
            ],
            'respon_api' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id_log', true);
        $this->forge->addKey('no_nota');
        $this->forge->addKey('no_tujuan');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->createTable('tb_log_whatsapp');
    }

    public function down()
    {
        $this->forge->dropTable('tb_log_whatsapp');
    }
}
