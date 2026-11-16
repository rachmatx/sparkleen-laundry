<?php

namespace App\Controllers;

use App\Libraries\Mobile\ApiProblem;
use App\Services\PickupService;

class Penjemputan extends BaseController
{
    public function index()
    {
        $status = $this->request->getGet('status') ?? 'aktif';
        if (!in_array($status, ['aktif', 'menunggu', 'diproses', 'dikonfirmasi', 'ditolak', 'dibatalkan', 'semua'], true)) $status = 'aktif';
        $page = max(1, min(1000000, (int) ($this->request->getGet('page') ?? 1)));
        $query = db_connect()->table('tb_permintaan_jemput r')->select('r.*, p.nama_pelanggan, p.no_telp')
            ->join('tb_pelanggan p', 'p.id_pelanggan = r.id_pelanggan');
        if ($status === 'aktif') $query->whereIn('r.status', PickupService::ACTIVE);
        elseif ($status !== 'semua') $query->where('r.status', $status);
        $total = (clone $query)->countAllResults();
        $rows = $query->orderBy('r.tgl_jemput', 'ASC')->orderBy('r.id_permintaan', 'ASC')->get(20, ($page - 1) * 20)->getResultArray();
        $data = ['title' => 'Antrean Penjemputan', 'activeMenu' => 'penjemputan', 'rows' => $rows,
            'status' => $status, 'page' => $page, 'lastPage' => max(1, (int) ceil($total / 20)),
            'canEdit' => in_array(session()->get('level'), ['admin', 'kasir'], true)];
        return view('backend/template/header', $data) . view('backend/template/sidebar', $data)
            . view('penjemputan/index', $data) . view('backend/template/footer');
    }

    public function update($id, $action)
    {
        try {
            $weight = $this->request->getPost('berat_aktual');
            $result = (new PickupService())->act((int) $id, (int) session()->get('id_karyawan'), $action, [
                'berat_aktual' => is_string($weight) && is_numeric($weight) ? (float) $weight : null,
                'alasan_penolakan' => $this->request->getPost('alasan_penolakan'),
            ]);
            if ($action === 'konfirmasi') {
                return redirect()->to('/transaksi/nota/' . $result['id_transaksi'])->with('success', 'Cucian diterima. Transaksi dan nota sudah dibuat.');
            }
            return redirect()->to('/penjemputan')->with('success', 'Permintaan berhasil diperbarui.');
        } catch (ApiProblem $error) {
            $message = $error->getMessage();
            foreach ($error->fields as $messages) $message .= ' ' . implode(' ', $messages);
            return redirect()->to('/penjemputan')->with('error', $message);
        } catch (\Throwable $error) {
            log_message('error', 'Pickup queue failure: {type}', ['type' => get_class($error)]);
            return redirect()->to('/penjemputan')->with('error', 'Permintaan belum diperbarui. Silakan coba lagi.');
        }
    }
}
