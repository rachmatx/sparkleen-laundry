<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransaksiTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_transaksi' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'no_nota' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
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
            'tgl_masuk' => [
                'type' => 'DATE',
            ],
            'tgl_selesai_estimasi' => [
                'type' => 'DATE',
            ],
            'tgl_selesai_aktual' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'total_berat' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'total_biaya' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'diskon' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'grand_total' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['menunggu', 'proses', 'setrika', 'selesai', 'diambil'],
                'default' => 'menunggu',
            ],
            'status_pembayaran' => [
                'type' => 'ENUM',
                'constraint' => ['lunas', 'cicil', 'belum'],
                'default' => 'belum',
            ],
            'metode_pembayaran' => [
                'type' => 'ENUM',
                'constraint' => ['cash', 'qris', 'transfer'],
                'null' => true,
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
        $this->forge->addKey('id_transaksi', true);
        $this->forge->addForeignKey('id_pelanggan', 'tb_pelanggan', 'id_pelanggan', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_kasir', 'tb_karyawan', 'id_karyawan', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_transaksi');
    }

    public function down()
    {
        $this->forge->dropTable('tb_transaksi');
    }
}