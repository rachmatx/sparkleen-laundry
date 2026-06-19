<?php

namespace App\Controllers;

use App\Services\KaryawanService;

class Karyawan extends BaseController
{
    use \App\Controllers\Traits\PaginationTrait;

    protected $karyawanService;
    protected $level;

    public function __construct()
    {
        $this->karyawanService = new KaryawanService();
        $this->level           = session()->get('level');
    }

    public function index()
    {
        $pg = $this->parsePagination(
            ['nama_karyawan', 'username', 'level', 'no_telp', 'gaji_pokok', 'is_aktif', 'id_karyawan'],
            'id_karyawan',
            'ASC'
        );

        $result = $this->karyawanService->getDaftarKaryawan($pg['sort'], $pg['dir'], $pg['limit'], $pg['offset']);

        $data = [
            'title'      => 'Data Karyawan',
            'activeMenu' => 'karyawan',
            'karyawan'   => $result['items'],
            'page'       => $pg['page'],
            'hasPrev'    => $pg['page'] > 1,
            'hasNext'    => $result['has_next'],
            'limit'      => $pg['limit'],
            'sort'       => $pg['sort'],
            'dir'        => $pg['dir'],
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
            'level_list' => $this->karyawanService->getLevelList(),
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

        $data = $this->karyawanService->prepareKaryawanData($this->request->getPost(), true);

        if ($data['email'] && !cekTypoEmail($data['email'])) {
            session()->setFlashdata('errors', ['email' => 'Domain email sepertinya salah ketik (typo). Pastikan menggunakan domain yang benar.']);
            return redirect()->to('/karyawan/create')->withInput();
        }

        $adminError = $this->karyawanService->validateAdminAccess($this->level, (int) $data['id_level']);
        if ($adminError !== null) {
            session()->setFlashdata('errors', ['id_level' => 'Admin hanya dapat membuat akun dengan level Kasir.']);
            return redirect()->to('/karyawan/create')->withInput();
        }

        if (!$this->karyawanService->saveKaryawan($data)) {
            session()->setFlashdata('errors', $this->karyawanService->getErrors());
            return redirect()->to('/karyawan/create')->withInput();
        }

        $this->karyawanService->logKaryawanChange('tambah', $this->karyawanService->getInsertID(), $data['nama_karyawan'], [
            'username' => $data['username'],
            'id_level' => $data['id_level'],
            'no_telp'  => $data['no_telp'],
        ]);

        session()->setFlashdata('success', 'Data karyawan berhasil ditambahkan.');
        return redirect()->to('/karyawan');
    }

    public function edit($id)
    {
        $karyawan = $this->karyawanService->getKaryawanWithLevel($id);

        if ($karyawan === null) {
            session()->setFlashdata('error', 'Data karyawan tidak ditemukan.');
            return redirect()->to('/karyawan');
        }

        $adminError = $this->karyawanService->validateAdminManageTarget($this->level, $karyawan['level']);
        if ($adminError !== null) {
            session()->setFlashdata('error', 'Admin hanya diizinkan untuk mengelola akun Kasir.');
            return redirect()->to('/karyawan');
        }

        $data = [
            'title'      => 'Edit Karyawan',
            'activeMenu' => 'karyawan',
            'karyawan'   => $karyawan,
            'level_list' => $this->karyawanService->getLevelList(),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('karyawan/edit', $data)
             . view('backend/template/footer');
    }

    public function update($id)
    {
        $postData = array_merge($this->request->getPost(), ['id_karyawan' => $id]);
        $data = $this->karyawanService->prepareKaryawanData($postData, false);

        if ($data['email'] && !cekTypoEmail($data['email'])) {
            session()->setFlashdata('errors', ['email' => 'Domain email sepertinya salah ketik (typo). Pastikan menggunakan domain yang benar.']);
            return redirect()->to('/karyawan/edit/' . $id)->withInput();
        }

        $target = $this->karyawanService->getKaryawanWithLevel($id);
        $adminError = $this->karyawanService->validateAdminManageTarget($this->level, $target['level']);
        if ($adminError !== null) {
            session()->setFlashdata('error', 'Admin tidak dapat mengedit akun Admin atau Pemilik.');
            return redirect()->to('/karyawan');
        }

        $levelError = $this->karyawanService->validateAdminAccess($this->level, (int) $data['id_level']);
        if ($levelError !== null) {
            session()->setFlashdata('errors', ['id_level' => 'Admin tidak dapat mengubah level menjadi Admin atau Pemilik.']);
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

        $passwordDiubah = !empty($password);
        if (!$passwordDiubah) {
            unset($data['password']);
        }

        if (!$this->karyawanService->saveKaryawan($data)) {
            session()->setFlashdata('errors', $this->karyawanService->getErrors());
            return redirect()->to('/karyawan/edit/' . $id)->withInput();
        }

        $perubahan = detail_perubahan($target, $data, [
            'nama_karyawan', 'username', 'id_level', 'no_telp', 'email', 'gaji_pokok',
        ]);
        if ($passwordDiubah) {
            $perubahan['password'] = 'diubah';
        }
        $this->karyawanService->logKaryawanChange('ubah', (int) $id, $data['nama_karyawan'], $perubahan);

        session()->setFlashdata('success', 'Data karyawan berhasil diupdate.');
        return redirect()->to('/karyawan');
    }

    public function toggleStatus($id)
    {
        $karyawan = $this->karyawanService->getKaryawanWithLevel($id);

        $adminError = $this->karyawanService->validateAdminManageTarget($this->level, $karyawan['level']);
        if ($adminError !== null) {
            session()->setFlashdata('error', 'Admin tidak dapat mengubah status akun Admin atau Pemilik.');
            return redirect()->to('/karyawan');
        }

        if ($karyawan) {
            $statusBaru = ($karyawan['is_aktif'] == 1) ? 0 : 1;
            $this->karyawanService->updateStatus($id, $statusBaru);
            $aksiLog = $statusBaru === 1 ? 'aktifkan' : 'nonaktifkan';
            $this->karyawanService->logKaryawanChange($aksiLog, (int) $id, $karyawan['nama_karyawan'], [
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

        $keyword = $this->request->getGet('q');
        $pg = $this->parsePagination(
            ['nama_karyawan', 'username', 'level', 'no_telp', 'gaji_pokok', 'is_aktif', 'id_karyawan'],
            'id_karyawan',
            'ASC'
        );

        $items = $this->karyawanService->searchKaryawan($keyword ?? '', $pg['sort'], $pg['dir']);
        $canManageIds = $this->karyawanService->getCanManageIds($items, $this->level);

        return $this->response->setJSON([
            'data'      => $items,
            'total'     => count($items),
            'canManage' => $canManageIds,
        ]);
    }
}
