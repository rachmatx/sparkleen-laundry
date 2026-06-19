<?php

namespace App\Controllers;

use App\Models\MPelanggan;

class Pelanggan extends BaseController
{
    use \App\Controllers\Traits\PaginationTrait;

    protected $pelangganModel;
    protected $level;

    public function __construct()
    {
        $this->level          = session()->get('level');
        $this->pelangganModel = new MPelanggan();
    }

    public function index()
    {
        $keyword = $this->request->getGet('q');
        $status = $this->request->getGet('status');

        $pg = $this->parsePagination(
            ['kode_pelanggan', 'nama_pelanggan', 'no_telp', 'alamat', 'is_aktif', 'id_pelanggan'],
            'id_pelanggan',
            'DESC'
        );

        $query = $this->pelangganModel->orderBy($pg['sort'], $pg['dir']);

        if ($status !== null && $status !== '') {
            $query->where('is_aktif', $status);
        }

        if (!empty($keyword)) {
            $query->groupStart()
                  ->like('nama_pelanggan', $keyword)
                  ->orLike('no_telp', $keyword)
                  ->orLike('kode_pelanggan', $keyword)
                  ->groupEnd();
        }

        $result = $this->paginateResults(
            $query->findAll($pg['limit'] + 1, $pg['offset']),
            $pg['limit']
        );

        $data = [
            'title'      => 'Data Pelanggan',
            'activeMenu' => 'pelanggan',
            'pelanggan'  => $result['items'],
            'page'       => $pg['page'],
            'hasPrev'    => $pg['page'] > 1,
            'hasNext'    => $result['has_next'],
            'limit'      => $pg['limit'],
            'keyword'    => $keyword,
            'status'     => $status,
            'sort'       => $pg['sort'],
            'dir'        => $pg['dir'],
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


        $pelanggan = $this->pelangganModel->find($id);
        if (!$pelanggan) {
            session()->setFlashdata('error', 'Data pelanggan tidak ditemukan.');
            return redirect()->to('/pelanggan');
        }

        $data = [
            'title'      => 'Edit Pelanggan',
            'activeMenu' => 'pelanggan',
            'pelanggan'  => $pelanggan,
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


        $pelanggan = $this->pelangganModel->find($id);
        if ($pelanggan) {
            $statusBaru = ($pelanggan['is_aktif'] == 1) ? 0 : 1;
            $this->pelangganModel->update($id, ['is_aktif' => $statusBaru]);
            $pesan = ($statusBaru == 1) ? 'diaktifkan' : 'dinonaktifkan';
            session()->setFlashdata('success', "Data pelanggan berhasil $pesan.");
        }
        return redirect()->to('/pelanggan');
    }

    // Endpoint for Select2 AJAX search
    public function ajaxSearch()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses ditolak']);
        }

        $keyword = $this->request->getGet('q');
        $query = $this->pelangganModel->select('id_pelanggan as id, nama_pelanggan, no_telp, poin')->where('is_aktif', 1);

        if (!empty($keyword)) {
            $query->groupStart()
                  ->like('nama_pelanggan', $keyword)
                  ->orLike('no_telp', $keyword)
                  ->groupEnd();
        }

        // Batasi 20 hasil maksimal agar respons cepat
        $pelanggan = $query->orderBy('nama_pelanggan', 'ASC')->findAll(20);

        // Format array yang dibutuhkan Select2
        $results = [];
        foreach ($pelanggan as $p) {
            $results[] = [
                'id' => $p['id'],
                'text' => $p['nama_pelanggan'] . ' - ' . $p['no_telp'],
                'poin' => $p['poin'] ?? 0
            ];
        }

        return $this->response->setJSON(['results' => $results]);
    }

    // Endpoint for live search table (data-page="pelanggan")
    public function ajaxSearchTable()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses ditolak']);
        }

        $keyword = $this->request->getGet('q');
        $status = $this->request->getGet('status');
        $pg = $this->parsePagination(
            ['kode_pelanggan', 'nama_pelanggan', 'no_telp', 'alamat', 'is_aktif', 'id_pelanggan'],
            'id_pelanggan',
            'DESC'
        );

        $query = $this->pelangganModel->orderBy($pg['sort'], $pg['dir']);

        if ($status !== null && $status !== '') {
            $query->where('is_aktif', $status);
        }

        if (!empty($keyword)) {
            $query->groupStart()
                  ->like('nama_pelanggan', $keyword)
                  ->orLike('no_telp', $keyword)
                  ->orLike('kode_pelanggan', $keyword)
                  ->groupEnd();
        }

        $items = $query->findAll(100);
        $level = $this->level;

        return $this->response->setJSON([
            'data'      => $items,
            'total'     => count($items),
            'canEdit'   => in_array($level, ['admin', 'kasir']),
            'canToggle' => in_array($level, ['admin', 'pemilik']),
        ]);
    }
}
