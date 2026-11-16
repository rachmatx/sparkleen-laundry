<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
class CreateCustomerAddresses extends Migration
{
    public function up()
    {
        $this->forge->addField(['id_alamat' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true], 'id_pelanggan' => ['type' => 'INT', 'unsigned' => true], 'label' => ['type' => 'VARCHAR', 'constraint' => 40], 'alamat' => ['type' => 'VARCHAR', 'constraint' => 1000], 'patokan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], 'utama_pelanggan_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true], 'created_at' => ['type' => 'DATETIME'], 'updated_at' => ['type' => 'DATETIME']]);
        $this->forge->addKey('id_alamat', true);
        $this->forge->addKey(['id_pelanggan', 'id_alamat']);
        $this->forge->addUniqueKey('utama_pelanggan_id');
        $this->forge->addForeignKey('id_pelanggan', 'tb_pelanggan', 'id_pelanggan', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('tb_alamat_pelanggan', false, ['ENGINE' => 'InnoDB']);
        $this->db->query('ALTER TABLE tb_alamat_pelanggan ADD CONSTRAINT address_default_owner CHECK (utama_pelanggan_id IS NULL OR utama_pelanggan_id=id_pelanggan)');
        $this->db->query("INSERT INTO tb_alamat_pelanggan (id_pelanggan,label,alamat,utama_pelanggan_id,created_at,updated_at) SELECT id_pelanggan,'Alamat utama',alamat,id_pelanggan,NOW(),NOW() FROM tb_pelanggan WHERE TRIM(COALESCE(alamat,''))<>''");
        $this->forge->addColumn('tb_permintaan_jemput', ['patokan_jemput' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]]);
        $this->forge->modifyColumn('tb_permintaan_jemput', ['alamat_jemput' => ['type' => 'VARCHAR', 'constraint' => 1000]]);
        $this->db->resetDataCache();
    }
    public function down()
    {
        $this->forge->dropColumn('tb_permintaan_jemput', 'patokan_jemput');
        // Do not narrow snapshots on rollback: existing pickup text must survive.
        $this->forge->dropTable('tb_alamat_pelanggan', true);
        $this->db->resetDataCache();
    }
}
