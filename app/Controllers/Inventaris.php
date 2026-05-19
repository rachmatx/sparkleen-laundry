<?php

namespace App\Controllers;

use App\Models\MInventaris;

class Inventaris extends BaseController
{
    protected $inventarisModel;

    public function __construct()
    {
        if (!session()->get('isLoggedIn')) {
            header('Location: ' . base_url('/login'));
            exit();
        }

        if (!in_array(session()->get('level'), ['admin', 'pemilik'])) {
            // Tidak boleh akses: redirect ke dashboard
            session()->setFlashdata('error', 'Anda tidak memiliki akses ke halaman ini.');
            header('Location: ' . base_url('/dashboard'));
            exit();
        }

        $this->inventarisModel = new MInventaris();
    }

    // Daftar inventaris
    public function index()
    {
        $semuaInventaris = $this->inventarisModel->getSemuaInventaris();
        $stokKritis      = array_filter($semuaInventaris, fn($i) => $i['stok_kritis']);

        $data = [
            'title'        => 'Data Inventaris',
            'activeMenu'   => 'inventaris',
            'inventaris'   => $semuaInventaris,
            'jumlahKritis' => count($stokKritis),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('inventaris/index', $data)
             . view('backend/template/footer');
    }

    // Form tambah
    public function create()
    {
        $data = [
            'title'      => 'Tambah Inventaris',
            'activeMenu' => 'inventaris',
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('inventaris/create', $data)
             . view('backend/template/footer');
    }

    // Simpan
    public function store()
    {
        $this->inventarisModel->save([
            'nama_barang'  => $this->request->getPost('nama_barang'),
            'stok'         => (float) $this->request->getPost('stok'),
            'satuan'       => $this->request->getPost('satuan'),
            'harga_beli'   => (int) $this->request->getPost('harga_beli'),
            'stok_minimal' => (float) $this->request->getPost('stok_minimal'),
        ]);

        session()->setFlashdata('success', 'Data inventaris berhasil ditambahkan.');
        return redirect()->to('/inventaris');
    }

    // Form edit
    public function edit($id)
    {
        $data = [
            'title'      => 'Edit Inventaris',
            'activeMenu' => 'inventaris',
            'inventaris' => $this->inventarisModel->find($id),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('inventaris/edit', $data)
             . view('backend/template/footer');
    }

    // Update
    public function update($id)
    {
        $this->inventarisModel->update($id, [
            'nama_barang'  => $this->request->getPost('nama_barang'),
            'stok'         => (float) $this->request->getPost('stok'),
            'satuan'       => $this->request->getPost('satuan'),
            'harga_beli'   => (int) $this->request->getPost('harga_beli'),
            'stok_minimal' => (float) $this->request->getPost('stok_minimal'),
        ]);

        session()->setFlashdata('success', 'Data inventaris berhasil diupdate.');
        return redirect()->to('/inventaris');
    }

    public function toggleStatus($id)
    {
        $item = $this->inventarisModel->find($id);
        if ($item) {
            $statusBaru = ($item['is_aktif'] == 1) ? 0 : 1;
            $this->inventarisModel->update($id, ['is_aktif' => $statusBaru]);
            $pesan = ($statusBaru == 1) ? 'diaktifkan' : 'dinonaktifkan';
            session()->setFlashdata('success', "Data inventaris berhasil $pesan.");
        }
        return redirect()->to('/inventaris');
    }

    // Halaman stok kritis
    public function warning()
    {
        $data = [
            'title'      => 'Peringatan Stok Kritis',
            'activeMenu' => 'inventaris',
            'inventaris' => $this->inventarisModel->getStokKritis(),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('inventaris/warning', $data)
             . view('backend/template/footer');
    }
}
