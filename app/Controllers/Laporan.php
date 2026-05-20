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
        $level = session()->get('level');
        if ($level != 'admin' && $level != 'pemilik') {
            session()->setFlashdata('error', 'Anda tidak memiliki akses ke halaman laporan.');
            header('Location: ' . base_url('/dashboard'));
            exit();
        }

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

    // Laporan Harian (Web)
    public function harian()
    {
        $tanggal = $this->request->getGet('tanggal') ?? date('Y-m-d');

        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->where('DATE(tgl_masuk)', $tanggal)
            ->orderBy('id_transaksi', 'DESC')
            ->findAll();

        $totalPendapatan = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('DATE(tgl_masuk)', $tanggal)
            ->first();

        $totalBerat = $this->transaksiModel
            ->select('SUM(total_berat) as berat')
            ->where('DATE(tgl_masuk)', $tanggal)
            ->first();

        $data = [
            'title'            => 'Laporan Harian',
            'activeMenu'       => 'laporan',
            'transaksi'        => $transaksi,
            'tanggal'          => $tanggal,
            'totalPendapatan'  => $totalPendapatan['total'] ?? 0,
            'jumlahTransaksi'  => count($transaksi),
            'totalBerat'       => $totalBerat['berat'] ?? 0,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('laporan/harian', $data)
             . view('backend/template/footer');
    }

    // Laporan Bulanan (Web)
    public function bulanan()
    {
        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->where('MONTH(tgl_masuk)', $bulan)
            ->where('YEAR(tgl_masuk)', $tahun)
            ->orderBy('tgl_masuk', 'ASC')
            ->findAll();

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

        $data = [
            'title'             => 'Laporan Bulanan',
            'activeMenu'        => 'laporan_bulanan',
            'transaksi'         => $transaksi,
            'bulan'             => $bulan,
            'tahun'             => $tahun,
            'totalPendapatan'   => $totalPendapatan['total'] ?? 0,
            'jumlahTransaksi'   => count($transaksi),
            'pendapatanPerHari' => $pendapatanPerHari,
        ];

        return view('backend/template/header', $data)
             . view('backend/template/sidebar', $data)
             . view('laporan/bulanan', $data)
             . view('backend/template/footer');
    }

    // ==================== EXPORT PDF ====================

    public function export_harian_pdf()
    {
        $tanggal = $this->request->getGet('tanggal') ?? date('Y-m-d');

        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->where('DATE(tgl_masuk)', $tanggal)
            ->findAll();

        $totalPendapatan = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('DATE(tgl_masuk)', $tanggal)
            ->first();

        $data = [
            'transaksi'       => $transaksi,
            'tanggal'         => $tanggal,
            'totalPendapatan' => $totalPendapatan['total'] ?? 0,
            'jumlahTransaksi' => count($transaksi),
        ];

        $html = view('laporan/export_harian_pdf', $data);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('Laporan_Harian_' . $tanggal . '.pdf', ['Attachment' => false]);
        exit();
    }

    public function export_bulanan_pdf()
    {
        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
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

    // ==================== EXPORT EXCEL ====================

    public function export_harian_excel()
    {
        $tanggal = $this->request->getGet('tanggal') ?? date('Y-m-d');

        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
            ->where('DATE(tgl_masuk)', $tanggal)
            ->findAll();

        $totalPendapatan = $this->transaksiModel
            ->select('SUM(grand_total) as total')
            ->where('DATE(tgl_masuk)', $tanggal)
            ->first();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN HARIAN SPARKLEEN LAUNDRY');
        $sheet->setCellValue('A2', 'Tanggal: ' . date('d/m/Y', strtotime($tanggal)));
        $sheet->setCellValue('A3', 'Total Pendapatan: Rp ' . number_format($totalPendapatan['total'] ?? 0, 0, ',', '.'));
        $sheet->setCellValue('A4', 'Jumlah Transaksi: ' . count($transaksi));

        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');
        $sheet->mergeCells('A4:H4');
        $sheet->getStyle('A1:A4')->getFont()->setBold(true);

        $headers = ['No', 'No Nota', 'Pelanggan', 'Tgl Masuk', 'Total Berat', 'Grand Total', 'Status', 'Status Bayar'];
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
            $sheet->setCellValue('E' . $row, $t['total_berat'] . ' kg');
            $sheet->setCellValue('F' . $row, $t['grand_total']);
            $sheet->setCellValue('G' . $row, ucfirst($t['status']));
            $sheet->setCellValue('H' . $row, ucfirst($t['status_pembayaran']));
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle('A6:H' . ($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Laporan_Harian_' . $tanggal . '.xlsx"');
        $writer->save('php://output');
        exit();
    }

    public function export_bulanan_excel()
    {
        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        $transaksi = $this->transaksiModel
            ->select('tb_transaksi.*, tb_pelanggan.nama_pelanggan')
            ->join('tb_pelanggan', 'tb_pelanggan.id_pelanggan = tb_transaksi.id_pelanggan')
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
}