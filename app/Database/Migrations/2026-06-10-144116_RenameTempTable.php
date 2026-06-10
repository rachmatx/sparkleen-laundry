<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameTempTable extends Migration
{
    public function up()
    {
        $this->forge->renameTable('tb_temp_peminjaman', 'tb_temp_keranjang');
    }

    public function down()
    {
        $this->forge->renameTable('tb_temp_keranjang', 'tb_temp_peminjaman');
    }
}
