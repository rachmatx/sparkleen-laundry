<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameTempTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tb_temp_peminjaman') && !$this->db->tableExists('tb_temp_keranjang')) {
            $this->forge->renameTable('tb_temp_peminjaman', 'tb_temp_keranjang');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tb_temp_keranjang') && !$this->db->tableExists('tb_temp_peminjaman')) {
            $this->forge->renameTable('tb_temp_keranjang', 'tb_temp_peminjaman');
        }
    }
}
