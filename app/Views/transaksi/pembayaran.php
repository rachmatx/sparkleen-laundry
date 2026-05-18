<!DOCTYPE html>
<html>
<head>
    <title>Pembayaran Laundry</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #000; }
        .header { margin-bottom: 20px; }
        .info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .sisa { font-size: 24px; color: #dc3545; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select { padding: 8px; width: 250px; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Pembayaran Laundry</h1>
        <a href="/transaksi/detail/<?= $transaksi['id_transaksi'] ?>" class="btn">Kembali</a>
    </div>

    <?php if(session()->getFlashdata('success')): ?>
        <p class="success"><?= session()->getFlashdata('success') ?></p>
    <?php endif; ?>

    <?php if(session()->getFlashdata('error')): ?>
        <p style="color:red"><?= session()->getFlashdata('error') ?></p>
    <?php endif; ?>

    <div class="info">
        <p><strong>No Nota:</strong> <?= $transaksi['no_nota'] ?></p>
        <p><strong>Pelanggan:</strong> <?= $namaPelanggan ?></p>
        <p><strong>Total Tagihan:</strong> Rp <?= number_format($transaksi['grand_total'], 0, ',', '.') ?></p>
        <p><strong>Sisa Tagihan:</strong> <span class="sisa">Rp <?= number_format($sisa, 0, ',', '.') ?></span></p>
    </div>

    <?php if($sisa > 0): ?>
    <div class="form-group">
        <h3>Bayar Sekarang</h3>
        <form action="/transaksi/simpanPembayaran" method="post">
            <input type="hidden" name="id_transaksi" value="<?= $transaksi['id_transaksi'] ?>">
            
            <div class="form-group">
                <label>Nominal Bayar</label>
                <input type="number" name="nominal_bayar" min="1" max="<?= $sisa ?>" required>
            </div>
            
            <div class="form-group">
                <label>Metode Pembayaran</label>
                <select name="metode_bayar" required>
                    <option value="cash">Cash</option>
                    <option value="qris">QRIS</option>
                    <option value="transfer">Transfer Bank</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-success">Proses Pembayaran</button>
        </form>
    </div>
    <?php else: ?>
    <p style="color:green; font-size:18px;">✅ LUNAS - Transaksi ini sudah dibayar lunas</p>
    <?php endif; ?>

    <h3>Riwayat Pembayaran</h3>
    <table>
        <thead>
            <tr><th>Tanggal</th><th>Nominal</th><th>Metode</th><th>Sisa Tagihan</th></tr>
        </thead>
        <tbody>
            <?php foreach($pembayaran as $p): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($p['tgl_bayar'])) ?></td>
                <td>Rp <?= number_format($p['nominal_bayar'], 0, ',', '.') ?></td>
                <td><?= ucfirst($p['metode_bayar']) ?></td>
                <td>Rp <?= number_format($p['sisa_tagihan'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($pembayaran)): ?>
            <tr><td colspan="4" style="text-align:center">Belum ada pembayaran</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>