<?php

namespace App\Controllers;

use App\Models\MInventaris;

class Inventaris extends BaseController
{
    protected $inventarisModel;

    public function __construct()
    {
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
        $data = [
            'nama_barang'  => $this->request->getPost('nama_barang'),
            'stok'         => (float) $this->request->getPost('stok'),
            'satuan'       => $this->request->getPost('satuan'),
            'harga_beli'   => (int) $this->request->getPost('harga_beli'),
            'stok_minimal' => (float) $this->request->getPost('stok_minimal'),
        ];

        $this->inventarisModel->save($data);

        log_aktivitas('inventaris', 'tambah', (int) $this->inventarisModel->getInsertID(), $data['nama_barang'], [
            'stok'         => $data['stok'],
            'satuan'       => $data['satuan'],
            'harga_beli'   => $data['harga_beli'],
            'stok_minimal' => $data['stok_minimal'],
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
        $lama = $this->inventarisModel->find($id);
        $baru = [
            'nama_barang'  => $this->request->getPost('nama_barang'),
            'stok'         => (float) $this->request->getPost('stok'),
            'satuan'       => $this->request->getPost('satuan'),
            'harga_beli'   => (int) $this->request->getPost('harga_beli'),
            'stok_minimal' => (float) $this->request->getPost('stok_minimal'),
        ];

        $this->inventarisModel->update($id, $baru);

        $perubahan = $lama
            ? detail_perubahan($lama, $baru, ['nama_barang', 'stok', 'satuan', 'harga_beli', 'stok_minimal'])
            : $baru;
        log_aktivitas('inventaris', 'ubah', (int) $id, $baru['nama_barang'], $perubahan);

        session()->setFlashdata('success', 'Data inventaris berhasil diupdate.');
        return redirect()->to('/inventaris');
    }

    public function toggleStatus($id)
    {
        $item = $this->inventarisModel->find($id);
        if ($item) {
            $statusBaru = ($item['is_aktif'] == 1) ? 0 : 1;
            $this->inventarisModel->update($id, ['is_aktif' => $statusBaru]);
            $aksiLog = $statusBaru === 1 ? 'aktifkan' : 'nonaktifkan';
            log_aktivitas('inventaris', $aksiLog, (int) $id, $item['nama_barang'], [
                'is_aktif' => ['dari' => $item['is_aktif'], 'ke' => $statusBaru],
            ]);
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
