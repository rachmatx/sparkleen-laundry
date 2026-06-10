<?php

namespace App\Controllers;

use App\Models\MInventaris;

class Inventaris extends BaseController
{
    protected $inventarisModel;

    public function __construct()
    {
        if (!in_array(session()->get('level'), ['admin', 'pemilik', 'kasir'])) {
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
        $page = (int)($this->request->getGet('page') ?? 1);
        $keyword = $this->request->getGet('q');
        if ($page < 1) $page = 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $sort = $this->request->getGet('sort') ?? 'nama_barang';
        $dir  = strtoupper($this->request->getGet('dir') ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $allowedSort = ['nama_barang', 'stok', 'satuan', 'harga_beli', 'is_aktif'];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'nama_barang';
        }

        $query = $this->inventarisModel->orderBy($sort, $dir);
        if (!empty($keyword)) {
            $query->like('nama_barang', $keyword);
        }

        $items = $query->findAll($limit + 1, $offset);
        $hasNext = count($items) > $limit;
        if ($hasNext) {
            array_pop($items);
        }
        $hasPrev = $page > 1;

        // Tambahkan flag stok_kritis
        foreach ($items as &$row) {
            $row['stok_kritis'] = ($row['stok'] <= $row['stok_minimal']) ? 1 : 0;
        }

        $stokKritis = $this->inventarisModel->getStokKritis();

        $data = [
            'title'        => 'Data Inventaris',
            'activeMenu'   => 'inventaris',
            'inventaris'   => $items,
            'page'         => $page,
            'hasPrev'      => $hasPrev,
            'hasNext'      => $hasNext,
            'limit'        => $limit,
            'keyword'      => $keyword,
            'jumlahKritis' => count($stokKritis),
            'sort'         => $sort,
            'dir'          => $dir,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('inventaris/index', $data)
             . view('backend/template/footer');
    }

    // Form tambah
    public function create()
    {
        if (!in_array(session()->get('level'), ['admin', 'pemilik'])) {
            session()->setFlashdata('error', 'Anda tidak memiliki akses ke fitur ini.');
            return redirect()->to('/inventaris');
        }

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
        if (!in_array(session()->get('level'), ['admin', 'pemilik'])) {
            return redirect()->to('/inventaris');
        }

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
        if (!in_array(session()->get('level'), ['admin', 'pemilik'])) {
            session()->setFlashdata('error', 'Anda tidak memiliki akses ke fitur ini.');
            return redirect()->to('/inventaris');
        }

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
        if (!in_array(session()->get('level'), ['admin', 'pemilik'])) {
            return redirect()->to('/inventaris');
        }

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
        if (!in_array(session()->get('level'), ['admin', 'pemilik'])) {
            return redirect()->to('/inventaris');
        }

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
        
        $sort = $this->request->getGet('sort') ?? 'nama_barang';
        $dir  = strtoupper($this->request->getGet('dir') ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $allowedSort = ['nama_barang', 'stok', 'satuan', 'harga_beli', 'is_aktif'];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'nama_barang';
        }

        $query   = $this->inventarisModel->orderBy($sort, $dir);

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
