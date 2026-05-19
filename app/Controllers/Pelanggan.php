<?php

namespace App\Controllers;

use App\Models\MPelanggan;

class Pelanggan extends BaseController
{
    protected $pelangganModel;
    protected $level;

    public function __construct()
    {
        if (!session()->get('isLoggedIn')) {
            header('Location: ' . base_url('/login'));
            exit();
        }
        $this->level          = session()->get('level');
        $this->pelangganModel = new MPelanggan();
    }

    public function index()
    {
        $data = [
            'title'      => 'Data Pelanggan',
            'activeMenu' => 'pelanggan',
            'pelanggan'  => $this->pelangganModel->findAll(),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('pelanggan/index', $data)
             . view('backend/template/footer');
    }

    public function create()
    {

        $data = [
            'title'      => 'Tambah Pelanggan',
            'activeMenu' => 'pelanggan',
            'kode'       => $this->pelangganModel->generateKode(),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('pelanggan/create', $data)
             . view('backend/template/footer');
    }

    public function store()
    {

        $data = [
            'kode_pelanggan' => $this->request->getPost('kode_pelanggan'),
            'nama_pelanggan' => $this->request->getPost('nama_pelanggan'),
            'no_telp'        => $this->request->getPost('no_telp'),
            'alamat'         => $this->request->getPost('alamat'),
        ];

        if (!$this->pelangganModel->save($data)) {
            session()->setFlashdata('errors', $this->pelangganModel->errors());
            return redirect()->to('/pelanggan/create')->withInput();
        }

        session()->setFlashdata('success', 'Data pelanggan berhasil ditambahkan.');
        return redirect()->to('/pelanggan');
    }

    public function edit($id)
    {

        $data = [
            'title'      => 'Edit Pelanggan',
            'activeMenu' => 'pelanggan',
            'pelanggan'  => $this->pelangganModel->find($id),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('pelanggan/edit', $data)
             . view('backend/template/footer');
    }

    public function update($id)
    {

        $data = [
            'id_pelanggan'   => $id,  // Diperlukan untuk is_unique exception
            'nama_pelanggan' => $this->request->getPost('nama_pelanggan'),
            'no_telp'        => $this->request->getPost('no_telp'),
            'alamat'         => $this->request->getPost('alamat'),
        ];

        if (!$this->pelangganModel->save($data)) {
            session()->setFlashdata('errors', $this->pelangganModel->errors());
            return redirect()->to('/pelanggan/edit/' . $id)->withInput();
        }

        session()->setFlashdata('success', 'Data pelanggan berhasil diupdate.');
        return redirect()->to('/pelanggan');
    }

    public function toggleStatus($id)
    {
        if (!in_array($this->level, ['admin', 'pemilik'])) {
            return redirect()->to('/pelanggan');
        }

        $pelanggan = $this->pelangganModel->find($id);
        if ($pelanggan) {
            $statusBaru = ($pelanggan['is_aktif'] == 1) ? 0 : 1;
            $this->pelangganModel->update($id, ['is_aktif' => $statusBaru]);
            $pesan = ($statusBaru == 1) ? 'diaktifkan' : 'dinonaktifkan';
            session()->setFlashdata('success', "Data pelanggan berhasil $pesan.");
        }
        return redirect()->to('/pelanggan');
    }
}
