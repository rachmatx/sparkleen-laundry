<?php

namespace App\Controllers;

use App\Models\MLogAktivitas;

class Backup extends BaseController
{
    protected $level;

    public function __construct()
    {
        $this->level = session()->get('level');
    }

    public function index()
    {
        if (!in_array($this->level, ['admin', 'pemilik'])) {
            session()->setFlashdata('error', 'Anda tidak memiliki akses ke fitur ini.');
            return redirect()->to('/dashboard');
        }

        $data = [
            'title'      => 'Backup Database',
            'activeMenu' => 'backup',
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('backup/index', $data)
             . view('backend/template/footer');
    }

    public function download()
    {
        if (!in_array($this->level, ['admin', 'pemilik'])) {
            return redirect()->to('/dashboard');
        }

        $db = \Config\Database::connect();
        $tables = $db->listTables();

        $sqlDump = "-- Sparkleen Laundry Database Backup\n";
        $sqlDump .= "-- Generated on " . date('Y-m-d H:i:s') . "\n";
        $sqlDump .= "-- Host: " . $db->hostname . "\n";
        $sqlDump .= "-- Database: " . $db->database . "\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Dapatkan skema tabel (CREATE TABLE)
            $query = $db->query("SHOW CREATE TABLE `" . $table . "`");
            $row = $query->getRowArray();
            
            $sqlDump .= "-- Table structure for table `$table`\n";
            $sqlDump .= "DROP TABLE IF EXISTS `" . $table . "`;\n";
            $sqlDump .= $row['Create Table'] . ";\n\n";

            // Dapatkan isi data tabel
            $rows = $db->table($table)->get()->getResultArray();
            if (!empty($rows)) {
                $sqlDump .= "-- Dumping data for table `$table`\n";
                foreach ($rows as $r) {
                    $keys = array_keys($r);
                    $escaped_values = [];
                    foreach ($r as $val) {
                        if ($val === null) {
                            $escaped_values[] = "NULL";
                        } else {
                            $escaped_values[] = $db->escape($val);
                        }
                    }
                    $sqlDump .= "INSERT INTO `" . $table . "` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $escaped_values) . ");\n";
                }
                $sqlDump .= "\n";
            }
        }

        $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        // Catat aktivitas backup
        $logModel = new MLogAktivitas();
        $logModel->save([
            'id_karyawan'    => session()->get('id_karyawan'),
            'nama_karyawan'  => session()->get('nama_karyawan'),
            'level_karyawan' => $this->level,
            'modul'          => 'Keamanan',
            'aksi'           => 'Backup Database',
            'id_referensi'   => null,
            'nama_referensi' => $db->database,
            'detail'         => 'Berhasil mengekspor database (backup).',
            'ip_address'     => $this->request->getIPAddress(),
        ]);

        $fileName = 'backup_' . $db->database . '_' . date('Ymd_His') . '.sql';

        return $this->response->setHeader('Content-Type', 'application/octet-stream')
                              ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
                              ->setBody($sqlDump);
    }
}
