<?php

namespace App\Controllers;

use App\Models\MKaryawan;

class Karyawan extends BaseController
{
    protected $karyawanModel;

    public function __construct()
    {


        $this->karyawanModel = new MKaryawan();
    }

    // Fungsi cekTypoEmail telah dipindahkan ke validation_helper.php

    public function index()
    {
        $page = (int)($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $sort = $this->request->getGet('sort') ?? 'id_karyawan';
        $dir  = strtoupper($this->request->getGet('dir') ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $allowedSort = ['nama_karyawan', 'username', 'level', 'no_telp', 'gaji_pokok', 'is_aktif', 'id_karyawan'];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'id_karyawan';
        }

        $items = $this->karyawanModel->orderBy($sort, $dir)->findAll($limit + 1, $offset);
        $hasNext = count($items) > $limit;
        if ($hasNext) {
            array_pop($items);
        }
        $hasPrev = $page > 1;

        $data = [
            'title'      => 'Data Karyawan',
            'activeMenu' => 'karyawan',
            'karyawan'   => $items,
            'page'       => $page,
            'hasPrev'    => $hasPrev,
            'hasNext'    => $hasNext,
            'limit'      => $limit,
            'sort'       => $sort,
            'dir'        => $dir,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('karyawan/index', $data)
             . view('backend/template/footer');
    }

    public function create()
    {
        $data = [
            'title'      => 'Tambah Karyawan',
            'activeMenu' => 'karyawan',
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('karyawan/create', $data)
             . view('backend/template/footer');
    }

    public function store()
    {
        $passwordError = password_validation_error(
            $this->request->getPost('password'),
            $this->request->getPost('password_confirm'),
            true
        );
        if ($passwordError !== null) {
            session()->setFlashdata('errors', ['password' => $passwordError]);
            return redirect()->to('/karyawan/create')->withInput();
        }

        $data = [
            'nama_karyawan' => $this->request->getPost('nama_karyawan'),
            'username'      => $this->request->getPost('username'),
            'password'      => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'level'         => $this->request->getPost('level'),
            'no_telp'       => $this->request->getPost('no_telp'),
            'email'         => $this->request->getPost('email') ?: null,
            'gaji_pokok'    => $this->request->getPost('gaji_pokok') ?? 0,
        ];

        if ($data['email'] && !cekTypoEmail($data['email'])) {
            session()->setFlashdata('errors', ['email' => 'Domain email sepertinya salah ketik (typo). Pastikan menggunakan domain yang benar.']);
            return redirect()->to('/karyawan/create')->withInput();
        }

        if (session()->get('level') === 'admin' && in_array($data['level'], ['admin', 'pemilik'])) {
            session()->setFlashdata('errors', ['level' => 'Admin hanya dapat membuat akun dengan level Kasir.']);
            return redirect()->to('/karyawan/create')->withInput();
        }

        if (!$this->karyawanModel->save($data)) {
            session()->setFlashdata('errors', $this->karyawanModel->errors());
            return redirect()->to('/karyawan/create')->withInput();
        }

        log_aktivitas('karyawan', 'tambah', (int) $this->karyawanModel->getInsertID(), $data['nama_karyawan'], [
            'username' => $data['username'],
            'level'    => $data['level'],
            'no_telp'  => $data['no_telp'],
        ]);

        session()->setFlashdata('success', 'Data karyawan berhasil ditambahkan.');
        return redirect()->to('/karyawan');
    }

    public function edit($id)
    {
        $karyawan = $this->karyawanModel->find($id);

        if (session()->get('level') === 'admin' && in_array($karyawan['level'], ['admin', 'pemilik'])) {
            session()->setFlashdata('error', 'Admin hanya diizinkan untuk mengelola akun Kasir.');
            return redirect()->to('/karyawan');
        }

        $data = [
            'title'      => 'Edit Karyawan',
            'activeMenu' => 'karyawan',
            'karyawan'   => $karyawan,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('karyawan/edit', $data)
             . view('backend/template/footer');
    }

    public function update($id)
    {
        $data = [
            'nama_karyawan' => $this->request->getPost('nama_karyawan'),
            'username'      => $this->request->getPost('username'),
            'level'         => $this->request->getPost('level'),
            'no_telp'       => $this->request->getPost('no_telp'),
            'email'         => $this->request->getPost('email') ?: null,
            'gaji_pokok'    => $this->request->getPost('gaji_pokok') ?? 0,
            'id_karyawan'   => $id,  // Diperlukan untuk is_unique exception
        ];

        if ($data['email'] && !cekTypoEmail($data['email'])) {
            session()->setFlashdata('errors', ['email' => 'Domain email sepertinya salah ketik (typo). Pastikan menggunakan domain yang benar.']);
            return redirect()->to('/karyawan/edit/' . $id)->withInput();
        }

        $target = $this->karyawanModel->find($id);
        if (session()->get('level') === 'admin' && in_array($target['level'], ['admin', 'pemilik'])) {
            session()->setFlashdata('error', 'Admin tidak dapat mengedit akun Admin atau Pemilik.');
            return redirect()->to('/karyawan');
        }

        if (session()->get('level') === 'admin' && in_array($data['level'], ['admin', 'pemilik'])) {
            session()->setFlashdata('errors', ['level' => 'Admin tidak dapat mengubah level menjadi Admin atau Pemilik.']);
            return redirect()->to('/karyawan/edit/' . $id)->withInput();
        }

        $password = $this->request->getPost('password');
        $passwordError = password_validation_error(
            $password,
            $this->request->getPost('password_confirm'),
            false
        );
        if ($passwordError !== null) {
            session()->setFlashdata('errors', ['password' => $passwordError]);
            return redirect()->to('/karyawan/edit/' . $id)->withInput();
        }
        $passwordDiubah = ! empty($password);
        if ($passwordDiubah) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if (!$this->karyawanModel->save($data)) {
            session()->setFlashdata('errors', $this->karyawanModel->errors());
            return redirect()->to('/karyawan/edit/' . $id)->withInput();
        }

        $perubahan = detail_perubahan($target, $data, [
            'nama_karyawan', 'username', 'level', 'no_telp', 'email', 'gaji_pokok',
        ]);
        if ($passwordDiubah) {
            $perubahan['password'] = 'diubah';
        }
        log_aktivitas('karyawan', 'ubah', (int) $id, $data['nama_karyawan'], $perubahan);

        session()->setFlashdata('success', 'Data karyawan berhasil diupdate.');
        return redirect()->to('/karyawan');
    }


    public function toggleStatus($id)
    {
        $karyawan = $this->karyawanModel->find($id);
        
        if (session()->get('level') === 'admin' && in_array($karyawan['level'], ['admin', 'pemilik'])) {
            session()->setFlashdata('error', 'Admin tidak dapat mengubah status akun Admin atau Pemilik.');
            return redirect()->to('/karyawan');
        }

        if ($karyawan) {
            $statusBaru = ($karyawan['is_aktif'] == 1) ? 0 : 1;
            $this->karyawanModel->update($id, ['is_aktif' => $statusBaru]);
            $aksiLog = $statusBaru === 1 ? 'aktifkan' : 'nonaktifkan';
            log_aktivitas('karyawan', $aksiLog, (int) $id, $karyawan['nama_karyawan'], [
                'is_aktif' => ['dari' => $karyawan['is_aktif'], 'ke' => $statusBaru],
            ]);
            $pesan = ($statusBaru == 1) ? 'diaktifkan' : 'dinonaktifkan';
            session()->setFlashdata('success', "Karyawan berhasil $pesan.");
        }
        return redirect()->to('/karyawan');
    }

    // Endpoint live search tabel karyawan
    public function ajaxSearch()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses ditolak']);
        }

        $keyword    = $this->request->getGet('q');
        $sessionLevel = session()->get('level');
        
        $sort = $this->request->getGet('sort') ?? 'id_karyawan';
        $dir  = strtoupper($this->request->getGet('dir') ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $allowedSort = ['nama_karyawan', 'username', 'level', 'no_telp', 'gaji_pokok', 'is_aktif', 'id_karyawan'];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'id_karyawan';
        }

        $query      = $this->karyawanModel->orderBy($sort, $dir);

        if (!empty($keyword)) {
            $query->groupStart()
                  ->like('nama_karyawan', $keyword)
                  ->orLike('username', $keyword)
                  ->orLike('no_telp', $keyword)
                  ->orLike('level', $keyword)
                  ->groupEnd();
        }

        $items = $query->findAll(100);

        // Tentukan id karyawan mana yang boleh dikelola oleh session user
        $canManageIds = [];
        foreach ($items as $k) {
            if ($sessionLevel === 'pemilik') {
                $canManageIds[] = (string) $k['id_karyawan'];
            } elseif ($sessionLevel === 'admin' && $k['level'] === 'kasir') {
                $canManageIds[] = (string) $k['id_karyawan'];
            }
        }

        return $this->response->setJSON([
            'data'      => $items,
            'total'     => count($items),
            'canManage' => $canManageIds,
        ]);
    }
}