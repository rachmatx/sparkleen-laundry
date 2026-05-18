<?php

namespace App\Controllers;

use App\Models\MPelanggan;
use App\Models\MLayanan;
use App\Models\MTransaksi;
use App\Models\MDetailTransaksi;
use App\Models\MTempPeminjaman;

class Transaksi extends BaseController
{
    protected $pelangganModel;
    protected $layananModel;
    protected $transaksiModel;
    protected $detailModel;
    protected $tempModel;

    public function __construct()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $this->pelangganModel = new MPelanggan();
        $this->layananModel = new MLayanan();
        $this->transaksiModel = new MTransaksi();
        $this->detailModel = new MDetailTransaksi();
        $this->tempModel = new MTempPeminjaman();
    }

    // Step 1: Pilih pelanggan
    public function index()
    {
        $data['pelanggan'] = $this->pelangganModel->findAll();
        return view('transaksi/step1', $data);
    }

    // Step 2: Pilih layanan (setelah memilih pelanggan)
    public function pilihLayanan($id_pelanggan)
    {
        session()->set('id_pelanggan_transaksi', $id_pelanggan);
        
        $data['pelanggan'] = $this->pelangganModel->find($id_pelanggan);
        $data['layanan'] = $this->layananModel->findAll();  // ← Kirim semua layanan
        $data['temp'] = $this->tempModel
            ->where('id_pelanggan', $id_pelanggan)
            ->where('id_kasir', session()->get('id_karyawan'))
            ->findAll();
        
        return view('transaksi/step2', $data);
    }

    // Tambah ke keranjang
    public function addToCart()
    {
        $id_pelanggan = session()->get('id_pelanggan_transaksi');
        $id_layanan = $this->request->getPost('id_layanan');
        $berat = $this->request->getPost('berat');

        // Ambil harga layanan
        $layanan = $this->layananModel->find($id_layanan);
        $harga = $layanan['harga_per_kg'];
        $subtotal = $berat * $harga;

        $this->tempModel->save([
            'id_pelanggan' => $id_pelanggan,
            'id_kasir' => session()->get('id_karyawan'),
            'id_layanan' => $id_layanan,
            'berat' => $berat,
        ]);

        session()->setFlashdata('success', 'Layanan ditambahkan ke keranjang');
        return redirect()->to('/transaksi/pilih-layanan/' . $id_pelanggan);
    }

    // Hapus dari keranjang
    public function removeFromCart($id_temp)
    {
        $this->tempModel->delete($id_temp);
        session()->setFlashdata('success', 'Item dihapus dari keranjang');
        return redirect()->back();
    }

    // Simpan transaksi
    public function simpanTransaksi()
    {
        $id_pelanggan = session()->get('id_pelanggan_transaksi');
        $id_kasir = session()->get('id_karyawan');

        // Ambil semua item dari keranjang
        $items = $this->tempModel
            ->where('id_pelanggan', $id_pelanggan)
            ->where('id_kasir', $id_kasir)
            ->findAll();

        if (empty($items)) {
            session()->setFlashdata('error', 'Keranjang kosong!');
            return redirect()->to('/transaksi');
        }

        // Hitung total
        $total_berat = 0;
        $total_biaya = 0;

        foreach ($items as $item) {
            $layanan = $this->layananModel->find($item['id_layanan']);
            $harga = $layanan['harga_per_kg'];
            $subtotal = $item['berat'] * $harga;
            
            $total_berat += $item['berat'];
            $total_biaya += $subtotal;
        }

        // Estimasi selesai (H+2 dari tgl masuk)
        $estimasi = date('Y-m-d', strtotime('+2 days'));

        // Simpan ke tb_transaksi
        $dataTransaksi = [
            'no_nota' => $this->transaksiModel->generateNota(),
            'id_pelanggan' => $id_pelanggan,
            'id_kasir' => $id_kasir,
            'tgl_masuk' => date('Y-m-d'),
            'tgl_selesai_estimasi' => $estimasi,
            'total_berat' => $total_berat,
            'total_biaya' => $total_biaya,
            'grand_total' => $total_biaya,
            'status' => 'menunggu',
            'status_pembayaran' => 'belum',
        ];

        $this->transaksiModel->save($dataTransaksi);
        $id_transaksi = $this->transaksiModel->insertID();

        // Simpan ke tb_detail_transaksi
        foreach ($items as $item) {
            $layanan = $this->layananModel->find($item['id_layanan']);
            $harga = $layanan['harga_per_kg'];
            $subtotal = $item['berat'] * $harga;

            $this->detailModel->save([
                'id_transaksi' => $id_transaksi,
                'id_layanan' => $item['id_layanan'],
                'berat' => $item['berat'],
                'harga_satuan' => $harga,
                'subtotal' => $subtotal,
            ]);
        }

        // Hapus keranjang
        $this->tempModel->where('id_pelanggan', $id_pelanggan)
            ->where('id_kasir', $id_kasir)
            ->delete();

        session()->setFlashdata('success', 'Transaksi berhasil disimpan!');
        return redirect()->to('/transaksi/nota/' . $id_transaksi);
    }

    // Tampilkan nota
    public function nota($id_transaksi)
    {
        $transaksi = $this->transaksiModel->find($id_transaksi);
        $pelanggan = $this->pelangganModel->find($transaksi['id_pelanggan']);
        $detail = $this->detailModel->where('id_transaksi', $id_transaksi)->findAll();
        
        foreach ($detail as &$d) {
            $d['layanan'] = $this->layananModel->find($d['id_layanan']);
        }

        $data = [
            'transaksi' => $transaksi,
            'pelanggan' => $pelanggan,
            'detail' => $detail
        ];

        return view('transaksi/nota', $data);
    }

    // Menampilkan daftar semua transaksi
    public function daftar()
    {
        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->orderBy('tb_transaksi.id_transaksi', 'DESC')
            ->findAll();
        
        $data['transaksi'] = $transaksi;
        return view('transaksi/daftar', $data);
    }

    // Menampilkan detail transaksi
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
        
        $data = [
            'transaksi' => $transaksi,
            'detail' => $detail,
            'statusLog' => $statusLog
        ];
        
        return view('transaksi/detail', $data);
    }

    // Menampilkan form update status
    public function ubahStatus($id_transaksi)
    {
        $transaksi = $this->transaksiModel->find($id_transaksi);
        $data['transaksi'] = $transaksi;
        return view('transaksi/ubah_status', $data);
    }

    // Proses update status
    public function prosesUbahStatus()
    {
        $id_transaksi = $this->request->getPost('id_transaksi');
        $status_baru = $this->request->getPost('status');
        $catatan = $this->request->getPost('catatan');
        
        // Ambil status lama
        $transaksi = $this->transaksiModel->find($id_transaksi);
        $status_lama = $transaksi['status'];
        
        // Update status di tb_transaksi
        $this->transaksiModel->update($id_transaksi, [
            'status' => $status_baru
        ]);
        
        // Catat ke tb_status_log
        $this->transaksiModel->db->table('tb_status_log')->insert([
            'id_transaksi' => $id_transaksi,
            'status_lama' => $status_lama,
            'status_baru' => $status_baru,
            'tgl_ubah' => date('Y-m-d H:i:s'),
            'id_karyawan' => session()->get('id_karyawan'),
            'catatan' => $catatan
        ]);
        
        // Jika status selesai, update tgl_selesai_aktual
        if ($status_baru == 'selesai') {
            $this->transaksiModel->update($id_transaksi, [
                'tgl_selesai_aktual' => date('Y-m-d')
            ]);
        }
        
        session()->setFlashdata('success', 'Status berhasil diubah menjadi ' . ucfirst($status_baru));
        return redirect()->to('/transaksi/detail/' . $id_transaksi);
    }

    // Menampilkan form pembayaran
    public function pembayaran($id_transaksi)
    {
        $transaksi = $this->transaksiModel->find($id_transaksi);
        
        // Ambil nama pelanggan
        $pelanggan = $this->pelangganModel->find($transaksi['id_pelanggan']);
        $namaPelanggan = $pelanggan['nama_pelanggan'] ?? '-';
        
        // Ambil riwayat pembayaran
        $pembayaran = $this->transaksiModel->db->table('tb_pembayaran')
            ->where('id_transaksi', $id_transaksi)
            ->orderBy('tgl_bayar', 'DESC')
            ->get()
            ->getResultArray();
        
        // Hitung total sudah dibayar dan sisa
        $totalDibayar = $this->transaksiModel->db->table('tb_pembayaran')
            ->select('SUM(nominal_bayar) as total')
            ->where('id_transaksi', $id_transaksi)
            ->get()
            ->getRow();
        
        $sudahDibayar = $totalDibayar->total ?? 0;
        $sisa = $transaksi['grand_total'] - $sudahDibayar;
        
        $data = [
            'transaksi' => $transaksi,
            'namaPelanggan' => $namaPelanggan,
            'pembayaran' => $pembayaran,
            'sisa' => $sisa
        ];
        
        return view('transaksi/pembayaran', $data);
    }

    // Proses simpan pembayaran
    public function simpanPembayaran()
    {
        $id_transaksi = $this->request->getPost('id_transaksi');
        $nominal_bayar = $this->request->getPost('nominal_bayar');
        $metode_bayar = $this->request->getPost('metode_bayar');
        
        $transaksi = $this->transaksiModel->find($id_transaksi);
        
        // Hitung total sudah dibayar
        $totalDibayar = $this->transaksiModel->db->table('tb_pembayaran')
            ->select('SUM(nominal_bayar) as total')
            ->where('id_transaksi', $id_transaksi)
            ->get()
            ->getRow();
        
        $sudahDibayar = $totalDibayar->total ?? 0;
        $sisa = $transaksi['grand_total'] - $sudahDibayar;
        $sisaSetelah = $sisa - $nominal_bayar;
        
        // Simpan pembayaran
        $this->transaksiModel->db->table('tb_pembayaran')->insert([
            'id_transaksi' => $id_transaksi,
            'tgl_bayar' => date('Y-m-d'),
            'nominal_bayar' => $nominal_bayar,
            'sisa_tagihan' => $sisaSetelah,
            'metode_bayar' => $metode_bayar
        ]);
        
        // Update status pembayaran di tb_transaksi
        if ($sisaSetelah <= 0) {
            $statusPembayaran = 'lunas';
        } else {
            $statusPembayaran = 'cicil';
        }
        
        $this->transaksiModel->update($id_transaksi, [
            'status_pembayaran' => $statusPembayaran,
            'metode_pembayaran' => $metode_bayar
        ]);
        
        session()->setFlashdata('success', 'Pembayaran berhasil disimpan');
        return redirect()->to('/transaksi/pembayaran/' . $id_transaksi);
    }
}