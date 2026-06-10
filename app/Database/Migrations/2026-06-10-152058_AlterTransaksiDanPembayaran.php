<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTransaksiDanPembayaran extends Migration
{
    public function up()
    {
        // ==========================================
        // 1. ALTER tb_transaksi
        // ==========================================
        $this->forge->addColumn('tb_transaksi', [
            'id_status_transaksi' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'id_status_pembayaran' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'id_metode_pembayaran' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);

        // Map status transaksi
        $this->db->query("UPDATE tb_transaksi SET id_status_transaksi = 1 WHERE status = 'menunggu'");
        $this->db->query("UPDATE tb_transaksi SET id_status_transaksi = 2 WHERE status = 'proses'");
        $this->db->query("UPDATE tb_transaksi SET id_status_transaksi = 3 WHERE status = 'setrika'");
        $this->db->query("UPDATE tb_transaksi SET id_status_transaksi = 4 WHERE status = 'selesai'");
        $this->db->query("UPDATE tb_transaksi SET id_status_transaksi = 5 WHERE status = 'diambil'");

        // Map status pembayaran
        $this->db->query("UPDATE tb_transaksi SET id_status_pembayaran = 1 WHERE status_pembayaran = 'lunas'");
        $this->db->query("UPDATE tb_transaksi SET id_status_pembayaran = 2 WHERE status_pembayaran = 'cicil'");
        $this->db->query("UPDATE tb_transaksi SET id_status_pembayaran = 3 WHERE status_pembayaran = 'belum'");

        // Map metode pembayaran
        $this->db->query("UPDATE tb_transaksi SET id_metode_pembayaran = 1 WHERE metode_pembayaran = 'cash'");
        $this->db->query("UPDATE tb_transaksi SET id_metode_pembayaran = 2 WHERE metode_pembayaran = 'qris'");
        $this->db->query("UPDATE tb_transaksi SET id_metode_pembayaran = 3 WHERE metode_pembayaran = 'transfer'");

        // Drop old columns
        $this->forge->dropColumn('tb_transaksi', 'status');
        $this->forge->dropColumn('tb_transaksi', 'status_pembayaran');
        $this->forge->dropColumn('tb_transaksi', 'metode_pembayaran');

        // Modify constraints to false where needed
        $this->forge->modifyColumn('tb_transaksi', [
            'id_status_transaksi' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'id_status_pembayaran' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
        ]);

        // Add Foreign Keys
        $this->forge->addForeignKey('id_status_transaksi', 'tb_master_status_transaksi', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('id_status_pembayaran', 'tb_master_status_pembayaran', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('id_metode_pembayaran', 'tb_master_metode_pembayaran', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('tb_transaksi');


        // ==========================================
        // 2. ALTER tb_pembayaran
        // ==========================================
        $this->forge->addColumn('tb_pembayaran', [
            'id_metode_pembayaran' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);

        // Map metode pembayaran
        $this->db->query("UPDATE tb_pembayaran SET id_metode_pembayaran = 1 WHERE metode_bayar = 'cash'");
        $this->db->query("UPDATE tb_pembayaran SET id_metode_pembayaran = 2 WHERE metode_bayar = 'qris'");
        $this->db->query("UPDATE tb_pembayaran SET id_metode_pembayaran = 3 WHERE metode_bayar = 'transfer'");

        $this->forge->dropColumn('tb_pembayaran', 'metode_bayar');

        $this->forge->modifyColumn('tb_pembayaran', [
            'id_metode_pembayaran' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
        ]);

        $this->forge->addForeignKey('id_metode_pembayaran', 'tb_master_metode_pembayaran', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('tb_pembayaran');
    }

    public function down()
    {
        // down not fully implemented for brevity in rollback
    }
}
