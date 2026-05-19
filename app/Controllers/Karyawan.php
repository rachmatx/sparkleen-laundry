<?php

namespace App\Controllers;

use App\Models\MKaryawan;

class Karyawan extends BaseController
{
    protected $karyawanModel;

    public function __construct()
    {
        if (!session()->get('isLoggedIn')) {
            header('Location: ' . base_url('/login'));
            exit();
        }

        if (!in_array(session()->get('level'), ['admin', 'pemilik'])) {
            session()->setFlashdata('error', 'Anda tidak memiliki akses ke halaman ini.');
            header('Location: ' . base_url('/dashboard'));
            exit();
        }

        $this->karyawanModel = new MKaryawan();
    }

    /**
     * Memeriksa apakah domain email merupakan typo dari domain populer.
     * Mengembalikan false jika terdeteksi typo, true jika aman.
     */
    private function cekTypoEmail($email)
    {
        if (empty($email)) return true;
        
        $domain = strtolower(substr(strrchr($email, "@"), 1));
        
        $typoDomains = [
            'gmaill.com', 'gmail.con', 'gamil.com', 'gmai.com', 'gmail.co', 'gmail.cpm', 'gmail.com.com',
            'yaho.com', 'yahoo.con', 'yahoo.cpm', 'yahool.com',
            'outlok.com', 'outlook.con', 
            'hotmai.com', 'hotmail.con'
        ];
        
        if (in_array($domain, $typoDomains)) {
            return false;
        }
        return true;
    }

    public function index()
    {
        $data = [
            'title'      => 'Data Karyawan',
            'activeMenu' => 'karyawan',
            'karyawan'   => $this->karyawanModel->findAll(),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('karyawan/index', $data)
             . view('backend/template/footer');
    }

    public function create()
    {
        $data = [
            'title'      => 'Tambah Karyawan',
            'activeMenu' => 'karyawan',
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('karyawan/create', $data)
             . view('backend/template/footer');
    }

    public function store()
    {
        $data = [
            'nama_karyawan' => $this->request->getPost('nama_karyawan'),
            'username'      => $this->request->getPost('username'),
            'password'      => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'level'         => $this->request->getPost('level'),
            'no_telp'       => $this->request->getPost('no_telp'),
            'email'         => $this->request->getPost('email') ?: null,
            'gaji_pokok'    => $this->request->getPost('gaji_pokok') ?? 0,
        ];

        if ($data['email'] && !$this->cekTypoEmail($data['email'])) {
            session()->setFlashdata('errors', ['email' => 'Domain email sepertinya salah ketik (typo). Pastikan menggunakan domain yang benar.']);
            return redirect()->to('/karyawan/create')->withInput();
        }

        if (!$this->karyawanModel->save($data)) {
            session()->setFlashdata('errors', $this->karyawanModel->errors());
            return redirect()->to('/karyawan/create')->withInput();
        }

        session()->setFlashdata('success', 'Data karyawan berhasil ditambahkan.');
        return redirect()->to('/karyawan');
    }

    public function edit($id)
    {
        $data = [
            'title'      => 'Edit Karyawan',
            'activeMenu' => 'karyawan',
            'karyawan'   => $this->karyawanModel->find($id),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('karyawan/edit', $data)
             . view('backend/template/footer');
    }

    public function update($id)
    {
        $data = [
            'nama_karyawan' => $this->request->getPost('nama_karyawan'),
            'username'      => $this->request->getPost('username'),
            'level'         => $this->request->getPost('level'),
            'no_telp'       => $this->request->getPost('no_telp'),
            'email'         => $this->request->getPost('email') ?: null,
            'gaji_pokok'    => $this->request->getPost('gaji_pokok') ?? 0,
            'id_karyawan'   => $id,  // Diperlukan untuk is_unique exception
        ];

        if ($data['email'] && !$this->cekTypoEmail($data['email'])) {
            session()->setFlashdata('errors', ['email' => 'Domain email sepertinya salah ketik (typo). Pastikan menggunakan domain yang benar.']);
            return redirect()->to('/karyawan/edit/' . $id)->withInput();
        }

        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if (!$this->karyawanModel->save($data)) {
            session()->setFlashdata('errors', $this->karyawanModel->errors());
            return redirect()->to('/karyawan/edit/' . $id)->withInput();
        }

        session()->setFlashdata('success', 'Data karyawan berhasil diupdate.');
        return redirect()->to('/karyawan');
    }

    public function delete($id)
    {
        $this->karyawanModel->delete($id);
        session()->setFlashdata('success', 'Data karyawan berhasil dihapus.');
        return redirect()->to('/karyawan');
    }
}