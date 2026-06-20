<?php

namespace App\Controllers;

use App\Models\MPelanggan;
use App\Models\MLayanan;
use App\Models\MTransaksi;
use App\Models\MDetailTransaksi;
use App\Models\MTempCart;
use App\Models\MInventaris;
use App\Models\MPenggunaanInventaris;
use App\Models\MLayananInventaris;
use App\Models\MPembayaran;
use App\Models\MStatusLog;
use App\Models\MasterStatusTransaksiModel;
use App\Models\MasterMetodePembayaranModel;
use App\Services\TransaksiService;

class Transaksi extends BaseController
{
    use \App\Controllers\Traits\PaginationTrait;

    protected $pelangganModel;
    protected $layananModel;
    protected $transaksiModel;
    protected $detailModel;
    protected $tempModel;
    protected $inventarisModel;
    protected $penggunaanModel;
    protected $layananInventarisModel;
    protected $pembayaranModel;
    protected $statusLogModel;
    protected $level;
    protected $transaksiService;

    public function __construct()
    {
        $this->pelangganModel         = new MPelanggan();
        $this->layananModel           = new MLayanan();
        $this->transaksiModel         = new MTransaksi();
        $this->detailModel            = new MDetailTransaksi();
        $this->tempModel              = new MTempCart();
        $this->inventarisModel        = new MInventaris();
        $this->penggunaanModel        = new MPenggunaanInventaris();
        $this->layananInventarisModel = new MLayananInventaris();
        $this->pembayaranModel        = new MPembayaran();
        $this->statusLogModel         = new MStatusLog();
        $this->level                  = session()->get('level');
        $this->transaksiService       = new TransaksiService();
    }

    /**
     * Helper internal untuk memproses flashdata hasil notifikasi WhatsApp
     */
    private function _handleWaFlashdata($hasil, $pesanSukses = 'Pesan WA Terkirim.')
    {
        if ($hasil['success']) {
            session()->setFlashdata('wa_success', $pesanSukses);
        } else {
            if (!empty($hasil['wa_link'])) {
                session()->setFlashdata('wa_link', $hasil['wa_link']);
            }
            session()->setFlashdata('wa_warning', $hasil['message']);
        }
    }

    // Step 1: Halaman POS Utama (Single Page)
    public function index()
    {
        $id_kasir = session()->get('id_karyawan');
        $temp = $this->tempModel
            ->where('id_kasir', $id_kasir)
            ->where('id_pelanggan', null)
            ->findAll();

        $resepRekomendasi = $this->transaksiService->calculateCartRekomendasi($temp);

        $pos_id_pelanggan = session()->get('pos_id_pelanggan');
        $selectedPelanggan = [];
        if ($pos_id_pelanggan) {
            $p = $this->pelangganModel->find($pos_id_pelanggan);
            if ($p) $selectedPelanggan[] = $p;
        }

        $data = [
            'title'            => 'Transaksi Baru',
            'activeMenu'       => 'transaksi',
            'pelanggan'        => $selectedPelanggan,
            'layanan'          => $this->layananModel->where('is_aktif', 1)->findAll(),
            'inventaris'       => $this->inventarisModel ? $this->inventarisModel->where('is_aktif', 1)->findAll() : [],
            'temp'             => $temp,
            'resepRekomendasi' => $resepRekomendasi,
        ];

        return view('transaksi/create', $data);
    }

    // Tambah ke keranjang
    public function addToCart()
    {
        $id_layanan   = $this->request->getPost('id_layanan');
        $berat        = $this->request->getPost('berat');
        
        $id_pelanggan_state = $this->request->getPost('id_pelanggan_state');
        if(!empty($id_pelanggan_state)) session()->set('pos_id_pelanggan', $id_pelanggan_state);

        $rules = [
            'id_layanan' => 'required|numeric|is_not_unique[tb_layanan.id_layanan]',
            'berat'      => 'required|numeric|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            session()->setFlashdata('errors', $this->validator->getErrors());
            return redirect()->to('/transaksi')->withInput();
        }

        // Pastikan layanan aktif
        $layanan = $this->layananModel->find($id_layanan);
        if (!$layanan || $layanan['is_aktif'] != 1) {
            session()->setFlashdata('error', 'Layanan tidak valid atau sedang tidak aktif.');
            return redirect()->to('/transaksi')->withInput();
        }

        $this->tempModel->save([
            'id_pelanggan' => null,
            'id_kasir'     => session()->get('id_karyawan'),
            'id_layanan'   => $id_layanan,
            'berat'        => $berat,
        ]);

        session()->setFlashdata('success', 'Layanan ditambahkan ke keranjang.');
        return redirect()->to('/transaksi');
    }

