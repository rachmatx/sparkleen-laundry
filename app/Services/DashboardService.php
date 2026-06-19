<?php

namespace App\Services;

use App\Models\MTransaksi;
use App\Models\MPelanggan;
use App\Models\MKaryawan;
use App\Models\MInventaris;
use Config\StatusTransaksi;
use Config\StatusPembayaran;

class DashboardService
{
    protected $transaksiModel;
    protected $pelangganModel;
    protected $karyawanModel;
    protected $inventarisModel;
    protected $db;

    public function __construct()
    {
        $this->transaksiModel  = new MTransaksi();
        $this->pelangganModel  = new MPelanggan();
        $this->karyawanModel   = new MKaryawan();
        $this->inventarisModel = new MInventaris();
        $this->db              = \Config\Database::connect();
    }

    public function getChart7Hari()
    {
        $startDate = date('Y-m-d', strtotime('-6 days'));
        $endDate   = date('Y-m-d', strtotime('+1 day'));

        $rows = $this->db->table('tb_transaksi')
            ->select("DATE(tgl_masuk) as tanggal, SUM(grand_total) as total_pendapatan, COUNT(*) as jumlah_transaksi")
            ->where('tgl_masuk >=', $startDate)
            ->where('tgl_masuk <', $endDate)
            ->where('deleted_at IS NULL')
            ->groupBy('DATE(tgl_masuk)')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['tanggal']] = $r;
        }

        $chart7Hari = [];
        for ($i = 6; $i >= 0; $i--) {
            $tgl = date('Y-m-d', strtotime("-$i days"));
            $chart7Hari[] = [
                'tanggal'    => date('d/m', strtotime($tgl)),
                'pendapatan' => (int)($map[$tgl]['total_pendapatan'] ?? 0),
                'transaksi'  => (int)($map[$tgl]['jumlah_transaksi'] ?? 0),
            ];
        }
        return $chart7Hari;
    }

    public function getCucianJatuhTempo()
    {
        return $this->transaksiModel
            ->select('tb_transaksi.id_transaksi, tb_transaksi.no_nota, st.nama_status as status, tb_transaksi.tgl_selesai_estimasi, tb_pelanggan.nama_pelanggan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->whereNotIn('tb_transaksi.id_status_transaksi', [StatusTransaksi::SELESAI, StatusTransaksi::DIAMBIL])
            ->where('DATE(tb_transaksi.tgl_selesai_estimasi) <=', date('Y-m-d'))
            ->orderBy('tb_transaksi.tgl_selesai_estimasi', 'ASC')
            ->limit(10)
            ->findAll();
    }

    public function getKasirData()
    {
        $today = date('Y-m-d');

        // Single grouped query for all status counts
        $statusCounts = $this->db->table('tb_transaksi')
            ->select('id_status_transaksi, COUNT(*) as cnt')
            ->whereIn('id_status_transaksi', StatusTransaksi::DIPROSES)
            ->where('deleted_at IS NULL')
            ->groupBy('id_status_transaksi')
            ->get()->getResultArray();

        $statusMap = [];
        foreach ($statusCounts as $sc) {
            $statusMap[(int) $sc['id_status_transaksi']] = (int) $sc['cnt'];
        }

        $pesananMasukHariIni = $this->transaksiModel->where('DATE(tgl_masuk)', $today)->countAllResults();
        $statusMenunggu = $statusMap[StatusTransaksi::MENUNGGU] ?? 0;
        $statusProses   = $statusMap[StatusTransaksi::PROSES] ?? 0;
        $statusSetrika  = $statusMap[StatusTransaksi::SETRIKA] ?? 0;
        $totalDiproses = $statusMenunggu + $statusProses + $statusSetrika;

        $transaksiTerbaru = $this->transaksiModel
            ->select('tb_transaksi.id_transaksi, tb_transaksi.no_nota, tb_transaksi.grand_total, st.nama_status as status, sp.nama_status as status_pembayaran, tb_transaksi.tgl_masuk, tb_pelanggan.nama_pelanggan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->orderBy('tb_transaksi.id_transaksi', 'DESC')
            ->limit(5)
            ->findAll();

        return [
            'pesananMasukHariIni' => $pesananMasukHariIni,
            'statusMenunggu'      => $statusMenunggu,
            'statusProses'        => $statusProses,
            'statusSetrika'       => $statusSetrika,
            'totalDiproses'       => $totalDiproses,
            'transaksiTerbaru'    => $transaksiTerbaru,
            'cucianJatuhTempo'    => $this->getCucianJatuhTempo(),
        ];
    }

    public function getAdminData()
    {
        $today = date('Y-m-d');

        // Single query for today's count + sum
        $todayStats = $this->db->table('tb_transaksi')
            ->select('COUNT(*) as jumlah, SUM(grand_total) as total')
            ->where('DATE(tgl_masuk)', $today)
            ->where('deleted_at IS NULL')
            ->get()->getRowArray();

        $transaksiHariIni  = (int)($todayStats['jumlah'] ?? 0);
        $pendapatanHariIni = (int)($todayStats['total'] ?? 0);

        $stokMenipis = [];
        try {
            $stokMenipis = $this->inventarisModel->getStokKritis();
        } catch (\Throwable $e) {
            log_message('error', 'DashboardService::getAdminData - Failed to fetch critical stock data: ' . $e->getMessage());
        }

        $logAktivitas = [];
        try {
            $logAktivitas = $this->db->table('tb_log_aktivitas la')
                ->select('la.id_log, la.modul, la.aksi, la.nama_referensi, la.created_at, la.id_karyawan, k.nama_karyawan')
                ->join('tb_karyawan k', 'k.id_karyawan = la.id_karyawan', 'left')
                ->orderBy('la.created_at', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'DashboardService::getAdminData - Failed to fetch activity log: ' . $e->getMessage());
        }

        $transaksiTerbaru = $this->transaksiModel
            ->select('tb_transaksi.id_transaksi, tb_transaksi.no_nota, tb_transaksi.grand_total, st.nama_status as status, sp.nama_status as status_pembayaran, tb_transaksi.tgl_masuk, tb_pelanggan.nama_pelanggan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->orderBy('tb_transaksi.id_transaksi', 'DESC')
            ->limit(5)
            ->findAll();

        return [
            'transaksiHariIni'   => $transaksiHariIni,
            'pendapatanHariIni'  => $pendapatanHariIni,
            'chart7Hari'         => $this->getChart7Hari(),
            'stokMenipis'        => $stokMenipis,
            'logAktivitas'       => $logAktivitas,
            'transaksiTerbaru'   => $transaksiTerbaru,
            'cucianJatuhTempo'   => $this->getCucianJatuhTempo(),
        ];
    }

    public function getOwnerData()
    {
        $bulanIni  = date('Y-m');
        $bulanLalu = date('Y-m', strtotime('first day of last month'));

        $omzetBulanIni = $this->db->table('tb_transaksi')->selectSum('grand_total', 'total')->where("DATE_FORMAT(tgl_masuk, '%Y-%m')", $bulanIni)->get()->getRowArray();
        $omzetBulanIni = (int)($omzetBulanIni['total'] ?? 0);

        $omzetBulanLalu = $this->db->table('tb_transaksi')->selectSum('grand_total', 'total')->where("DATE_FORMAT(tgl_masuk, '%Y-%m')", $bulanLalu)->get()->getRowArray();
        $omzetBulanLalu = (int)($omzetBulanLalu['total'] ?? 0);

        $growthPersen = $omzetBulanLalu > 0 ? (($omzetBulanIni - $omzetBulanLalu) / $omzetBulanLalu) * 100 : ($omzetBulanIni > 0 ? 100 : 0);
        $growthNominal = $omzetBulanIni - $omzetBulanLalu;

        $totalGajiKaryawan = 0;
        try {
            $gajiRow = $this->db->table('tb_karyawan')->selectSum('gaji_pokok', 'total')->get()->getRowArray();
            $totalGajiKaryawan = (int)($gajiRow['total'] ?? 0);
        } catch (\Throwable $e) {
            log_message('error', 'DashboardService::getOwnerData - Failed to calculate total salaries: ' . $e->getMessage());
        }

        $totalBeliInventarisBulanIni = 0;
        try {
            $logs = $this->db->table('tb_log_aktivitas')->where('modul', 'inventaris')->whereIn('aksi', ['tambah', 'ubah'])->where("DATE_FORMAT(created_at, '%Y-%m')", $bulanIni)->get()->getResultArray();
            foreach ($logs as $log) {
                $detail = json_decode($log['detail'] ?? '', true);
                if (empty($detail)) continue;

                if ($log['aksi'] === 'tambah') {
                    $stok = (float)($detail['stok'] ?? 0);
                    $hargaBeli = (int)($detail['harga_beli'] ?? 0);
                    $totalBeliInventarisBulanIni += ($stok * $hargaBeli);
                } elseif ($log['aksi'] === 'ubah') {
                    if (isset($detail['stok']['dari']) && isset($detail['stok']['ke'])) {
                        $dari = (float)$detail['stok']['dari'];
                        $ke = (float)$detail['stok']['ke'];
                        if ($ke > $dari) {
                            $selisih = $ke - $dari;
                            $hargaBeli = isset($detail['harga_beli']['ke']) ? (int)$detail['harga_beli']['ke'] : (isset($detail['harga_beli']['dari']) ? (int)$detail['harga_beli']['dari'] : 0);
                            if ($hargaBeli == 0) {
                                $item = $this->db->table('tb_inventaris')->where('id_inventaris', $log['id_referensi'])->get()->getRowArray();
                                if ($item) $hargaBeli = (int)$item['harga_beli'];
                            }
                            $totalBeliInventarisBulanIni += ($selisih * $hargaBeli);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'DashboardService::getOwnerData - Failed to calculate inventory purchases: ' . $e->getMessage());
        }

        $keuntunganBersih = $omzetBulanIni - ($totalGajiKaryawan + $totalBeliInventarisBulanIni);

        $piutangStats = ['lunas' => 0, 'piutang' => 0];
        try {
            $piutangRow = $this->db->table('tb_transaksi')
                ->select("SUM(CASE WHEN id_status_pembayaran = " . StatusPembayaran::LUNAS . " THEN grand_total ELSE 0 END) as total_lunas, SUM(CASE WHEN id_status_pembayaran != " . StatusPembayaran::LUNAS . " THEN grand_total ELSE 0 END) as total_piutang")
                ->where("DATE_FORMAT(tgl_masuk, '%Y-%m')", $bulanIni)->get()->getRowArray();
            $piutangStats['lunas']   = (int)($piutangRow['total_lunas'] ?? 0);
            $piutangStats['piutang'] = (int)($piutangRow['total_piutang'] ?? 0);
        } catch (\Throwable $e) {
            log_message('error', 'DashboardService::getOwnerData - Failed to calculate receivables: ' . $e->getMessage());
        }

        $metodePembayaran = [];
        try {
            $metodePembayaran = $this->db->table('tb_pembayaran p')
                ->select('mp.nama_metode as metode_bayar, SUM(p.nominal_bayar) as total')
                ->join('tb_transaksi t', 't.id_transaksi = p.id_transaksi')
                ->join('tb_master_metode_pembayaran mp', 'mp.id = p.id_metode_pembayaran', 'left')
                ->where("DATE_FORMAT(t.tgl_masuk, '%Y-%m')", $bulanIni)
                ->groupBy('p.id_metode_pembayaran')->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'DashboardService::getOwnerData - Failed to fetch payment methods: ' . $e->getMessage());
        }

        $totalPelanggan = $this->pelangganModel->countAll();
        $pelangganBaruBulanIni = 0;
        try {
            $pelangganBaruBulanIni = $this->db->table('tb_pelanggan')->where("DATE_FORMAT(created_at, '%Y-%m')", $bulanIni)->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'DashboardService::getOwnerData - Failed to count new customers: ' . $e->getMessage());
        }

        $topLayanan = [];
        try {
            $topLayanan = $this->db->table('tb_detail_transaksi dt')->select('l.nama_layanan, SUM(dt.subtotal) as total_pendapatan, SUM(dt.berat) as total_berat, COUNT(dt.id_detail) as jumlah_order')->join('tb_layanan l', 'l.id_layanan = dt.id_layanan')->join('tb_transaksi t', 't.id_transaksi = dt.id_transaksi')->where("DATE_FORMAT(t.tgl_masuk, '%Y-%m')", $bulanIni)->groupBy('dt.id_layanan')->orderBy('total_pendapatan', 'DESC')->limit(5)->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'DashboardService::getOwnerData - Failed to fetch top services: ' . $e->getMessage());
        }

        $topKasir = [];
        try {
            $topKasir = $this->db->table('tb_transaksi t')->select('k.nama_karyawan, COUNT(t.id_transaksi) as jumlah_transaksi, SUM(t.grand_total) as total_omzet')->join('tb_karyawan k', 'k.id_karyawan = t.id_kasir')->where("DATE_FORMAT(t.tgl_masuk, '%Y-%m')", $bulanIni)->groupBy('t.id_kasir')->orderBy('jumlah_transaksi', 'DESC')->limit(5)->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'DashboardService::getOwnerData - Failed to fetch top cashiers: ' . $e->getMessage());
        }

        return [
            'omzetBulanIni'               => $omzetBulanIni,
            'omzetBulanLalu'              => $omzetBulanLalu,
            'growthPersen'                => round($growthPersen, 2),
            'growthNominal'               => $growthNominal,
            'totalGajiKaryawan'           => $totalGajiKaryawan,
            'totalBeliInventarisBulanIni' => $totalBeliInventarisBulanIni,
            'keuntunganBersih'            => $keuntunganBersih,
            'piutangStats'                => $piutangStats,
            'metodePembayaran'            => $metodePembayaran,
            'totalPelanggan'              => $totalPelanggan,
            'pelangganBaruBulanIni'       => $pelangganBaruBulanIni,
            'topLayanan'                  => $topLayanan,
            'topKasir'                    => $topKasir,
            'bulanIni'                    => date('F Y'),
            'bulanLaluLabel'              => date('F Y', strtotime('first day of last month')),
            'chart7Hari'                  => $this->getChart7Hari(),
        ];
    }
}
