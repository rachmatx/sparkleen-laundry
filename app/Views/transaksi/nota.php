<!DOCTYPE html>
<html>
<head>
    <title>Nota Laundry</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .nota { width: 300px; margin: auto; border: 1px solid #ddd; padding: 15px; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px; }
        .total { font-weight: bold; margin-top: 10px; }
        .btn { display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="nota">
        <div class="header">
            <h3>Laundry App</h3>
            <p><?= date('d/m/Y H:i:s') ?></p>
        </div>
        
        <p><strong>No Nota:</strong> <?= $transaksi['no_nota'] ?></p>
        <p><strong>Pelanggan:</strong> <?= $pelanggan['nama_pelanggan'] ?></p>
        
        </table>
            <thead><tr><th>Layanan</th><th>Berat</th><th>Subtotal</th></tr></thead>
            <tbody>
                <?php foreach($detail as $d): ?>
                <tr>
                    <td><?= $d['layanan']['nama_layanan'] ?></td>
                    <td><?= $d['berat'] ?> kg</td>
                    <td>Rp <?= number_format($d['subtotal'],0,',','.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><th colspan="2">Total Berat</th><th><?= $transaksi['total_berat'] ?> kg</th></tr>
                <tr><th colspan="2">Total Biaya</th><th>Rp <?= number_format($transaksi['grand_total'],0,',','.') ?></th></tr>
            </tfoot>
        </table>
        
        <p style="text-align:center; margin-top:20px">Terima kasih!<br>Estimasi selesai: <?= date('d/m/Y', strtotime($transaksi['tgl_selesai_estimasi'])) ?></p>
    </div>
    
    <div style="text-align:center">
        <button onclick="window.print()" class="btn">Cetak Nota</button>
        <a href="/transaksi" class="btn">Transaksi Baru</a>
        <a href="/dashboard" class="btn">Dashboard</a>
    </div>
</body>
</html>