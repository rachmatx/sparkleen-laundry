<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniquePhone extends Migration
{
    public function up()
    {
        // Bersihkan duplikat karyawan (aman jika tidak ada duplikat)
        $this->db->query("
            UPDATE tb_karyawan k
            INNER JOIN (
                SELECT MIN(id_karyawan) as keep_id, no_telp
                FROM tb_karyawan
                GROUP BY no_telp
                HAVING COUNT(*) > 1
            ) dup ON k.no_telp = dup.no_telp
            SET k.no_telp = CONCAT(k.id_karyawan, '_', k.no_telp)
            WHERE k.id_karyawan != dup.keep_id
        ");

        // Bersihkan duplikat pelanggan
        $this->db->query("
            UPDATE tb_pelanggan p
            INNER JOIN (
                SELECT MIN(id_pelanggan) as keep_id, no_telp
                FROM tb_pelanggan
                GROUP BY no_telp
                HAVING COUNT(*) > 1
            ) dup ON p.no_telp = dup.no_telp
            SET p.no_telp = CONCAT(p.id_pelanggan, '_', p.no_telp)
            WHERE p.id_pelanggan != dup.keep_id
        ");

        // Tambah UNIQUE key — hanya jika belum ada
        $keyPelanggan = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_pelanggan' AND INDEX_NAME = 'uq_pelanggan_telp'"
        )->getRow();

        if (!$keyPelanggan || $keyPelanggan->cnt == 0) {
            $this->db->query('ALTER TABLE tb_pelanggan ADD UNIQUE KEY uq_pelanggan_telp (no_telp)');
        }

        $keyKaryawan = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_karyawan' AND INDEX_NAME = 'uq_karyawan_telp'"
        )->getRow();

        if (!$keyKaryawan || $keyKaryawan->cnt == 0) {
            $this->db->query('ALTER TABLE tb_karyawan ADD UNIQUE KEY uq_karyawan_telp (no_telp)');
        }
    }

    public function down()
    {
        $this->db->query('ALTER TABLE tb_pelanggan DROP INDEX IF EXISTS uq_pelanggan_telp');
        $this->db->query('ALTER TABLE tb_karyawan DROP INDEX IF EXISTS uq_karyawan_telp');
    }
}
