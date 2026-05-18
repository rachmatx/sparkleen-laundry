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

    // Menampilkan halaman login
    public function login()
    {
        // Jika sudah login, redirect ke dashboard
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }
        return view('auth/login');
    }

    // Proses autentikasi login
    public function doLogin()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // Cari user berdasarkan username
        $builder = $this->karyawanModel->db->table('tb_karyawan');
        $builder->where('username', $username);
        $query = $builder->get();
        $user = $query->getRow();

        if ($user) {
            // Verifikasi password
            if (password_verify($password, $user->password)) {
                // Set session
                session()->set([
                    'id_karyawan' => $user->id_karyawan,
                    'nama_karyawan' => $user->nama_karyawan,
                    'username' => $user->username,
                    'level' => $user->level,
                    'isLoggedIn' => true
                ]);

                session()->setFlashdata('success', 'Selamat datang, ' . $user->nama_karyawan);
                return redirect()->to('/dashboard');
            }
        }

        session()->setFlashdata('error', 'Username atau password salah!');
        return redirect()->to('/login');
    }

    // Logout
    public function logout()
    {
        session()->destroy();
        session()->setFlashdata('success', 'Anda telah logout');
        return redirect()->to('/login');
    }
}