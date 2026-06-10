<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterLogWhatsapp extends Migration
{
    public function up()
    {
        // 1. Tambah kolom id_status_whatsapp
        $this->forge->addColumn('tb_log_whatsapp', [
            'id_status_whatsapp' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'status'
            ]
        ]);

        // 2. Migrate data lama ke ID
        $this->db->query("UPDATE tb_log_whatsapp SET id_status_whatsapp = 1 WHERE status = 'pending'");
        $this->db->query("UPDATE tb_log_whatsapp SET id_status_whatsapp = 2 WHERE status = 'sukses'");
        $this->db->query("UPDATE tb_log_whatsapp SET id_status_whatsapp = 3 WHERE status = 'gagal'");

        // 3. Hapus kolom status lama
        $this->forge->dropColumn('tb_log_whatsapp', 'status');

        // 4. Tambah Foreign Key
        $this->forge->addForeignKey('id_status_whatsapp', 'tb_master_status_whatsapp', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('tb_log_whatsapp');
    }

    public function down()
    {
        $this->forge->dropForeignKey('tb_log_whatsapp', 'tb_log_whatsapp_id_status_whatsapp_foreign');

        $this->forge->addColumn('tb_log_whatsapp', [
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'sukses', 'gagal'],
                'default'    => 'pending',
                'after'      => 'id_status_whatsapp'
            ]
        ]);

        $this->db->query("UPDATE tb_log_whatsapp SET status = 'pending' WHERE id_status_whatsapp = 1");
        $this->db->query("UPDATE tb_log_whatsapp SET status = 'sukses' WHERE id_status_whatsapp = 2");
        $this->db->query("UPDATE tb_log_whatsapp SET status = 'gagal' WHERE id_status_whatsapp = 3");

        $this->forge->dropColumn('tb_log_whatsapp', 'id_status_whatsapp');
    }
}
