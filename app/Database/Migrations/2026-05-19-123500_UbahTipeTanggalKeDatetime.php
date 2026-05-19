<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UbahTipeTanggalKeDatetime extends Migration
{
    public function up()
    {
        // Alter tabel tb_transaksi
        $fieldsTransaksi = [
            'tgl_masuk' => [
                'type' => 'DATETIME',
            ],
            'tgl_selesai_estimasi' => [
                'type' => 'DATETIME',
            ],
            'tgl_selesai_aktual' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];
        $this->forge->modifyColumn('tb_transaksi', $fieldsTransaksi);

        // Alter tabel tb_pembayaran
        $fieldsPembayaran = [
            'tgl_bayar' => [
                'type' => 'DATETIME',
            ],
        ];
        $this->forge->modifyColumn('tb_pembayaran', $fieldsPembayaran);
    }

    public function down()
    {
        // Revert ke DATE jika di-rollback
        $fieldsTransaksi = [
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
        ];
        $this->forge->modifyColumn('tb_transaksi', $fieldsTransaksi);

        $fieldsPembayaran = [
            'tgl_bayar' => [
                'type' => 'DATE',
            ],
        ];
        $this->forge->modifyColumn('tb_pembayaran', $fieldsPembayaran);
    }
}
