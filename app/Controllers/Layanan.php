<?php

namespace App\Controllers;

use App\Models\MLayanan;

class Layanan extends BaseController
{
    protected $layananModel;

    public function __construct()
    {
        // Proteksi: cek sudah login atau belum
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        
        $this->layananModel = new MLayanan();
    }

    public function index()
    {
        $data['layanan'] = $this->layananModel->findAll();
        return view('layanan/index', $data);
    }

    public function create()
    {
        return view('layanan/create');
    }

    public function store()
    {
        $this->layananModel->save([
            'nama_layanan' => $this->request->getPost('nama_layanan'),
            'harga_per_kg' => $this->request->getPost('harga_per_kg'),
            'estimasi_hari' => $this->request->getPost('estimasi_hari'),
        ]);

        session()->setFlashdata('success', 'Data layanan berhasil ditambahkan');
        return redirect()->to('/layanan');
    }

    public function edit($id)
    {
        $data['layanan'] = $this->layananModel->find($id);
        return view('layanan/edit', $data);
    }

    public function update($id)
    {
        $this->layananModel->update($id, [
            'nama_layanan' => $this->request->getPost('nama_layanan'),
            'harga_per_kg' => $this->request->getPost('harga_per_kg'),
            'estimasi_hari' => $this->request->getPost('estimasi_hari'),
        ]);

        session()->setFlashdata('success', 'Data layanan berhasil diupdate');
        return redirect()->to('/layanan');
    }

    public function delete($id)
    {
        $this->layananModel->delete($id);
        session()->setFlashdata('success', 'Data layanan berhasil dihapus');
        return redirect()->to('/layanan');
    }
}