    // Hapus dari keranjang
    public function removeFromCart($id_temp)
    {
        $id_pelanggan_state = $this->request->getGet('id_pelanggan_state');
        if(!empty($id_pelanggan_state)) session()->set('pos_id_pelanggan', $id_pelanggan_state);

        $this->tempModel->delete($id_temp);
        session()->setFlashdata('success', 'Item dihapus dari keranjang.');
        return redirect()->to('/transaksi');
    }

    // Simpan transaksi + catat penggunaan inventaris
    public function simpanTransaksi()
    {
        $id_pelanggan = $this->request->getPost('id_pelanggan');
        $id_kasir     = session()->get('id_karyawan');
        
        if (empty($id_pelanggan)) {
            session()->setFlashdata('error', 'Pelanggan belum dipilih!');
            return redirect()->to('/transaksi');
        }

        $items = $this->tempModel
            ->where('id_kasir', $id_kasir)
            ->where('id_pelanggan', null)
            ->findAll();

        if (empty($items)) {
            session()->setFlashdata('error', 'Keranjang masih kosong!');
            return redirect()->to('/transaksi');
        }

        // Validasi ketersediaan stok inventaris sebelum memproses transaksi
        if ($this->inventarisModel) {
            $stokValid = $this->transaksiService->validateStokInventaris($this->request->getPost());
            if (!$stokValid['valid']) {
                session()->setFlashdata('error', $stokValid['error_message']);
                return redirect()->to('/transaksi')->withInput();
            }
        }

        $pelanggan = $this->pelangganModel->find($id_pelanggan);
        
        // Panggil Service untuk memproses transaksi
        $result = $this->transaksiService->prosesTransaksiBaru(
            $this->request->getPost(), 
            $id_kasir, 
            $items, 
            $pelanggan
        );

        if (!$result['success']) {
            session()->setFlashdata('error', $result['message']);
            return redirect()->to('/transaksi');
        }

        $id_transaksi = $result['id_transaksi'];
        $pin_plain = $result['pin_pengambilan'] ?? '-';

        // Bersihkan keranjang
        $this->tempModel->where('id_kasir', $id_kasir)
            ->where('id_pelanggan', null)
            ->delete();

        // Bersihkan state session
        session()->remove('pos_id_pelanggan');

        // Kirim WhatsApp (Order Baru) jika diaktifkan
        try {
            $pelangganFresh = $this->pelangganModel->find($id_pelanggan);
            if ($pelangganFresh) {
                $transaksiFresh = $this->transaksiModel->find($id_transaksi);
                $transaksiFresh['pin_pengambilan'] = $pin_plain;
                $hasil = service('whatsapp')->notifyOrderBaru($transaksiFresh, $pelangganFresh);
                $this->_handleWaFlashdata($hasil, $hasil['message'] ?? 'Notifikasi WA Order Baru Terkirim');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal mengirim WA Order Baru: ' . $e->getMessage());
        }

        session()->setFlashdata('success', 'Transaksi berhasil disimpan!');
        session()->setFlashdata('pin_pengambilan_plain', $pin_plain);
        return redirect()->to('/transaksi/nota/' . $id_transaksi);
    }

    // Nota (standalone, tanpa AdminLTE layout)
    public function nota($id_transaksi)
    {
        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_karyawan.nama_karyawan as nama_kasir')
            ->join('tb_karyawan', 'tb_karyawan.id_karyawan = tb_transaksi.id_kasir')
            ->where('id_transaksi', $id_transaksi)
            ->first();

        if (!$transaksi) {
            session()->setFlashdata('error', 'Transaksi tidak ditemukan.');
            return redirect()->to('/transaksi/daftar');
        }

        $pelanggan = $this->pelangganModel->find($transaksi['id_pelanggan']);
        $detail    = $this->detailModel->where('id_transaksi', $id_transaksi)->findAll();

        foreach ($detail as &$d) {
            $d['layanan'] = $this->layananModel->find($d['id_layanan']);
        }

        $data = compact('transaksi', 'pelanggan', 'detail');
        return view('transaksi/nota', $data);
    }

    // Daftar transaksi
    public function daftar()
    {
        $status = $this->request->getGet('status') ?? 'semua';
        $keyword = $this->request->getGet('q');

        $pg = $this->parsePagination(
            ['no_nota', 'nama_pelanggan', 'tgl_masuk', 'total_berat', 'grand_total', 'status', 'status_pembayaran', 'id_transaksi'],
            'id_transaksi',
            'DESC'
        );

        $query = $this->transaksiModel->buildPencarianQuery($status, $keyword, $pg['sort'], $pg['dir']);

        $result = $this->paginateResults(
            $query->findAll($pg['limit'] + 1, $pg['offset']),
            $pg['limit']
        );

        $data = [
            'title'      => 'Daftar Transaksi',
            'activeMenu' => 'daftar_transaksi',
            'transaksi'  => $result['items'],
            'page'       => $pg['page'],
            'hasPrev'    => $pg['page'] > 1,
            'hasNext'    => $result['has_next'],
            'limit'      => $pg['limit'],
            'statusFilter'=> $status,
            'keyword'    => $keyword,
            'sort'       => $pg['sort'],
            'dir'        => $pg['dir'],
        ];

        return view('transaksi/daftar', $data);
    }

    // Detail transaksi
    public function detail($id_transaksi)
    {
        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, tb_pelanggan.no_telp, tb_karyawan.nama_karyawan,
                      st.nama_status as status, sp.nama_status as status_pembayaran, mp.nama_metode as metode_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_karyawan', 'tb_karyawan.id_karyawan = tb_transaksi.id_kasir')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->join('tb_master_metode_pembayaran mp', 'mp.id = tb_transaksi.id_metode_pembayaran', 'left')
            ->where('tb_transaksi.id_transaksi', $id_transaksi)
            ->first();

        if (!$transaksi) {
            session()->setFlashdata('error', 'Transaksi tidak ditemukan.');
            return redirect()->to('/transaksi/daftar');
        }

        $detail = $this->detailModel
            ->select('tb_detail_transaksi.*, tb_layanan.nama_layanan')
            ->join('tb_layanan', 'tb_layanan.id_layanan = tb_detail_transaksi.id_layanan')
            ->where('id_transaksi', $id_transaksi)
            ->findAll();

        $statusLog = $this->statusLogModel
            ->select('tb_status_log.*, tb_karyawan.nama_karyawan')
            ->join('tb_karyawan', 'tb_karyawan.id_karyawan = tb_status_log.id_karyawan', 'left')
            ->where('id_transaksi', $id_transaksi)
            ->orderBy('tgl_ubah', 'DESC')
            ->findAll();

        $penggunaanInventaris = $this->penggunaanModel->getByTransaksi($id_transaksi);

        $waLink = null;
        if ($transaksi && $transaksi['status'] === 'selesai') {
            $notifier = service('whatsapp');
            $phone    = $notifier->normalizePhone($transaksi['no_telp'] ?? '');
            if ($phone !== '') {
                $waLink = $notifier->buildWaMeLink($phone, $notifier->buildOrderSelesaiMessage(
                    $transaksi,
                    ['nama_pelanggan' => $transaksi['nama_pelanggan']]
                ));
            }
        }

        $data = [
            'title'                => 'Detail Transaksi',
            'activeMenu'           => 'daftar_transaksi',
            'transaksi'            => $transaksi,
            'detail'               => $detail,
            'statusLog'            => $statusLog,
            'penggunaanInventaris' => $penggunaanInventaris,
            'waLink'               => $waLink,
        ];

        return view('transaksi/detail', $data);
    }

    // Modal Quick View (AJAX)
    public function ajaxDetail($id_transaksi)
    {
        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, tb_pelanggan.no_telp, tb_karyawan.nama_karyawan,
                      st.nama_status as status, sp.nama_status as status_pembayaran, mp.nama_metode as metode_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_karyawan', 'tb_karyawan.id_karyawan = tb_transaksi.id_kasir')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->join('tb_master_metode_pembayaran mp', 'mp.id = tb_transaksi.id_metode_pembayaran', 'left')
            ->where('tb_transaksi.id_transaksi', $id_transaksi)
            ->first();

        if (!$transaksi) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Transaksi tidak ditemukan']);
        }

        $detail = $this->detailModel
            ->select('tb_detail_transaksi.*, tb_layanan.nama_layanan')
            ->join('tb_layanan', 'tb_layanan.id_layanan = tb_detail_transaksi.id_layanan')
            ->where('id_transaksi', $id_transaksi)
            ->findAll();

        $pembayaran = $this->pembayaranModel->getPembayaranByTransaksi($id_transaksi);

        return $this->response->setJSON([
            'status' => 'success',
            'transaksi' => $transaksi,
            'detail' => $detail,
            'pembayaran' => $pembayaran
        ]);
    }

