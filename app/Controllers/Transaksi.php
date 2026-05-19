<?php

namespace App\Controllers;

use App\Models\MPelanggan;
use App\Models\MLayanan;
use App\Models\MTransaksi;
use App\Models\MDetailTransaksi;
use App\Models\MTempPeminjaman;
use App\Models\MInventaris;
use App\Models\MPenggunaanInventaris;

class Transaksi extends BaseController
{
    protected $pelangganModel;
    protected $layananModel;
    protected $transaksiModel;
    protected $detailModel;
    protected $tempModel;
    protected $inventarisModel;
    protected $penggunaanModel;

    public function __construct()
    {
        if (!session()->get('isLoggedIn')) {
            header('Location: ' . base_url('/login'));
            exit();
        }

        $this->pelangganModel  = new MPelanggan();
        $this->layananModel    = new MLayanan();
        $this->transaksiModel  = new MTransaksi();
        $this->detailModel     = new MDetailTransaksi();
        $this->tempModel       = new MTempPeminjaman();

        // Inventaris bersifat opsional — muat hanya jika tabel tersedia
        $this->inventarisModel = new MInventaris();
        $this->penggunaanModel = new MPenggunaanInventaris();
    }

    // Step 1: Pilih pelanggan
    public function index()
    {
        $data = [
            'title'      => 'Transaksi Baru',
            'activeMenu' => 'transaksi',
            'pelanggan'  => $this->pelangganModel->findAll(),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('transaksi/step1', $data)
             . view('backend/template/footer');
    }

    // Step 2: Pilih layanan + inventaris
    public function pilihLayanan($id_pelanggan)
    {
        session()->set('id_pelanggan_transaksi', $id_pelanggan);

        $data = [
            'title'      => 'Pilih Layanan',
            'activeMenu' => 'transaksi',
            'pelanggan'  => $this->pelangganModel->find($id_pelanggan),
            'layanan'    => $this->layananModel->findAll(),
            'inventaris' => $this->inventarisModel ? $this->inventarisModel->findAll() : [],
            'temp'       => $this->tempModel
                                ->where('id_pelanggan', $id_pelanggan)
                                ->where('id_kasir', session()->get('id_karyawan'))
                                ->findAll(),
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('transaksi/step2', $data)
             . view('backend/template/footer');
    }

    // Tambah ke keranjang
    public function addToCart()
    {
        $id_pelanggan = session()->get('id_pelanggan_transaksi');
        $id_layanan   = $this->request->getPost('id_layanan');
        $berat        = $this->request->getPost('berat');

        $layanan = $this->layananModel->find($id_layanan);

        $this->tempModel->save([
            'id_pelanggan' => $id_pelanggan,
            'id_kasir'     => session()->get('id_karyawan'),
            'id_layanan'   => $id_layanan,
            'berat'        => $berat,
        ]);

        session()->setFlashdata('success', 'Layanan ditambahkan ke keranjang.');
        return redirect()->to('/transaksi/pilih-layanan/' . $id_pelanggan);
    }

    // Hapus dari keranjang
    public function removeFromCart($id_temp)
    {
        $this->tempModel->delete($id_temp);
        session()->setFlashdata('success', 'Item dihapus dari keranjang.');
        return redirect()->back();
    }

    // Simpan transaksi + catat penggunaan inventaris
    public function simpanTransaksi()
    {
        $id_pelanggan = session()->get('id_pelanggan_transaksi');
        $id_kasir     = session()->get('id_karyawan');

        $items = $this->tempModel
            ->where('id_pelanggan', $id_pelanggan)
            ->where('id_kasir', $id_kasir)
            ->findAll();

        if (empty($items)) {
            session()->setFlashdata('error', 'Keranjang masih kosong!');
            return redirect()->to('/transaksi');
        }

        // Hitung total
        $total_berat = 0;
        $total_biaya = 0;

        foreach ($items as $item) {
            $layanan  = $this->layananModel->find($item['id_layanan']);
            $subtotal = $item['berat'] * $layanan['harga_per_kg'];
            $total_berat += $item['berat'];
            $total_biaya += $subtotal;
        }

        // Simpan transaksi
        $estimasiSelesai = $this->request->getPost('tgl_selesai_estimasi') ?? date('Y-m-d H:i:s', strtotime('+2 days'));

        $dataTransaksi = [
            'no_nota'              => $this->transaksiModel->generateNota(),
            'id_pelanggan'         => $id_pelanggan,
            'id_kasir'             => $id_kasir,
            'tgl_masuk'            => date('Y-m-d H:i:s'),
            'tgl_selesai_estimasi' => date('Y-m-d H:i:s', strtotime($estimasiSelesai)),
            'total_berat'          => $total_berat,
            'total_biaya'          => $total_biaya,
            'grand_total'          => $total_biaya,
            'status'               => 'menunggu',
            'status_pembayaran'    => 'belum',
        ];

        $this->transaksiModel->save($dataTransaksi);
        $id_transaksi = $this->transaksiModel->insertID();

        // Simpan detail layanan
        foreach ($items as $item) {
            $layanan  = $this->layananModel->find($item['id_layanan']);
            $subtotal = $item['berat'] * $layanan['harga_per_kg'];

            $this->detailModel->save([
                'id_transaksi' => $id_transaksi,
                'id_layanan'   => $item['id_layanan'],
                'berat'        => $item['berat'],
                'harga_satuan' => $layanan['harga_per_kg'],
                'subtotal'     => $subtotal,
            ]);
        }

        // Catat penggunaan inventaris & kurangi stok (jika inventaris tersedia)
        if ($this->inventarisModel && $this->penggunaanModel) {
            // Checkbox only submits when checked — get array of checked IDs
            $inventarisIds = $this->request->getPost('inv_id') ?? [];

            foreach ($inventarisIds as $id_inv) {
                $id_inv = (int)$id_inv;
                // Each item has its own named field: inv_jumlah_1, inv_ket_1, etc.
                $jumlah = (float)($this->request->getPost('inv_jumlah_' . $id_inv) ?? 0);
                $ket    = $this->request->getPost('inv_ket_' . $id_inv) ?? '';

                if ($id_inv > 0 && $jumlah > 0) {
                    $this->penggunaanModel->save([
                        'id_transaksi'  => $id_transaksi,
                        'id_inventaris' => $id_inv,
                        'jumlah_pakai'  => $jumlah,
                        'keterangan'    => $ket,
                    ]);
                    $this->inventarisModel->kurangiStok($id_inv, $jumlah);
                }
            }
        }

        // Bersihkan keranjang
        $this->tempModel->where('id_pelanggan', $id_pelanggan)
            ->where('id_kasir', $id_kasir)
            ->delete();

        session()->setFlashdata('success', 'Transaksi berhasil disimpan!');
        return redirect()->to('/transaksi/nota/' . $id_transaksi);
    }

    // Nota (standalone, tanpa AdminLTE layout)
    public function nota($id_transaksi)
    {
        $transaksi = $this->transaksiModel->find($id_transaksi);
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
        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->orderBy('tb_transaksi.id_transaksi', 'DESC')
            ->findAll();

        $data = [
            'title'      => 'Daftar Transaksi',
            'activeMenu' => 'daftar_transaksi',
            'transaksi'  => $transaksi,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('transaksi/daftar', $data)
             . view('backend/template/footer');
    }

    // Detail transaksi
    public function detail($id_transaksi)
    {
        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, tb_karyawan.nama_karyawan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_karyawan', 'tb_karyawan.id_karyawan = tb_transaksi.id_kasir')
            ->where('tb_transaksi.id_transaksi', $id_transaksi)
            ->first();

        $detail = $this->detailModel
            ->select('tb_detail_transaksi.*, tb_layanan.nama_layanan')
            ->join('tb_layanan', 'tb_layanan.id_layanan = tb_detail_transaksi.id_layanan')
            ->where('id_transaksi', $id_transaksi)
            ->findAll();

        $statusLog = $this->transaksiModel->db->table('tb_status_log')
            ->select('tb_status_log.*, tb_karyawan.nama_karyawan')
            ->join('tb_karyawan', 'tb_karyawan.id_karyawan = tb_status_log.id_karyawan')
            ->where('id_transaksi', $id_transaksi)
            ->orderBy('tgl_ubah', 'DESC')
            ->get()
            ->getResultArray();

        $penggunaanInventaris = $this->penggunaanModel->getByTransaksi($id_transaksi);

        $data = [
            'title'                => 'Detail Transaksi',
            'activeMenu'           => 'daftar_transaksi',
            'transaksi'            => $transaksi,
            'detail'               => $detail,
            'statusLog'            => $statusLog,
            'penggunaanInventaris' => $penggunaanInventaris,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('transaksi/detail', $data)
             . view('backend/template/footer');
    }

    // Form update status
    public function ubahStatus($id_transaksi)
    {
        $transaksi = $this->transaksiModel->find($id_transaksi);

        $data = [
            'title'      => 'Update Status Transaksi',
            'activeMenu' => 'daftar_transaksi',
            'transaksi'  => $transaksi,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('transaksi/ubah_status', $data)
             . view('backend/template/footer');
    }

    // Proses update status
    public function prosesUbahStatus()
    {
        $id_transaksi = $this->request->getPost('id_transaksi');
        $status_baru  = $this->request->getPost('status');
        $catatan      = $this->request->getPost('catatan');

        $transaksi   = $this->transaksiModel->find($id_transaksi);
        $status_lama = $transaksi['status'];

        $this->transaksiModel->update($id_transaksi, ['status' => $status_baru]);

        $this->transaksiModel->db->table('tb_status_log')->insert([
            'id_transaksi' => $id_transaksi,
            'status_lama'  => $status_lama,
            'status_baru'  => $status_baru,
            'tgl_ubah'     => date('Y-m-d H:i:s'),
            'id_karyawan'  => session()->get('id_karyawan'),
            'catatan'      => $catatan,
        ]);

        if ($status_baru == 'selesai') {
            $this->transaksiModel->update($id_transaksi, ['tgl_selesai_aktual' => date('Y-m-d')]);
        }

        session()->setFlashdata('success', 'Status berhasil diubah menjadi ' . ucfirst($status_baru));
        return redirect()->to('/transaksi/detail/' . $id_transaksi);
    }

    // Form pembayaran
    public function pembayaran($id_transaksi)
    {
        $transaksi    = $this->transaksiModel->find($id_transaksi);
        $pelanggan    = $this->pelangganModel->find($transaksi['id_pelanggan']);
        $namaPelanggan = $pelanggan['nama_pelanggan'] ?? '-';

        $pembayaran = $this->transaksiModel->db->table('tb_pembayaran')
            ->where('id_transaksi', $id_transaksi)
            ->orderBy('tgl_bayar', 'DESC')
            ->get()
            ->getResultArray();

        $totalDibayar = $this->transaksiModel->db->table('tb_pembayaran')
            ->select('SUM(nominal_bayar) as total')
            ->where('id_transaksi', $id_transaksi)
            ->get()
            ->getRow();

        $sudahDibayar = $totalDibayar->total ?? 0;
        $sisa         = $transaksi['grand_total'] - $sudahDibayar;

        $data = [
            'title'         => 'Pembayaran',
            'activeMenu'    => 'daftar_transaksi',
            'transaksi'     => $transaksi,
            'namaPelanggan' => $namaPelanggan,
            'pembayaran'    => $pembayaran,
            'sisa'          => $sisa,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('transaksi/pembayaran', $data)
             . view('backend/template/footer');
    }

    // Simpan pembayaran
    public function simpanPembayaran()
    {
        $id_transaksi  = $this->request->getPost('id_transaksi');
        $nominal_bayar = $this->request->getPost('nominal_bayar');
        $metode_bayar  = $this->request->getPost('metode_bayar');

        $transaksi    = $this->transaksiModel->find($id_transaksi);
        $totalDibayar = $this->transaksiModel->db->table('tb_pembayaran')
            ->select('SUM(nominal_bayar) as total')
            ->where('id_transaksi', $id_transaksi)
            ->get()->getRow();

        $sudahDibayar = $totalDibayar->total ?? 0;
        $sisa         = $transaksi['grand_total'] - $sudahDibayar;
        $sisaSetelah  = $sisa - $nominal_bayar;

        $this->transaksiModel->db->table('tb_pembayaran')->insert([
            'id_transaksi'  => $id_transaksi,
            'tgl_bayar'     => date('Y-m-d'),
            'nominal_bayar' => $nominal_bayar,
            'sisa_tagihan'  => $sisaSetelah,
            'metode_bayar'  => $metode_bayar,
        ]);

        $statusPembayaran = ($sisaSetelah <= 0) ? 'lunas' : 'cicil';

        $this->transaksiModel->update($id_transaksi, [
            'status_pembayaran' => $statusPembayaran,
            'metode_pembayaran' => $metode_bayar,
        ]);

        session()->setFlashdata('success', 'Pembayaran berhasil disimpan.');
        return redirect()->to('/transaksi/pembayaran/' . $id_transaksi);
    }
}