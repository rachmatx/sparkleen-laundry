<?php
namespace App\Controllers;
use App\Models\MLayanan;

class Layanan extends BaseController
{
    protected $mLayanan;

    public function __construct()
    {
        $this->mLayanan = new MLayanan();
    }

    public function index()
    {
        return view('layanan/index', ['layanan' => $this->mLayanan->findAll()]);
    }

    public function create()
    {
        return view('layanan/create');
    }

    public function store()
    {
        $this->mLayanan->save([
            'nama_layanan'  => $this->request->getPost('nama_layanan'),
            'harga_per_kg'  => $this->request->getPost('harga_per_kg'),
            'estimasi_hari' => $this->request->getPost('estimasi_hari')
        ]);
        return redirect()->to('/layanan');
    }

    public function edit($id)
    {
        return view('layanan/edit', ['layanan' => $this->mLayanan->find($id)]);
    }

    public function update($id)
    {
        $this->mLayanan->update($id, [
            'nama_layanan'  => $this->request->getPost('nama_layanan'),
            'harga_per_kg'  => $this->request->getPost('harga_per_kg'),
            'estimasi_hari' => $this->request->getPost('estimasi_hari')
        ]);
        return redirect()->to('/layanan');
    }

    public function delete($id)
    {
        $this->mLayanan->delete($id);
        return redirect()->to('/layanan');
    }
}