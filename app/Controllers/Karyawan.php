<?php

namespace App\Controllers;

use App\Models\MKaryawan;

class Karyawan extends BaseController
{
    protected $karyawanModel;

    public function __construct()
    {
        // Proteksi: cek sudah login atau belum
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        
        $this->karyawanModel = new MKaryawan(); 
    }

    public function index()
    {
        $data['karyawan'] = $this->karyawanModel->findAll();
        return view('karyawan/index', $data);
    }

    public function create()
    {
        return view('karyawan/create');
    }

    public function store()
    {
        // Hash password sebelum disimpan
        $hashedPassword = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);

        $this->karyawanModel->save([
            'nama_karyawan' => $this->request->getPost('nama_karyawan'),
            'username' => $this->request->getPost('username'),
            'password' => $hashedPassword,
            'level' => $this->request->getPost('level'),
            'no_telp' => $this->request->getPost('no_telp'),
            'gaji_pokok' => $this->request->getPost('gaji_pokok') ?? 0,
        ]);

        session()->setFlashdata('success', 'Data karyawan berhasil ditambahkan');
        return redirect()->to('/karyawan');
    }

    public function edit($id)
    {
        $data['karyawan'] = $this->karyawanModel->find($id);
        return view('karyawan/edit', $data);
    }

    public function update($id)
    {
        $data = [
            'nama_karyawan' => $this->request->getPost('nama_karyawan'),
            'username' => $this->request->getPost('username'),
            'level' => $this->request->getPost('level'),
            'no_telp' => $this->request->getPost('no_telp'),
            'gaji_pokok' => $this->request->getPost('gaji_pokok') ?? 0,
        ];

        // Jika password diisi, update password
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->karyawanModel->update($id, $data);

        session()->setFlashdata('success', 'Data karyawan berhasil diupdate');
        return redirect()->to('/karyawan');
    }

    public function delete($id)
    {
        $this->karyawanModel->delete($id);
        session()->setFlashdata('success', 'Data karyawan berhasil dihapus');
        return redirect()->to('/karyawan');
    }
}