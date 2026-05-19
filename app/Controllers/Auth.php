<?php

namespace App\Controllers;

use App\Models\MKaryawan;

class Auth extends BaseController
{
    protected $karyawanModel;

    public function __construct()
    {
        $this->karyawanModel = new MKaryawan();
    }

    // Halaman login
    public function login()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }
        return view('auth/login');
    }

    // Proses login — bisa username ATAU email
    public function doLogin()
    {
        $identifier = trim($this->request->getPost('username')); // field name tetap 'username' di form
        $password   = $this->request->getPost('password');

        // Validasi input dasar
        if (empty($identifier) || empty($password)) {
            session()->setFlashdata('error', 'Username/email dan password wajib diisi.');
            return redirect()->to('/login');
        }

        // Gunakan method getLogin dari model (support username & email)
        $user = $this->karyawanModel->getLogin($identifier, $password);

        if ($user) {
            session()->set([
                'id_karyawan'   => $user->id_karyawan,
                'nama_karyawan' => $user->nama_karyawan,
                'username'      => $user->username,
                'email'         => $user->email ?? '',
                'level'         => $user->level,
                'isLoggedIn'    => true,
            ]);

            session()->setFlashdata('success', 'Selamat datang, ' . $user->nama_karyawan . '!');
            return redirect()->to('/dashboard');
        }

        session()->setFlashdata('error', 'Username/email atau password salah.');
        return redirect()->to('/login');
    }

    // Logout
    public function logout()
    {
        session()->destroy();
        session()->setFlashdata('success', 'Anda telah berhasil logout.');
        return redirect()->to('/login');
    }
}