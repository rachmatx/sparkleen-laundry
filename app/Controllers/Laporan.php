<?php

namespace App\Controllers;

use App\Models\MTransaksi;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class Laporan extends BaseController
{
    protected $transaksiModel;

    public function __construct()
    {


        $this->transaksiModel = new MTransaksi();
    }

    // Halaman utama laporan
    public function index()
    {
        $data = [
            'title'      => 'Laporan',
            'activeMenu' => 'laporan',
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('laporan/index', $data)
             . view('backend/template/footer');
    }

    // Laporan Bulanan (Web)
    public function bulanan()
    {
        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        $page = (int)($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $items = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, st.nama_status as status, sp.nama_status as status_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->orderBy('tgl_masuk', 'ASC')
            ->findAll($limit + 1, $offset);

        $hasNext = count($items) > $limit;
        if ($hasNext) {
            array_pop($items);
        }
        $hasPrev = $page > 1;

        $totalPendapatan = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->first();

        $pendapatanPerHari = $this->transaksiModel
            ->select('DATE(tgl_masuk) as tanggal, SUM(grand_total) as total')
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->groupBy('DATE(tgl_masuk)')
            ->orderBy('tanggal', 'ASC')
            ->findAll();

        $jumlahTransaksi = $this->transaksiModel
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->countAllResults();

        $data = [
            'title'             => 'Laporan Bulanan',
            'activeMenu'        => 'laporan_bulanan',
            'transaksi'         => $items,
            'page'              => $page,
            'hasPrev'           => $hasPrev,
            'hasNext'           => $hasNext,
            'limit'             => $limit,
            'bulan'             => $bulan,
            'tahun'             => $tahun,
            'totalPendapatan'   => $totalPendapatan['total'] ?? 0,
            'jumlahTransaksi'   => $jumlahTransaksi,
            'pendapatanPerHari' => $pendapatanPerHari,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('laporan/bulanan', $data)
             . view('backend/template/footer');
    }

    public function export_bulanan_pdf()
    {
        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, st.nama_status as status, sp.nama_status as status_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->findAll();

        $totalPendapatan = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->first();

        $namaBulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April',   '05' => 'Mei',       '06' => 'Juni',
            '07' => 'Juli',    '08' => 'Agustus',   '09' => 'September',
            '10' => 'Oktober', '11' => 'November',  '12' => 'Desember',
        ];

        $data = [
            'transaksi'       => $transaksi,
            'bulan'           => $bulan,
            'tahun'           => $tahun,
            'namaBulan'       => $namaBulan[$bulan],
            'totalPendapatan' => $totalPendapatan['total'] ?? 0,
            'jumlahTransaksi' => count($transaksi),
        ];

        $html = view('laporan/export_bulanan_pdf', $data);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('Laporan_Bulanan_' . $namaBulan[$bulan] . '_' . $tahun . '.pdf', ['Attachment' => false]);
        exit();
    }

    public function export_bulanan_excel()
    {
        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, st.nama_status as status, sp.nama_status as status_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->findAll();

        $totalPendapatan = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->first();

        $namaBulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April',   '05' => 'Mei',       '06' => 'Juni',
            '07' => 'Juli',    '08' => 'Agustus',   '09' => 'September',
            '10' => 'Oktober', '11' => 'November',  '12' => 'Desember',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN BULANAN SPARKLEEN LAUNDRY');
        $sheet->setCellValue('A2', 'Bulan: ' . $namaBulan[$bulan] . ' ' . $tahun);
        $sheet->setCellValue('A3', 'Total Pendapatan: Rp ' . number_format($totalPendapatan['total'] ?? 0, 0, ',', '.'));
        $sheet->setCellValue('A4', 'Jumlah Transaksi: ' . count($transaksi));

        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');
        $sheet->mergeCells('A4:I4');
        $sheet->getStyle('A1:A4')->getFont()->setBold(true);

        $headers = ['No', 'No Nota', 'Pelanggan', 'Tgl Masuk', 'Tgl Selesai', 'Total Berat', 'Grand Total', 'Status', 'Status Bayar'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '6', $header);
            $sheet->getStyle($col . '6')->getFont()->setBold(true);
            $sheet->getStyle($col . '6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        $row = 7;
        $no  = 1;
        foreach ($transaksi as $t) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $t['no_nota']);
            $sheet->setCellValue('C' . $row, $t['nama_pelanggan']);
            $sheet->setCellValue('D' . $row, date('d/m/Y', strtotime($t['tgl_masuk'])));
            $sheet->setCellValue('E' . $row, $t['tgl_selesai_aktual'] ? date('d/m/Y', strtotime($t['tgl_selesai_aktual'])) : '-');
            $sheet->setCellValue('F' . $row, $t['total_berat'] . ' kg');
            $sheet->setCellValue('G' . $row, $t['grand_total']);
            $sheet->setCellValue('H' . $row, ucfirst($t['status']));
            $sheet->setCellValue('I' . $row, ucfirst($t['status_pembayaran']));
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle('A6:I' . ($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Laporan_Bulanan_' . $namaBulan[$bulan] . '_' . $tahun . '.xlsx"');
        $writer->save('php://output');
        exit();
    }

    // ==================== RENTANG TANGGAL ====================

    public function rentang()
    {
        $tgl_awal = $this->request->getGet('tgl_awal') ?? date('Y-m-01');
        $tgl_akhir = $this->request->getGet('tgl_akhir') ?? date('Y-m-d');

        $page = (int)($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $items = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, st.nama_status as status, sp.nama_status as status_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->where('DATE(tgl_masuk) >=', $tgl_awal)
            ->where('DATE(tgl_masuk) <=', $tgl_akhir)
            ->orderBy('tgl_masuk', 'ASC')
            ->findAll($limit + 1, $offset);

        $hasNext = count($items) > $limit;
        if ($hasNext) {
            array_pop($items);
        }
        $hasPrev = $page > 1;

        $totalPendapatan = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('DATE(tgl_masuk) >=', $tgl_awal)
            ->where('DATE(tgl_masuk) <=', $tgl_akhir)
            ->first();

        $totalBerat = $this->transaksiModel
            ->select('SUM(total_berat) as berat')
            ->where('DATE(tgl_masuk) >=', $tgl_awal)
            ->where('DATE(tgl_masuk) <=', $tgl_akhir)
            ->first();

        $jumlahTransaksi = $this->transaksiModel
            ->where('DATE(tgl_masuk) >=', $tgl_awal)
            ->where('DATE(tgl_masuk) <=', $tgl_akhir)
            ->countAllResults();

        $data = [
            'title'            => 'Laporan Rentang Tanggal',
            'activeMenu'       => 'laporan_rentang',
            'transaksi'        => $items,
            'page'             => $page,
            'hasPrev'          => $hasPrev,
            'hasNext'          => $hasNext,
            'limit'            => $limit,
            'tgl_awal'         => $tgl_awal,
            'tgl_akhir'        => $tgl_akhir,
            'totalPendapatan'  => $totalPendapatan['total'] ?? 0,
            'jumlahTransaksi'  => $jumlahTransaksi,
            'totalBerat'       => $totalBerat['berat'] ?? 0,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('laporan/rentang', $data)
             . view('backend/template/footer');
    }

    public function export_rentang_pdf()
    {
        $tgl_awal = $this->request->getGet('tgl_awal') ?? date('Y-m-01');
        $tgl_akhir = $this->request->getGet('tgl_akhir') ?? date('Y-m-d');

        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, st.nama_status as status, sp.nama_status as status_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->where('DATE(tgl_masuk) >=', $tgl_awal)
            ->where('DATE(tgl_masuk) <=', $tgl_akhir)
            ->findAll();

        $totalPendapatan = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('DATE(tgl_masuk) >=', $tgl_awal)
            ->where('DATE(tgl_masuk) <=', $tgl_akhir)
            ->first();

        $data = [
            'transaksi'       => $transaksi,
            'tgl_awal'        => $tgl_awal,
            'tgl_akhir'       => $tgl_akhir,
            'totalPendapatan' => $totalPendapatan['total'] ?? 0,
            'jumlahTransaksi' => count($transaksi),
        ];

        $html = view('laporan/export_rentang_pdf', $data);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('Laporan_Rentang_' . $tgl_awal . '_sd_' . $tgl_akhir . '.pdf', ['Attachment' => false]);
        exit();
    }

    public function export_rentang_excel()
    {
        $tgl_awal = $this->request->getGet('tgl_awal') ?? date('Y-m-01');
        $tgl_akhir = $this->request->getGet('tgl_akhir') ?? date('Y-m-d');

        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan, st.nama_status as status, sp.nama_status as status_pembayaran')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->join('tb_master_status_transaksi st', 'st.id = tb_transaksi.id_status_transaksi', 'left')
            ->join('tb_master_status_pembayaran sp', 'sp.id = tb_transaksi.id_status_pembayaran', 'left')
            ->where('DATE(tgl_masuk) >=', $tgl_awal)
            ->where('DATE(tgl_masuk) <=', $tgl_akhir)
            ->findAll();

        $totalPendapatan = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('DATE(tgl_masuk) >=', $tgl_awal)
            ->where('DATE(tgl_masuk) <=', $tgl_akhir)
            ->first();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN RENTANG TANGGAL SPARKLEEN LAUNDRY');
        $sheet->setCellValue('A2', 'Rentang: ' . date('d/m/Y', strtotime($tgl_awal)) . ' s/d ' . date('d/m/Y', strtotime($tgl_akhir)));
        $sheet->setCellValue('A3', 'Total Pendapatan: Rp ' . number_format($totalPendapatan['total'] ?? 0, 0, ',', '.'));
        $sheet->setCellValue('A4', 'Jumlah Transaksi: ' . count($transaksi));

        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');
        $sheet->mergeCells('A4:I4');
        $sheet->getStyle('A1:A4')->getFont()->setBold(true);

        $headers = ['No', 'No Nota', 'Pelanggan', 'Tgl Masuk', 'Tgl Selesai', 'Total Berat', 'Grand Total', 'Status', 'Status Bayar'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '6', $header);
            $sheet->getStyle($col . '6')->getFont()->setBold(true);
            $sheet->getStyle($col . '6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        $row = 7;
        $no  = 1;
        foreach ($transaksi as $t) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $t['no_nota']);
            $sheet->setCellValue('C' . $row, $t['nama_pelanggan']);
            $sheet->setCellValue('D' . $row, date('d/m/Y', strtotime($t['tgl_masuk'])));
            $sheet->setCellValue('E' . $row, $t['tgl_selesai_aktual'] ? date('d/m/Y', strtotime($t['tgl_selesai_aktual'])) : '-');
            $sheet->setCellValue('F' . $row, $t['total_berat'] . ' kg');
            $sheet->setCellValue('G' . $row, $t['grand_total']);
            $sheet->setCellValue('H' . $row, ucfirst($t['status']));
            $sheet->setCellValue('I' . $row, ucfirst($t['status_pembayaran']));
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle('A6:I' . ($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Laporan_Rentang_' . $tgl_awal . '_sd_' . $tgl_akhir . '.xlsx"');
        $writer->save('php://output');
        exit();
    }
}