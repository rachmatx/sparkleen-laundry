<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterInventarisDecimal extends Migration
{
    public function up()
    {
        // Ubah tb_inventaris
        $fieldsInv = [
            'stok' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'stok_minimal' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
        ];
        $this->forge->modifyColumn('tb_inventaris', $fieldsInv);

        // Ubah tb_penggunaan_inventaris
        $fieldsPenggunaan = [
            'jumlah_pakai' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
        ];
        $this->forge->modifyColumn('tb_penggunaan_inventaris', $fieldsPenggunaan);
    }

    public function down()
    {
        // Kembalikan ke INT
        $fieldsInv = [
            'stok' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'stok_minimal' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 10,
            ],
        ];
        $this->forge->modifyColumn('tb_inventaris', $fieldsInv);

        $fieldsPenggunaan = [
            'jumlah_pakai' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
        ];
        $this->forge->modifyColumn('tb_penggunaan_inventaris', $fieldsPenggunaan);
    }
}
