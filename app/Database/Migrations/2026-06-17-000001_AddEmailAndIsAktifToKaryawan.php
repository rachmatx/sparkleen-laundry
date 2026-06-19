<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailAndIsAktifToKaryawan extends Migration
{
    public function up()
    {
        // Add email + is_aktif to tb_karyawan
        $this->forge->addColumn('tb_karyawan', [
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'no_telp',
            ],
            'is_aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 1,
                'after'      => 'email',
            ],
        ]);

        // Add is_aktif to tb_pelanggan
        $this->forge->addColumn('tb_pelanggan', [
            'is_aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 1,
                'after'      => 'alamat',
            ],
        ]);

        // Add is_aktif to tb_layanan
        $this->forge->addColumn('tb_layanan', [
            'is_aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 1,
                'after'      => 'estimasi_hari',
            ],
        ]);

        // Add is_aktif to tb_inventaris
        $this->forge->addColumn('tb_inventaris', [
            'is_aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 1,
                'after'      => 'stok_minimal',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_karyawan', ['email', 'is_aktif']);
        $this->forge->dropColumn('tb_pelanggan', 'is_aktif');
        $this->forge->dropColumn('tb_layanan', 'is_aktif');
        $this->forge->dropColumn('tb_inventaris', 'is_aktif');
    }
}
