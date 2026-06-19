<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTempKeranjangAddFk extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('tb_temp_keranjang')) return;

        // Clean up any orphaned data first before adding constraints
        $this->db->query("DELETE FROM tb_temp_keranjang WHERE id_pelanggan NOT IN (SELECT id_pelanggan FROM tb_pelanggan)");
        $this->db->query("DELETE FROM tb_temp_keranjang WHERE id_kasir NOT IN (SELECT id_karyawan FROM tb_karyawan)");
        $this->db->query("DELETE FROM tb_temp_keranjang WHERE id_layanan NOT IN (SELECT id_layanan FROM tb_layanan)");

        // Add foreign key constraints
        $this->db->query("ALTER TABLE tb_temp_keranjang
            ADD CONSTRAINT fk_cart_pelanggan FOREIGN KEY (id_pelanggan) REFERENCES tb_pelanggan(id_pelanggan) ON DELETE CASCADE,
            ADD CONSTRAINT fk_cart_kasir FOREIGN KEY (id_kasir) REFERENCES tb_karyawan(id_karyawan) ON DELETE CASCADE,
            ADD CONSTRAINT fk_cart_layanan FOREIGN KEY (id_layanan) REFERENCES tb_layanan(id_layanan) ON DELETE CASCADE");
    }

    public function down()
    {
        if (!$this->db->tableExists('tb_temp_keranjang')) return;

        $this->db->query("ALTER TABLE tb_temp_keranjang
            DROP FOREIGN KEY fk_cart_pelanggan,
            DROP FOREIGN KEY fk_cart_kasir,
            DROP FOREIGN KEY fk_cart_layanan");
    }
}
