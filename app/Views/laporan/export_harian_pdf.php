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
        .footer { text-align: center; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN HARIAN LAUNDRY</h1>
        <p>Tanggal: <?= date('d/m/Y', strtotime($tanggal)) ?></p>
    </div>
    <div class="summary">
        <strong>Total Pendapatan:</strong> Rp <?= number_format($totalPendapatan, 0, ',', '.') ?> |
        <strong>Jumlah Transaksi:</strong> <?= $jumlahTransaksi ?>
    </div>
    <table>
        <thead><tr><th>No</th><th>No Nota</th><th>Pelanggan</th><th>Tgl Masuk</th><th>Berat</th><th>Total</th><th>Status</th><th>Bayar</th></tr></thead>
        <tbody>
            <?php $no = 1; foreach($transaksi as $t): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $t['no_nota'] ?></td>
                <td><?= $t['nama_pelanggan'] ?></td>
                <td><?= date('d/m/Y H:i', strtotime($t['tgl_masuk'])) ?></td>
                <td><?= $t['total_berat'] ?> kg</td>
                <td>Rp <?= number_format($t['grand_total'], 0, ',', '.') ?></td>
                <td><?= ucfirst($t['status']) ?></td>
                <td><?= ucfirst($t['status_pembayaran']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="footer">Dicetak: <?= date('d/m/Y H:i:s') ?></div>
</body>
</html>
