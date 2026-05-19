<?php
namespace App\Controllers;
use CodeIgniter\Controller;

class Test extends Controller {
    public function index() {
        $db = \Config\Database::connect();
        
        $penggunaan = $db->query("SELECT * FROM tb_penggunaan_inventaris")->getResultArray();
        echo "PENGGUNAAN:\n";
        print_r($penggunaan);

        $inventaris = $db->query("SELECT * FROM tb_inventaris")->getResultArray();
        echo "INVENTARIS:\n";
        print_r($inventaris);
    }

    // Update column types to DATETIME
    public function fixDb() {
        $db = \Config\Database::connect();
        $db->query("ALTER TABLE tb_transaksi MODIFY tgl_masuk DATETIME");
        $db->query("ALTER TABLE tb_transaksi MODIFY tgl_selesai_estimasi DATETIME");
        $db->query("ALTER TABLE tb_transaksi MODIFY tgl_selesai_aktual DATETIME");
        
        $fields = $db->getFieldData('tb_transaksi');
        echo "Database berhasil diperbarui ke DATETIME!\n";
        print_r($fields);
    }
}

