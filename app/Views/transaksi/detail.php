<!DOCTYPE html>
<html>
<head>
    <title>Detail Transaksi Laundry</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .header { margin-bottom: 20px; }
        .info-box { margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .status-menunggu { background: #ffc107; padding: 5px 10px; border-radius: 3px; }
        .status-proses { background: #17a2b8; color: #fff; padding: 5px 10px; border-radius: 3px; }
        .status-setrika { background: #6c757d; color: #fff; padding: 5px 10px; border-radius: 3px; }
        .status-selesai { background: #28a745; color: #fff; padding: 5px 10px; border-radius: 3px; }
        .status-diambil { background: #007bff; color: #fff; padding: 5px 10px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Detail Transaksi</h1>
        <a href="/transaksi/daftar" class="btn">Kembali ke Daftar</a>
        <a href="/transaksi/nota/<?= $transaksi['id_transaksi'] ?>" class="btn btn-success">Cetak Nota</a>
    </div>

    <div class="info-box">
        <h3>Informasi Transaksi</h3>
        <p><strong>No Nota:</strong> <?= $transaksi['no_nota'] ?></p>
        <p><strong>Pelanggan:</strong> <?= $transaksi['nama_pelanggan'] ?></p>
        <p><strong>Kasir:</strong> <?= $transaksi['nama_karyawan'] ?></p>
        <p><strong>Tanggal Masuk:</strong> <?= date('d/m/Y', strtotime($transaksi['tgl_masuk'])) ?></p>
        <p><strong>Estimasi Selesai:</strong> <?= date('d/m/Y', strtotime($transaksi['tgl_selesai_estimasi'])) ?></p>
        <p><strong>Status:</strong> <span class="status-<?= $transaksi['status'] ?>"><?= ucfirst($transaksi['status']) ?></span></p>
        <p><strong>Status Pembayaran:</strong> <?= ucfirst($transaksi['status_pembayaran']) ?></p>
        <p><strong>Total Berat:</strong> <?= $transaksi['total_berat'] ?> kg</p>
        <p><strong>Total Biaya:</strong> Rp <?= number_format($transaksi['grand_total'], 0, ',', '.') ?></p>
    </div>

    <h3>Detail Layanan</h3>
    <table>
        <thead>
            <tr><th>Layanan</th><th>Berat (kg)</th><th>Harga/kg</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
            <?php foreach($detail as $d): ?>
            <tr>
                <td><?= $d['nama_layanan'] ?></td>
                <td><?= $d['berat'] ?> kg</td>
                <td>Rp <?= number_format($d['harga_satuan'], 0, ',', '.') ?></td>
                <td>Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if(!empty($statusLog)): ?>
    <h3>Riwayat Perubahan Status</h3>
    <table>
        <thead>
            <tr><th>Status Lama</th><th>Status Baru</th><th>Diubah Oleh</th><th>Tanggal</th><th>Catatan</th></tr>
        </thead>
        <tbody>
            <?php foreach($statusLog as $log): ?>
            <tr>
                <td><?= ucfirst($log['status_lama']) ?></td>
                <td><?= ucfirst($log['status_baru']) ?></td>
                <td><?= $log['nama_karyawan'] ?></td>
                <td><?= date('d/m/Y H:i', strtotime($log['tgl_ubah'])) ?></td>
                <td><?= $log['catatan'] ?? '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div style="margin-top: 20px; display: flex; gap: 10px;">
    <a href="/transaksi/pembayaran/<?= $transaksi['id_transaksi'] ?>" class="btn btn-success">💳 Bayar</a>
    <?php if($transaksi['status'] != 'diambil'): ?>
    <a href="/transaksi/ubah-status/<?= $transaksi['id_transaksi'] ?>" class="btn btn-warning">Update Status</a>
    <?php endif; ?>
</div>

    <?php if($transaksi['status'] != 'diambil'): ?>
    <div style="margin-top: 20px;">
        <a href="/transaksi/ubah-status/<?= $transaksi['id_transaksi'] ?>" class="btn btn-warning">Update Status</a>
    </div>
    <?php endif; ?>
</body>
</html>