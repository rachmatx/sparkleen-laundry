<?php

namespace App\Controllers;

use App\Models\MKaryawan;

class Profile extends BaseController
{
    protected $karyawanModel;

    public function __construct()
    {
        $this->karyawanModel = new MKaryawan();
    }

    public function index()
    {
        $id = session()->get('id_karyawan');
        
        $data = [
            'title'      => 'Profil Saya',
            'activeMenu' => 'profile',
            'user'       => $this->karyawanModel->find($id),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('profile/index', $data)
             . view('backend/template/footer');
    }

    public function update()
    {
        $id = session()->get('id_karyawan');
        $user = $this->karyawanModel->find($id);

        $data = [
            'id_karyawan'   => $id, // Diperlukan untuk is_unique exception
            'nama_karyawan' => $this->request->getPost('nama_karyawan'),
            'username'      => $this->request->getPost('username'),
            'no_telp'       => $this->request->getPost('no_telp'),
            'email'         => $this->request->getPost('email') ?: null,
        ];

        // Mencegah perubahan level, gaji, atau status (tetap dari DB)
        $data['level'] = $user['level'];
        $data['gaji_pokok'] = $user['gaji_pokok'];
        $data['is_aktif'] = $user['is_aktif'];

        // Cek typo email
        if ($data['email'] && !cekTypoEmail($data['email'])) {
            session()->setFlashdata('errors', ['email' => 'Domain email sepertinya salah ketik (typo). Pastikan menggunakan domain yang benar.']);
            return redirect()->to('/profile')->withInput();
        }

        $password = $this->request->getPost('password');
        $passwordError = password_validation_error(
            $password,
            $this->request->getPost('password_confirm'),
            false
        );
        if ($passwordError !== null) {
            session()->setFlashdata('errors', ['password' => $passwordError]);
            return redirect()->to('/profile')->withInput();
        }
        if (! empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if (!$this->karyawanModel->save($data)) {
            session()->setFlashdata('errors', $this->karyawanModel->errors());
            return redirect()->to('/profile')->withInput();
        }

        // Update session jika nama atau username berubah
        session()->set([
            'nama_karyawan' => $data['nama_karyawan'],
            'username'      => $data['username'],
        ]);

        session()->setFlashdata('success', 'Profil dan kredensial berhasil diperbarui.');
        return redirect()->to('/profile');
    }

    // Fungsi cekTypoEmail telah dipindahkan ke validation_helper.php
}
