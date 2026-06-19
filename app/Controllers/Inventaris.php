<?php

namespace App\Controllers;

use App\Models\MInventaris;

class Inventaris extends BaseController
{
    use \App\Controllers\Traits\PaginationTrait;

    protected $inventarisModel;

    public function __construct()
    {
        $this->inventarisModel = new MInventaris();
    }

    // Daftar inventaris
    public function index()
    {
        $keyword = $this->request->getGet('q');

        $pg = $this->parsePagination(
            ['nama_barang', 'stok', 'satuan', 'harga_beli', 'is_aktif'],
            'nama_barang',
            'ASC'
        );

        $query = $this->inventarisModel->orderBy($pg['sort'], $pg['dir']);
        if (!empty($keyword)) {
            $query->like('nama_barang', $keyword);
        }

        $result = $this->paginateResults(
            $query->findAll($pg['limit'] + 1, $pg['offset']),
            $pg['limit']
        );

        // Tambahkan flag stok_kritis
        foreach ($result['items'] as &$row) {
            $row['stok_kritis'] = ($row['stok'] <= $row['stok_minimal']) ? 1 : 0;
        }

        $stokKritis = $this->inventarisModel->getStokKritis();

        $data = [
            'title'        => 'Data Inventaris',
            'activeMenu'   => 'inventaris',
            'inventaris'   => $result['items'],
            'page'         => $pg['page'],
            'hasPrev'      => $pg['page'] > 1,
            'hasNext'      => $result['has_next'],
            'limit'        => $pg['limit'],
            'keyword'      => $keyword,
            'jumlahKritis' => count($stokKritis),
            'sort'         => $pg['sort'],
            'dir'          => $pg['dir'],
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
        $inventaris = $this->inventarisModel->find($id);
        if (!$inventaris) {
            session()->setFlashdata('error', 'Data inventaris tidak ditemukan.');
            return redirect()->to('/inventaris');
        }

        $data = [
            'title'      => 'Edit Inventaris',
            'activeMenu' => 'inventaris',
            'inventaris' => $inventaris,
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

    // Endpoint live search tabel inventaris
    public function ajaxSearch()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses ditolak']);
        }

        $keyword = $this->request->getGet('q');

        $pg = $this->parsePagination(
            ['nama_barang', 'stok', 'satuan', 'harga_beli', 'is_aktif'],
            'nama_barang',
            'ASC'
        );

        $query = $this->inventarisModel->orderBy($pg['sort'], $pg['dir']);

        if (!empty($keyword)) {
            $query->like('nama_barang', $keyword);
        }

        $items = $query->findAll(100);

        // Tambahkan flag stok_kritis
        foreach ($items as &$row) {
            $row['stok_kritis'] = ($row['stok'] <= $row['stok_minimal']) ? 1 : 0;
        }

        $level = session()->get('level');

        return $this->response->setJSON([
            'data'      => $items,
            'total'     => count($items),
            'canManage' => in_array($level, ['admin', 'pemilik']),
        ]);
    }

    // Endpoint untuk mengecek stok kritis via AJAX (dipanggil di header/footer global)
    public function ajaxCekStokKritis()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses ditolak']);
        }

        $kritis = $this->inventarisModel->getStokKritis();
        
        $items = [];
        foreach ($kritis as $item) {
            $items[] = [
                'nama' => $item['nama_barang'],
                'stok' => $item['stok'],
                'satuan' => $item['satuan']
            ];
        }

        return $this->response->setJSON([
            'count' => count($kritis),
            'items' => $items
        ]);
    }
}
