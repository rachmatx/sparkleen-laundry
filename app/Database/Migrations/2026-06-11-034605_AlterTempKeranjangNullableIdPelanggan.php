<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTempKeranjangNullableIdPelanggan extends Migration
{
    public function up()
    {
        // First drop the foreign key since we're modifying the column
        $this->db->query("ALTER TABLE tb_temp_keranjang DROP FOREIGN KEY fk_cart_pelanggan");

        $fields = [
            'id_pelanggan' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'default' => null,
            ],
        ];
        $this->forge->modifyColumn('tb_temp_keranjang', $fields);

        // Update existing 0 values to null
        $this->db->query("UPDATE tb_temp_keranjang SET id_pelanggan = NULL WHERE id_pelanggan = 0");

        // Add back the foreign key
        $this->db->query("ALTER TABLE tb_temp_keranjang 
            ADD CONSTRAINT fk_cart_pelanggan FOREIGN KEY (id_pelanggan) REFERENCES tb_pelanggan(id_pelanggan) ON DELETE CASCADE ON UPDATE CASCADE");
    }

    public function down()
    {
        // Drop the foreign key
        $this->db->query("ALTER TABLE tb_temp_keranjang DROP FOREIGN KEY fk_cart_pelanggan");

        // Set nulls to 0 before reverting to NOT NULL
        $this->db->query("UPDATE tb_temp_keranjang SET id_pelanggan = 0 WHERE id_pelanggan IS NULL");

        $fields = [
            'id_pelanggan' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
        ];
        $this->forge->modifyColumn('tb_temp_keranjang', $fields);

        // Add back the foreign key
        $this->db->query("ALTER TABLE tb_temp_keranjang 
            ADD CONSTRAINT fk_cart_pelanggan FOREIGN KEY (id_pelanggan) REFERENCES tb_pelanggan(id_pelanggan) ON DELETE CASCADE ON UPDATE CASCADE");
    }
}
