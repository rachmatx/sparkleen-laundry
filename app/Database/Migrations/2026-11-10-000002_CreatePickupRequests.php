<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePickupRequests extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_permintaan' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'id_pelanggan' => ['type' => 'INT', 'unsigned' => true],
            'active_customer_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'id_layanan' => ['type' => 'INT', 'unsigned' => true],
            'nama_layanan' => ['type' => 'VARCHAR', 'constraint' => 100],
            'berat_perkiraan' => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'estimasi_harga' => ['type' => 'BIGINT', 'unsigned' => true],
            'estimasi_hari' => ['type' => 'INT', 'unsigned' => true],
            'alamat_jemput' => ['type' => 'VARCHAR', 'constraint' => 500],
            'tgl_jemput' => ['type' => 'DATETIME'],
            'slot_jemput' => ['type' => 'VARCHAR', 'constraint' => 10],
            'catatan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'menunggu'],
            'alasan_penolakan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'id_transaksi' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'id_karyawan' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id_permintaan', true);
        $this->forge->addUniqueKey('active_customer_id');
        $this->forge->addUniqueKey('id_transaksi');
        $this->forge->addKey(['id_pelanggan', 'status']);
        $this->forge->addKey(['status', 'tgl_jemput']);
        foreach (['id_pelanggan' => 'tb_pelanggan', 'id_layanan' => 'tb_layanan', 'id_transaksi' => 'tb_transaksi', 'id_karyawan' => 'tb_karyawan'] as $key => $table) {
            $this->forge->addForeignKey($key, $table, $key, 'RESTRICT', 'RESTRICT');
        }
        $this->forge->createTable('tb_permintaan_jemput', false, ['ENGINE' => 'InnoDB']);
        $this->db->query("ALTER TABLE tb_permintaan_jemput ADD CONSTRAINT pickup_active_customer CHECK ((status IN ('menunggu','diproses') AND active_customer_id IS NOT NULL AND active_customer_id = id_pelanggan) OR (status IN ('dikonfirmasi','ditolak','dibatalkan') AND active_customer_id IS NULL))");
        $this->db->resetDataCache();
    }

    public function down()
    {
        $this->forge->dropTable('tb_permintaan_jemput', true);
        $this->db->resetDataCache();
    }
}
