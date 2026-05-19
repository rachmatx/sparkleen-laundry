<?php

namespace App\Controllers;

use App\Models\MLayanan;

class Layanan extends BaseController
{
    protected $layananModel;
    protected $level;

    public function __construct()
    {
        if (!session()->get('isLoggedIn')) {
            header('Location: ' . base_url('/login'));
            exit();
        }
        $this->level        = session()->get('level');
        $this->layananModel = new MLayanan();
    }

    public function index()
    {
        $data = [
            'title'      => 'Data Layanan',
            'activeMenu' => 'layanan',
            'layanan'    => $this->layananModel->findAll(),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('layanan/index', $data)
             . view('backend/template/footer');
    }

    public function create()
    {
        if (!in_array($this->level, ['admin', 'pemilik'])) {
            session()->setFlashdata('error', 'Anda tidak memiliki akses ke halaman ini.');
            return redirect()->to('/layanan');
        }

        $data = [
            'title'      => 'Tambah Layanan',
            'activeMenu' => 'layanan',
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('layanan/create', $data)
             . view('backend/template/footer');
    }

    public function store()
    {
        if (!in_array($this->level, ['admin', 'pemilik'])) {
            return redirect()->to('/layanan');
        }

        $this->layananModel->save([
            'nama_layanan'  => $this->request->getPost('nama_layanan'),
            'harga_per_kg'  => $this->request->getPost('harga_per_kg'),
            'estimasi_hari' => $this->request->getPost('estimasi_hari'),
        ]);

        session()->setFlashdata('success', 'Data layanan berhasil ditambahkan.');
        return redirect()->to('/layanan');
    }

    public function edit($id)
    {
        if (!in_array($this->level, ['admin', 'pemilik'])) {
            session()->setFlashdata('error', 'Anda tidak memiliki akses ke halaman ini.');
            return redirect()->to('/layanan');
        }

        $data = [
            'title'      => 'Edit Layanan',
            'activeMenu' => 'layanan',
            'layanan'    => $this->layananModel->find($id),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('layanan/edit', $data)
             . view('backend/template/footer');
    }

    public function update($id)
    {
        if (!in_array($this->level, ['admin', 'pemilik'])) {
            return redirect()->to('/layanan');
        }

        $this->layananModel->update($id, [
            'nama_layanan'  => $this->request->getPost('nama_layanan'),
            'harga_per_kg'  => $this->request->getPost('harga_per_kg'),
            'estimasi_hari' => $this->request->getPost('estimasi_hari'),
        ]);

        session()->setFlashdata('success', 'Data layanan berhasil diupdate.');
        return redirect()->to('/layanan');
    }

    public function toggleStatus($id)
    {
        if (!in_array($this->level, ['admin', 'pemilik'])) {
            return redirect()->to('/layanan');
        }

        $layanan = $this->layananModel->find($id);
        if ($layanan) {
            $statusBaru = ($layanan['is_aktif'] == 1) ? 0 : 1;
            $this->layananModel->update($id, ['is_aktif' => $statusBaru]);
            $pesan = ($statusBaru == 1) ? 'diaktifkan' : 'dinonaktifkan';
            session()->setFlashdata('success', "Data layanan berhasil $pesan.");
        }
        return redirect()->to('/layanan');
    }
}