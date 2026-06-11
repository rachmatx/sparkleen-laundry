<?php

namespace App\Models;

use CodeIgniter\Model;

class MTransaksi extends Model
{
    protected $table = 'tb_transaksi';
    protected $primaryKey = 'id_transaksi';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'no_nota', 'id_pelanggan', 'id_kasir', 'tgl_masuk',
        'tgl_selesai_estimasi', 'tgl_selesai_aktual', 'total_berat',
        'total_biaya', 'diskon', 'poin_ditebus', 'poin_diperoleh', 'grand_total', 'id_status_transaksi', 'id_status_pembayaran', 'id_metode_pembayaran', 'pin_pengambilan'
    ];
    protected $useTimestamps = true;

    public function getTransaksiWithJoins($id)
    {
        return $this->select('tb_transaksi.*, 
            st.nama_status as status, 
            sp.nama_status as status_pembayaran, 
            mp.nama_metode as metode_pembayaran')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->join('tb_master_metode_pembayaran mp', 'mp.id = tb_transaksi.id_metode_pembayaran', 'left')
            ->where('tb_transaksi.id_transaksi', $id)
            ->first();
    }

    // Generate nomor nota otomatis
    public function generateNota()
    {
        $tanggal = date('Ymd');
        $builder = $this->db->table($this->table);
        $builder->selectMax('id_transaksi');
        $query = $builder->get();
        $row = $query->getRow();
        $urut = ($row->id_transaksi ?? 0) + 1;
        return 'LAU/' . $tanggal . '/' . str_pad($urut, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Membangun query dasar untuk pencarian dan filter transaksi
     */
    public function buildPencarianQuery($status = 'semua', $keyword = '', $sort = 'id_transaksi', $dir = 'DESC')
    {
        $query = $this->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, 
                                st.nama_status as status, 
                                sp.nama_status as status_pembayaran, 
                                mp.nama_metode as metode_pembayaran')
                      ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
                      ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
                      ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
                      ->join('tb_master_metode_pembayaran mp', 'mp.id = tb_transaksi.id_metode_pembayaran', 'left')
                      ->orderBy('tb_transaksi.' . $sort, $dir);

        if (!empty($status) && $status !== 'semua') {
            if ($status === 'belum_diambil') {
                $query->whereIn('tb_transaksi.id_status_transaksi', [1, 2, 3, 4]); // 1: menunggu, 2: proses, 3: setrika, 4: selesai
            } elseif ($status === 'belum_lunas') {
                $query->where('tb_transaksi.id_status_pembayaran !=', 1); // 1: lunas
            } else {
                $query->where('st.nama_status', $status);
            }
        }

        if (!empty($keyword)) {
            $query->groupStart()
                  ->like('tb_transaksi.no_nota', $keyword)
                  ->orLike('tb_pelanggan.nama_pelanggan', $keyword)
                  ->groupEnd();
        }

        return $query;
    }

    /**
     * Memproses logika transaksi baru, memotong stok, mengatur diskon poin, dsb.
     * Mengembalikan array response.
     */
    public function prosesTransaksiBaru($postData, $id_kasir, $itemsTemp, $pelanggan)
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $layananModel = new \App\Models\MLayanan();
            $detailModel = new \App\Models\MDetailTransaksi();
            $inventarisModel = new \App\Models\MInventaris();
            $penggunaanModel = new \App\Models\MPenggunaanInventaris();
            $pelangganModel = new \App\Models\MPelanggan();

            // Hitung total
            $total_berat = 0;
            $total_biaya = 0;

            foreach ($itemsTemp as $item) {
                $layanan  = $layananModel->find($item['id_layanan']);
                $subtotal = $item['berat'] * $layanan['harga_per_kg'];
                $total_berat += $item['berat'];
                $total_biaya += $subtotal;
            }

            // Simpan transaksi
            $estimasiSelesai = $postData['tgl_selesai_estimasi'] ?? date('Y-m-d H:i:s', strtotime('+2 days'));
            
            // Perhitungan Poin & Diskon
            $poin_ditebus = (int)($postData['poin_ditebus'] ?? 0);
            $poin_tersedia = (int)($pelanggan['poin'] ?? 0);
            if ($poin_ditebus > $poin_tersedia) {
                $poin_ditebus = $poin_tersedia;
            }
            if ($poin_ditebus < 0) {
                $poin_ditebus = 0;
            }

            $diskon = $poin_ditebus * 300;
            if ($diskon > $total_biaya) {
                $diskon = $total_biaya;
                $poin_ditebus = (int)ceil($diskon / 300);
            }

            $grand_total = $total_biaya - $diskon;
            if ($grand_total < 0) {
                $grand_total = 0;
            }

            // Perolehan poin baru
            $poin_diperoleh = (int)floor($grand_total / 10000);

            $pin_plain = (string)random_int(1000, 9999);
            $dataTransaksi = [
                'id_pelanggan'         => $pelanggan['id_pelanggan'],
                'id_kasir'             => $id_kasir,
                'tgl_masuk'            => date('Y-m-d H:i:s'),
                'tgl_selesai_estimasi' => date('Y-m-d H:i:s', strtotime($estimasiSelesai)),
                'total_berat'          => $total_berat,
                'total_biaya'          => $total_biaya,
                'diskon'               => $diskon,
                'poin_ditebus'         => $poin_ditebus,
                'poin_diperoleh'       => $poin_diperoleh,
                'grand_total'          => $grand_total,
                'id_status_transaksi'  => 1, // 1: menunggu
                'id_status_pembayaran' => 3, // 3: belum
                'pin_pengambilan'      => $pin_plain,
            ];

            $this->save($dataTransaksi);
            $id_transaksi = $this->insertID();

            $tanggal = date('Ymd');
            $no_nota = 'LAU/' . $tanggal . '/' . str_pad($id_transaksi, 4, '0', STR_PAD_LEFT);
            $this->update($id_transaksi, ['no_nota' => $no_nota]);

            // Update poin pelanggan
            if ($pelanggan) {
                $poin_baru = $poin_tersedia - $poin_ditebus + $poin_diperoleh;
                $total_transaksi_baru = (float)($pelanggan['total_transaksi'] ?? 0) + $grand_total;
                $pelangganModel->update($pelanggan['id_pelanggan'], [
                    'poin'            => $poin_baru,
                    'total_transaksi' => $total_transaksi_baru
                ]);
            }

            // Simpan detail layanan
            foreach ($itemsTemp as $item) {
                $layanan  = $layananModel->find($item['id_layanan']);
                $subtotal = $item['berat'] * $layanan['harga_per_kg'];

                $detailModel->save([
                    'id_transaksi' => $id_transaksi,
                    'id_layanan'   => $item['id_layanan'],
                    'berat'        => $item['berat'],
                    'harga_satuan' => $layanan['harga_per_kg'],
                    'subtotal'     => $subtotal,
                ]);
            }

            // Catat penggunaan inventaris & kurangi stok
            $inventarisIds = $postData['inv_id'] ?? [];
            foreach ($inventarisIds as $id_inv) {
                $id_inv = (int)$id_inv;
                $jumlah = (float)($postData['inv_jumlah_' . $id_inv] ?? 0);
                $ket    = $postData['inv_ket_' . $id_inv] ?? '';

                if ($id_inv > 0 && $jumlah > 0) {
                    $penggunaanModel->save([
                        'id_transaksi'  => $id_transaksi,
                        'id_inventaris' => $id_inv,
                        'jumlah_pakai'  => $jumlah,
                        'keterangan'    => $ket,
                    ]);
                    $inventarisModel->kurangiStok($id_inv, $jumlah);
                }
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return ['success' => false, 'message' => 'Gagal memproses transaksi ke database.'];
            }

            $db->transCommit();
            return ['success' => true, 'id_transaksi' => $id_transaksi, 'pin_pengambilan' => $pin_plain];

        } catch (\Exception $e) {
            $db->transRollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}