<?php
namespace App\Controllers;
use App\Models\MPelanggan;

class Pelanggan extends BaseController
{
    protected $mPelanggan;

    public function __construct()
    {
        $this->mPelanggan = new MPelanggan();
    }

    public function index()
    {
        return view('pelanggan/index', ['pelanggan' => $this->mPelanggan->findAll()]);
    }

    public function create()
    {
        return view('pelanggan/create');
    }

    public function store()
    {
        $this->mPelanggan->save([
            'kode_pelanggan' => $this->mPelanggan->generateKode(),
            'nama_pelanggan' => $this->request->getPost('nama_pelanggan'),
            'no_telp'        => $this->request->getPost('no_telp'),
            'alamat'         => $this->request->getPost('alamat')
        ]);
        return redirect()->to('/pelanggan');
    }

    public function edit($id)
    {
        return view('pelanggan/edit', ['pelanggan' => $this->mPelanggan->find($id)]);
    }

    public function update($id)
    {
        $this->mPelanggan->update($id, [
            'nama_pelanggan' => $this->request->getPost('nama_pelanggan'),
            'no_telp'        => $this->request->getPost('no_telp'),
            'alamat'         => $this->request->getPost('alamat')
        ]);
        return redirect()->to('/pelanggan');
    }

    public function delete($id)
    {
        $this->mPelanggan->delete($id);
        return redirect()->to('/pelanggan');
    }
}