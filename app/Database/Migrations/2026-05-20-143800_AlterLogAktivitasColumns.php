<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterLogAktivitasColumns extends Migration
{
    public function up()
    {
        $fields = [
            'id_karyawan' => [
                'name'       => 'id_karyawan',
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'level_karyawan' => [
                'name'       => 'level_karyawan',
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'modul' => [
                'name'       => 'modul',
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'aksi' => [
                'name'       => 'aksi',
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
        ];

        $this->forge->modifyColumn('tb_log_aktivitas', $fields);
    }

    public function down()
    {
        $fields = [
            'id_karyawan' => [
                'name'       => 'id_karyawan',
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'level_karyawan' => [
                'name'       => 'level_karyawan',
                'type'       => 'ENUM',
                'constraint' => ['admin', 'kasir', 'pemilik'],
                'null'       => false,
            ],
            'modul' => [
                'name'       => 'modul',
                'type'       => 'ENUM',
                'constraint' => ['karyawan', 'inventaris'],
                'null'       => false,
            ],
            'aksi' => [
                'name'       => 'aksi',
                'type'       => 'ENUM',
                'constraint' => ['tambah', 'ubah', 'hapus', 'aktifkan', 'nonaktifkan'],
                'null'       => false,
            ],
        ];

        $this->forge->modifyColumn('tb_log_aktivitas', $fields);
    }
}
