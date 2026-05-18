<?php

namespace App\Controllers;

use App\Models\MPelanggan;

class Pelanggan extends BaseController
{
    protected $pelangganModel;

    public function __construct()
    {
        // Proteksi: cek sudah login atau belum
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        
        $this->pelangganModel = new MPelanggan();
    }

    // Menampilkan semua data pelanggan
    public function index()
    {
        $data['pelanggan'] = $this->pelangganModel->findAll();
        return view('pelanggan/index', $data); // view sederhana dulu
    }

    // Form tambah pelanggan
    public function create()
    {
        $data['kode'] = $this->pelangganModel->generateKode();
        return view('pelanggan/create', $data);
    }

    // Simpan data pelanggan
    public function store()
    {
        $this->pelangganModel->save([
            'kode_pelanggan' => $this->request->getPost('kode_pelanggan'),
            'nama_pelanggan' => $this->request->getPost('nama_pelanggan'),
            'no_telp' => $this->request->getPost('no_telp'),
            'alamat' => $this->request->getPost('alamat'),
        ]);

        session()->setFlashdata('success', 'Data pelanggan berhasil ditambahkan');
        return redirect()->to('/pelanggan');
    }

    // Form edit pelanggan
    public function edit($id)
    {
        $data['pelanggan'] = $this->pelangganModel->find($id);
        return view('pelanggan/edit', $data);
    }

    // Update data pelanggan
    public function update($id)
    {
        $this->pelangganModel->update($id, [
            'nama_pelanggan' => $this->request->getPost('nama_pelanggan'),
            'no_telp' => $this->request->getPost('no_telp'),
            'alamat' => $this->request->getPost('alamat'),
        ]);

        session()->setFlashdata('success', 'Data pelanggan berhasil diupdate');
        return redirect()->to('/pelanggan');
    }

    // Hapus pelanggan
    public function delete($id)
    {
        $this->pelangganModel->delete($id);
        session()->setFlashdata('success', 'Data pelanggan berhasil dihapus');
        return redirect()->to('/pelanggan');
    }
}
