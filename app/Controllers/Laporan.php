<?php

namespace App\Controllers;

use App\Services\LaporanService;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class Laporan extends BaseController
{
    protected $laporanService;

    public function __construct()
    {
        $this->laporanService = new LaporanService();
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
        // Normalize: terima "6" atau "06" → selalu "06" (2 digit dengan leading zero)
        $bulan = str_pad((int) $bulan, 2, '0', STR_PAD_LEFT);
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        $page = (int)($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $result  = $this->laporanService->getTransaksiBulanan((int) $bulan, (int) $tahun, $limit, $offset);
        $summary = $this->laporanService->getSummaryBulanan((int) $bulan, (int) $tahun);

        $data = [
            'title'             => 'Laporan Bulanan',
            'activeMenu'        => 'laporan_bulanan',
            'transaksi'         => $result['items'],
            'page'              => $page,
            'hasPrev'           => $page > 1,
            'hasNext'           => $result['has_next'],
            'limit'             => $limit,
            'bulan'             => $bulan,
            'tahun'             => $tahun,
            'totalPendapatan'   => $summary['total_pendapatan'],
            'jumlahTransaksi'   => $summary['jumlah_transaksi'],
            'pendapatanPerHari' => $summary['pendapatan_per_hari'],
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

        $transaksi = $this->laporanService->getAllTransaksiBulanan((int) $bulan, (int) $tahun);
        $summary   = $this->laporanService->getSummaryBulanan((int) $bulan, (int) $tahun);
        $namaBulan = $this->laporanService->getNamaBulan($bulan);

        $data = [
            'transaksi'       => $transaksi,
            'bulan'           => $bulan,
            'tahun'           => $tahun,
            'namaBulan'       => $namaBulan,
            'totalPendapatan' => $summary['total_pendapatan'],
            'jumlahTransaksi' => count($transaksi),
        ];

        $html = view('laporan/export_bulanan_pdf', $data);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('Laporan_Bulanan_' . $namaBulan . '_' . $tahun . '.pdf', ['Attachment' => false]);
        exit();
    }

    public function export_bulanan_excel()
    {
        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        $transaksi = $this->laporanService->getAllTransaksiBulanan((int) $bulan, (int) $tahun);
        $summary   = $this->laporanService->getSummaryBulanan((int) $bulan, (int) $tahun);
        $namaBulan = $this->laporanService->getNamaBulan($bulan);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN BULANAN SPARKLEEN LAUNDRY');
        $sheet->setCellValue('A2', 'Bulan: ' . $namaBulan . ' ' . $tahun);
        $sheet->setCellValue('A3', 'Total Pendapatan: Rp ' . number_format($summary['total_pendapatan'], 0, ',', '.'));
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
        header('Content-Disposition: attachment;filename="Laporan_Bulanan_' . $namaBulan . '_' . $tahun . '.xlsx"');
        $writer->save('php://output');
        exit();
    }

    // ==================== HARIAN ====================

    public function export_harian_pdf()
    {
        $tanggal = $this->request->getGet('tanggal') ?? date('Y-m-d');

        $transaksi = $this->laporanService->getAllTransaksiHarian($tanggal);
        $summary   = $this->laporanService->getSummaryHarian($tanggal);

        $data = [
            'transaksi'       => $transaksi,
            'tanggal'         => $tanggal,
            'totalPendapatan' => $summary['total_pendapatan'],
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

    public function export_harian_excel()
    {
        $tanggal = $this->request->getGet('tanggal') ?? date('Y-m-d');

        $transaksi = $this->laporanService->getAllTransaksiHarian($tanggal);
        $summary   = $this->laporanService->getSummaryHarian($tanggal);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN HARIAN SPARKLEEN LAUNDRY');
        $sheet->setCellValue('A2', 'Tanggal: ' . date('d/m/Y', strtotime($tanggal)));
        $sheet->setCellValue('A3', 'Total Pendapatan: Rp ' . number_format($summary['total_pendapatan'], 0, ',', '.'));
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
            $sheet->setCellValue('D' . $row, date('d/m/Y H:i', strtotime($t['tgl_masuk'])));
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
        header('Content-Disposition: attachment;filename="Laporan_Harian_' . $tanggal . '.xlsx"');
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

        $result  = $this->laporanService->getTransaksiRentang($tgl_awal, $tgl_akhir, $limit, $offset);
        $summary = $this->laporanService->getSummaryRentang($tgl_awal, $tgl_akhir);

        $data = [
            'title'            => 'Laporan Rentang Tanggal',
            'activeMenu'       => 'laporan_rentang',
            'transaksi'        => $result['items'],
            'page'             => $page,
            'hasPrev'          => $page > 1,
            'hasNext'          => $result['has_next'],
            'limit'            => $limit,
            'tgl_awal'         => $tgl_awal,
            'tgl_akhir'        => $tgl_akhir,
            'totalPendapatan'  => $summary['total_pendapatan'],
            'jumlahTransaksi'  => $summary['jumlah_transaksi'],
            'totalBerat'       => $summary['total_berat'],
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

        $transaksi = $this->laporanService->getAllTransaksiRentang($tgl_awal, $tgl_akhir);
        $summary   = $this->laporanService->getSummaryRentang($tgl_awal, $tgl_akhir);

        $data = [
            'transaksi'       => $transaksi,
            'tgl_awal'        => $tgl_awal,
            'tgl_akhir'       => $tgl_akhir,
            'totalPendapatan' => $summary['total_pendapatan'],
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

        $transaksi = $this->laporanService->getAllTransaksiRentang($tgl_awal, $tgl_akhir);
        $summary   = $this->laporanService->getSummaryRentang($tgl_awal, $tgl_akhir);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN RENTANG TANGGAL SPARKLEEN LAUNDRY');
        $sheet->setCellValue('A2', 'Rentang: ' . date('d/m/Y', strtotime($tgl_awal)) . ' s/d ' . date('d/m/Y', strtotime($tgl_akhir)));
        $sheet->setCellValue('A3', 'Total Pendapatan: Rp ' . number_format($summary['total_pendapatan'], 0, ',', '.'));
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
