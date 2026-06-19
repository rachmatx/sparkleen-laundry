<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterKaryawanLevel extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('tb_karyawan')) return;

        // 1. Tambah kolom id_level baru
        $this->forge->addColumn('tb_karyawan', [
            'id_level' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'password',
            ]
        ]);

        // 2. Update data (konversi string ke id)
        // admin = 1, kasir = 2, pemilik = 3 (sesuai MasterSeeder)
        $this->db->query("UPDATE tb_karyawan SET id_level = 1 WHERE level = 'admin'");
        $this->db->query("UPDATE tb_karyawan SET id_level = 2 WHERE level = 'kasir'");
        $this->db->query("UPDATE tb_karyawan SET id_level = 3 WHERE level = 'pemilik'");

        // 3. Ubah kolom id_level agar tidak boleh null
        $this->forge->modifyColumn('tb_karyawan', [
            'id_level' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ]
        ]);

        // 4. Hapus kolom level yang lama
        $this->forge->dropColumn('tb_karyawan', 'level');

        // 5. Tambahkan Foreign Key
        $this->forge->addForeignKey('id_level', 'tb_master_level_karyawan', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('tb_karyawan');
    }

    public function down()
    {
        if (!$this->db->tableExists('tb_karyawan')) return;

        // Jika di-rollback
        $this->forge->dropForeignKey('tb_karyawan', 'tb_karyawan_id_level_foreign');

        $this->forge->addColumn('tb_karyawan', [
            'level' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'kasir', 'pemilik'],
                'default'    => 'kasir',
                'after'      => 'id_level',
            ]
        ]);

        $this->db->query("UPDATE tb_karyawan SET level = 'admin' WHERE id_level = 1");
        $this->db->query("UPDATE tb_karyawan SET level = 'kasir' WHERE id_level = 2");
        $this->db->query("UPDATE tb_karyawan SET level = 'pemilik' WHERE id_level = 3");

        $this->forge->dropColumn('tb_karyawan', 'id_level');
    }
}
