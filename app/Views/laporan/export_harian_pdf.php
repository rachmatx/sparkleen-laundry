<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Harian Laundry</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .summary { margin-bottom: 20px; }
        .summary-item { display: inline-block; margin-right: 30px; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN HARIAN LAUNDRY</h1>
        <p>Tanggal: <?= date('d/m/Y', strtotime($tanggal)) ?></p>
    </div>
    <div class="summary">
        <div class="summary-item">Total Pendapatan: Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></div>
        <div class="summary-item">Jumlah Transaksi: <?= $jumlahTransaksi ?></div>
    </div>
    <table>
        <thead><tr><th>No Nota</th><th>Pelanggan</th><th>Total Berat</th><th>Grand Total</th><th>Status</th><th>Status Bayar</th></tr></thead>
        <tbody>
            <?php foreach($transaksi as $t): ?>
            <tr><td><?= $t['no_nota'] ?></td><td><?= $t['nama_pelanggan'] ?></td><td><?= $t['total_berat'] ?> kg</td><td>Rp <?= number_format($t['grand_total'], 0, ',', '.') ?></td><td><?= ucfirst($t['status']) ?></td><td><?= ucfirst($t['status_pembayaran']) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="footer">Dicetak: <?= date('d/m/Y H:i:s') ?></div>

    <button onclick="window.location.href='/laporan/export_harian_pdf?tanggal=<?= $tanggal ?>'" class="btn btn-warning">📄 Export PDF</button>
    <button onclick="window.location.href='/laporan/export_harian_excel?tanggal=<?= $tanggal ?>'" class="btn btn-info">📊 Export Excel</button>
</body>
</html>