<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\StatusTransaksi;
use Config\StatusPembayaran;

class MTransaksi extends Model
{
    protected $table = 'tb_transaksi';
    protected $primaryKey = 'id_transaksi';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'no_nota', 'id_pelanggan', 'id_kasir', 'tgl_masuk',
        'tgl_selesai_estimasi', 'tgl_selesai_aktual', 'total_berat',
        'total_biaya', 'diskon', 'poin_ditebus', 'poin_diperoleh', 'grand_total', 'id_status_transaksi', 'id_status_pembayaran', 'id_metode_pembayaran', 'pin_pengambilan', 'payment_ref', 'payment_detail'
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
        // Map sort field ke qualified column agar tidak error saat sort by joined field
        $sortColumnMap = [
            'id_transaksi'       => 'tb_transaksi.id_transaksi',
            'no_nota'            => 'tb_transaksi.no_nota',
            'tgl_masuk'          => 'tb_transaksi.tgl_masuk',
            'total_berat'        => 'tb_transaksi.total_berat',
            'grand_total'        => 'tb_transaksi.grand_total',
            'nama_pelanggan'     => 'tb_pelanggan.nama_pelanggan',
            'status'             => 'st.nama_status',
            'status_pembayaran'  => 'sp.nama_status',
        ];
        $sortColumn = $sortColumnMap[$sort] ?? 'tb_transaksi.id_transaksi';

        $query = $this->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan,
                                st.nama_status as status,
                                sp.nama_status as status_pembayaran,
                                mp.nama_metode as metode_pembayaran')
                      ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
                      ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
                      ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
                      ->join('tb_master_metode_pembayaran mp', 'mp.id = tb_transaksi.id_metode_pembayaran', 'left')
                      ->orderBy($sortColumn, $dir);

        if (!empty($status) && $status !== 'semua') {
            if ($status === 'belum_diambil') {
                $query->whereIn('tb_transaksi.id_status_transaksi', StatusTransaksi::BELUM_DIAMBIL);
            } elseif ($status === 'belum_lunas') {
                $query->where('tb_transaksi.id_status_pembayaran !=', StatusPembayaran::LUNAS);
            } else {
                $query->where('st.nama_status', $status);
            }
        }

        if (!empty($keyword)) {
            $query->groupStart()
                  ->like('tb_transaksi.no_nota', $keyword)
                  ->orLike('tb_pelanggan.nama_pelanggan', $keyword)
                  ->orLike('tb_pelanggan.no_telp', $keyword)
                  ->orLike('tb_pelanggan.kode_pelanggan', $keyword)
                  ->groupEnd();
        }

        return $query;
    }
}