    // Endpoint live search tabel daftar transaksi
    public function ajaxSearch()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses ditolak']);
        }

        $keyword = $this->request->getGet('q');
        $status  = $this->request->getGet('status') ?? 'semua';

        $pg = $this->parsePagination(
            ['no_nota', 'nama_pelanggan', 'tgl_masuk', 'total_berat', 'grand_total', 'status', 'status_pembayaran', 'id_transaksi'],
            'id_transaksi',
            'DESC'
        );

        $query = $this->transaksiModel->buildPencarianQuery($status, $keyword, $pg['sort'], $pg['dir']);
        $items = $query->findAll(100);

        // Format tanggal agar konsisten dengan tampilan tabel
        foreach ($items as &$t) {
            $t['tgl_masuk'] = date('d/m/Y H:i', strtotime($t['tgl_masuk']));
        }

        return $this->response->setJSON([
            'data'    => $items,
            'total'   => count($items),
            'canEdit' => in_array($this->level, ['admin', 'kasir']),
        ]);
    }

    // Form update status
    public function ubahStatus($id_transaksi)
    {
        $transaksi = $this->transaksiModel->getTransaksiWithJoins($id_transaksi);
        if (!$transaksi) {
            session()->setFlashdata('error', 'Transaksi tidak ditemukan.');
            return redirect()->to('/transaksi/daftar');
        }
        $is_pin_required = $this->transaksiService->checkPinRequired($id_transaksi, (float) $transaksi['grand_total']);

        $statusModel = new MasterStatusTransaksiModel();

        $data = [
            'title'           => 'Update Status Transaksi',
            'activeMenu'      => 'daftar_transaksi',
            'transaksi'       => $transaksi,
            'is_pin_required' => $is_pin_required,
            'status_list'     => $statusModel->findAll(),
        ];

        return view('transaksi/ubah_status', $data);
    }

    // Proses update status
    public function prosesUbahStatus()
    {
        $id_transaksi = (int) $this->request->getPost('id_transaksi');

        $postData = [
            'id_status_transaksi' => $this->request->getPost('id_status_transaksi'),
            'catatan'             => $this->request->getPost('catatan'),
            'kirim_wa'            => $this->request->getPost('kirim_wa'),
            'pin_pengambilan'     => $this->request->getPost('pin_pengambilan'),
            'id_karyawan'         => session()->get('id_karyawan'),
        ];

        try {
            $result = $this->transaksiService->prosesUbahStatus($id_transaksi, $postData, $this->level);
        } catch (\Throwable $e) {
            log_message('error', 'prosesUbahStatus error: ' . $e->getMessage());
            session()->setFlashdata('error', 'Terjadi kesalahan saat mengubah status.');
            return redirect()->to('/transaksi/ubah-status/' . $id_transaksi);
        }

        if (!$result['success']) {
            session()->setFlashdata('error', $result['message']);
            return redirect()->to('/transaksi/ubah-status/' . $id_transaksi);
        }

        // Handle WA flashdata jika ada
        if (!empty($result['wa_result'])) {
            $this->_handleWaFlashdata($result['wa_result'], $result['wa_result']['message'] ?? 'Pesan WA Terkirim');
        }

        session()->setFlashdata('success', $result['message']);
        return redirect()->to('/transaksi/detail/' . $id_transaksi);
    }

    // Form pembayaran
    public function pembayaran($id_transaksi)
    {
        $transaksi    = $this->transaksiModel->getTransaksiWithJoins($id_transaksi);
        $pelanggan    = $this->pelangganModel->find($transaksi['id_pelanggan']);
        $namaPelanggan = $pelanggan['nama_pelanggan'] ?? '-';

        $pembayaran = $this->pembayaranModel->getPembayaranByTransaksi($id_transaksi);

        $sisa = $this->transaksiService->hitungSisaTagihan($id_transaksi);

        $metodeModel = new MasterMetodePembayaranModel();

        $data = [
            'title'         => 'Pembayaran',
            'activeMenu'    => 'daftar_transaksi',
            'transaksi'     => $transaksi,
            'namaPelanggan' => $namaPelanggan,
            'pembayaran'    => $pembayaran,
            'sisa'          => $sisa,
            'metode_list'   => $metodeModel->findAll(),
            'qrCodeDataUri' => $this->transaksiService->generateQrCode($sisa, $transaksi['no_nota'] ?? '-'),
        ];

        return view('transaksi/pembayaran', $data);
    }

    // Simpan pembayaran
    public function simpanPembayaran()
    {
        if (!$this->validate([
            'id_transaksi'  => 'required|numeric',
            'nominal_bayar' => 'required|numeric|greater_than[0]',
            'id_metode_pembayaran'  => 'required|numeric'
        ])) {
            session()->setFlashdata('error', 'Input tidak valid. Pastikan nominal lebih dari 0 dan metode bayar valid.');
            return redirect()->to('/transaksi/pembayaran/' . $this->request->getPost('id_transaksi'));
        }

        $id_transaksi = (int) $this->request->getPost('id_transaksi');

        $result = $this->transaksiService->prosesPembayaran($id_transaksi, [
            'nominal_bayar'        => $this->request->getPost('nominal_bayar'),
            'id_metode_pembayaran' => $this->request->getPost('id_metode_pembayaran'),
        ]);

        if (!$result['success']) {
            session()->setFlashdata('error', $result['message']);
            return redirect()->to('/transaksi/pembayaran/' . $id_transaksi);
        }

        session()->setFlashdata('success', $result['message']);
        if (($result['kembalian'] ?? 0) > 0) {
            session()->setFlashdata('kembalian', $result['kembalian']);
        }

        return redirect()->to('/transaksi/pembayaran/' . $id_transaksi);
    }

    // Fitur kirim ulang WhatsApp via API
    public function kirimWaApi($id_transaksi, $tipe)
    {
        $transaksi = $this->transaksiModel->getTransaksiWithJoins($id_transaksi);
        if (!$transaksi) {
            session()->setFlashdata('error', 'Transaksi tidak ditemukan.');
            return redirect()->back();
        }

        $pelanggan = $this->pelangganModel->find($transaksi['id_pelanggan']);
        if (!$pelanggan) {
            session()->setFlashdata('error', 'Pelanggan tidak ditemukan.');
            return redirect()->back();
        }

        $notifier = service('whatsapp');
        $hasil = ['success' => false, 'message' => 'Tipe notifikasi tidak valid.', 'wa_link' => null];

        try {
            if ($tipe === 'baru' || $tipe === 'order_baru') {
                $hasil = $notifier->notifyOrderBaru($transaksi, $pelanggan);
            } elseif ($tipe === 'selesai' || $tipe === 'order_selesai') {
                $hasil = $notifier->notifyOrderSelesai($transaksi, $pelanggan);
            } elseif ($tipe === 'diambil' || $tipe === 'order_diambil') {
                $hasil = $notifier->notifyOrderDiambil($transaksi, $pelanggan);
            }

            if ($hasil['success']) {
                session()->setFlashdata('wa_success', 'Kirim Ulang Sukses: ' . $hasil['message']);
            } else {
                if (!empty($hasil['wa_link'])) {
                    session()->setFlashdata('wa_link', $hasil['wa_link']);
                }
                session()->setFlashdata('wa_warning', 'Gagal Kirim Ulang: ' . $hasil['message']);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal kirim ulang WA: ' . $e->getMessage());
            session()->setFlashdata('wa_warning', 'Terjadi kesalahan saat kirim ulang WA: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function ajaxCekPembayaran($id)
    {
        $transaksi = $this->transaksiModel->find($id);
        if (!$transaksi) return $this->response->setJSON(['sisa' => -1]);
        
        $totalDibayar = $this->pembayaranModel
            ->selectSum('nominal_bayar', 'total')
            ->where('id_transaksi', $id)
            ->first();
        $sudahDibayar = $totalDibayar['total'] ?? 0;
        $sisa         = (float) $transaksi['grand_total'] - $sudahDibayar;
        
        return $this->response->setJSON(['sisa' => $sisa, 'status' => $transaksi['id_status_pembayaran']]);
    }

    public function batalAPI($id)
    {
        $transaksi = $this->transaksiModel->find($id);
        if (!$transaksi) {
            return redirect()->to('/transaksi/pembayaran/' . $id)->with('error', 'Transaksi tidak ditemukan.');
        }

        // Hapus payment_ref dan payment_detail
        $this->transaksiModel->update($id, [
            'payment_ref' => null,
            'payment_detail' => null,
            // Jika mau kembalikan id_metode_pembayaran ke default (misal 1/Cash), buka komentar di bawah:
            // 'id_metode_pembayaran' => 1
        ]);

        return redirect()->to('/transaksi/pembayaran/' . $id)->with('success', 'Permintaan pembayaran API dibatalkan. Silakan pilih metode pembayaran lain.');
    }
}
