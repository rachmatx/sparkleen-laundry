<?php

namespace App\Controllers;

use App\Models\MLayanan;
use App\Models\MInventaris;
use App\Models\MLayananInventaris;

class Layanan extends BaseController
{
    protected $layananModel;
    protected $inventarisModel;
    protected $layananInventarisModel;
    protected $level;

    public function __construct()
    {
        $this->level                  = session()->get('level');
        $this->layananModel           = new MLayanan();
        $this->inventarisModel        = new MInventaris();
        $this->layananInventarisModel = new MLayananInventaris();
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

    public function resep($id)
    {
        if ($this->level !== 'admin') {
            session()->setFlashdata('error', 'Hanya Admin yang dapat mengonfigurasi resep bahan baku.');
            return redirect()->to('/layanan');
        }

        $layanan = $this->layananModel->find($id);
        if (!$layanan) {
            session()->setFlashdata('error', 'Layanan tidak ditemukan.');
            return redirect()->to('/layanan');
        }

        // Dapatkan semua barang inventaris aktif
        $inventaris = $this->inventarisModel->where('is_aktif', 1)->findAll();

        // Dapatkan data resep saat ini
        $currentRecipeRaw = $this->layananInventarisModel->where('id_layanan', $id)->findAll();
        $currentRecipe = [];
        foreach ($currentRecipeRaw as $r) {
            $currentRecipe[$r['id_inventaris']] = $r['jumlah_default'];
        }

        $data = [
            'title'         => 'Resep Bahan Baku - ' . $layanan['nama_layanan'],
            'activeMenu'    => 'layanan',
            'layanan'       => $layanan,
            'inventaris'    => $inventaris,
            'currentRecipe' => $currentRecipe,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('layanan/resep', $data)
             . view('backend/template/footer');
    }

    public function resepStore($id)
    {
        if ($this->level !== 'admin') {
            return redirect()->to('/layanan');
        }

        $layanan = $this->layananModel->find($id);
        if (!$layanan) {
            session()->setFlashdata('error', 'Layanan tidak ditemukan.');
            return redirect()->to('/layanan');
        }

        // Hapus resep yang ada terlebih dahulu
        $this->layananInventarisModel->where('id_layanan', $id)->delete();

        // Simpan resep baru
        $jumlahDefaults = $this->request->getPost('jumlah_default') ?? [];
        foreach ($jumlahDefaults as $id_inventaris => $jumlah) {
            $jumlah = (float)$jumlah;
            if ($jumlah > 0) {
                $this->layananInventarisModel->save([
                    'id_layanan'     => $id,
                    'id_inventaris'  => (int)$id_inventaris,
                    'jumlah_default' => $jumlah,
                ]);
            }
        }

        session()->setFlashdata('success', 'Resep bahan baku layanan berhasil disimpan.');
        return redirect()->to('/layanan');
    }
}