<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MKaryawan;

class Karyawan extends BaseController
{
    protected $mKaryawan;

    public function __construct()
    {
        $this->mKaryawan = new MKaryawan();
    }

    public function index()
    {
        $data = [
            'karyawan' => $this->mKaryawan->findAll()
        ];
        return view('karyawan/index', $data);
    }

    public function create()
    {
        return view('karyawan/create');
    }

    public function store()
    {
        $data = [
            'nama_karyawan' => $this->request->getPost('nama_karyawan'),
            'username'      => $this->request->getPost('username'),
            'password'      => password_hash((string)$this->request->getPost('password'), PASSWORD_BCRYPT),
            'level'         => $this->request->getPost('level'),
            'no_telp'       => $this->request->getPost('no_telp'),
            'gaji_pokok'    => $this->request->getPost('gaji_pokok')
        ];

        $this->mKaryawan->insert($data);
        return redirect()->to('/karyawan')->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $data = [
            'karyawan' => $this->mKaryawan->find($id)
        ];

        if (empty($data['karyawan'])) {
            return redirect()->to('/karyawan')->with('error', 'Data tidak ditemukan.');
        }

        return view('karyawan/edit', $data);
    }

    public function update($id)
    {
        $passwordInput = $this->request->getPost('password');
        
        $data = [
            'nama_karyawan' => $this->request->getPost('nama_karyawan'),
            'username'      => $this->request->getPost('username'),
            'level'         => $this->request->getPost('level'),
            'no_telp'       => $this->request->getPost('no_telp'),
            'gaji_pokok'    => $this->request->getPost('gaji_pokok')
        ];

        // Hanya update password jika diisi
        if (!empty($passwordInput)) {
            $data['password'] = password_hash((string)$passwordInput, PASSWORD_BCRYPT);
        }

        $this->mKaryawan->update($id, $data);
        return redirect()->to('/karyawan')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->mKaryawan->delete($id);
        return redirect()->to('/karyawan')->with('success', 'Data karyawan berhasil dihapus.');
    }
}