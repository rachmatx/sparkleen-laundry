<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePembayaranTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_pembayaran' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_transaksi' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'tgl_bayar' => [
                'type' => 'DATE',
            ],
            'nominal_bayar' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'sisa_tagihan' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'metode_bayar' => [
                'type' => 'ENUM',
                'constraint' => ['cash', 'qris', 'transfer'],
            ],
            'bukti_foto' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id_pembayaran', true);
        $this->forge->addForeignKey('id_transaksi', 'tb_transaksi', 'id_transaksi', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_pembayaran');
    }

    public function down()
    {
        $this->forge->dropTable('tb_pembayaran');
    }
}