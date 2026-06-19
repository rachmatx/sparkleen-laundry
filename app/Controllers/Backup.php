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
        if ($this->level !== 'pemilik') {
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
        if ($this->level !== 'pemilik') {
            return redirect()->to('/dashboard');
        }

        $password = $this->request->getPost('password');
        if (empty($password)) {
            session()->setFlashdata('error', 'Password otorisasi wajib diisi.');
            return redirect()->to('/backup');
        }

        $id_karyawan = session()->get('id_karyawan');
        $karyawanModel = new \App\Models\MKaryawan();
        $user = $karyawanModel->find($id_karyawan);

        if (!$user || !password_verify($password, $user['password'])) {
            session()->setFlashdata('error', 'Otorisasi gagal! Password tidak sesuai.');
            return redirect()->to('/backup');
        }

        $backupService = new \App\Services\BackupService();
        $sqlDump = $backupService->generateSqlDump();

        $db = \Config\Database::connect();

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
