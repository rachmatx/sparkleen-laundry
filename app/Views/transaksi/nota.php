<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Laundry - <?= $transaksi['no_nota'] ?></title>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 13px; background: #f0f0f0; }
        .wrapper { display: flex; flex-direction: column; align-items: center; padding: 20px; }
        .nota {
            background: #fff;
            width: 320px;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,.15);
        }
        .nota-header { text-align: center; border-bottom: 1px dashed #999; padding-bottom: 12px; margin-bottom: 12px; }
        .nota-header h2 { font-size: 18px; letter-spacing: 2px; margin-bottom: 4px; }
        .nota-header small { font-size: 11px; color: #555; }
        .nota-info { margin-bottom: 12px; }
        .nota-info table { width: 100%; }
        .nota-info td { padding: 2px 0; font-size: 12px; }
        .nota-info td:first-child { color: #555; width: 45%; }
        .divider { border: none; border-top: 1px dashed #999; margin: 12px 0; }
        .item-table { width: 100%; border-collapse: collapse; }
        .item-table th { font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #ccc; padding: 4px 2px; }
        .item-table td { font-size: 12px; padding: 4px 2px; vertical-align: top; }
        .item-table .text-right { text-align: right; }
        .item-table tfoot td { border-top: 1px dashed #999; font-weight: bold; padding-top: 8px; }
        .nota-footer { text-align: center; margin-top: 14px; font-size: 12px; color: #555; line-height: 1.7; }
        .btn-group { display: flex; flex-direction: column; gap: 8px; width: 320px; margin-top: 20px; }
        .btn-row { display: flex; gap: 10px; width: 100%; }
        .btn {
            display: inline-block; padding: 10px 20px;
            border-radius: 4px; text-decoration: none;
            font-family: Arial, sans-serif; font-size: 14px; cursor: pointer;
            border: 1px solid #ccc; background: #fff;
            text-align: center;
        }
        .btn-primary { background: #0d6efd; color: #fff; border-color: #0d6efd; }
        .btn-secondary { background: #6c757d; color: #fff; border-color: #6c757d; }
        .btn-success { background: #25d366; color: #fff; border-color: #25d366; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .wrapper { padding: 0; }
            .nota { box-shadow: none; width: 100%; max-width: 300px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="nota">
        <div class="nota-header">
            <h2>SPARKLEEN</h2>
            <small>Jasa Laundry Profesional</small><br>
            <small id="printTime"></small>
            <script>
                const now = new Date();
                const formattedTime = now.toLocaleDateString('id-ID', {
                    day: '2-digit', month: '2-digit', year: 'numeric'
                }) + ' ' + now.toLocaleTimeString('id-ID', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                }).replace(/\./g, ':');
                document.getElementById('printTime').textContent = formattedTime;
            </script>
        </div>

        <div class="nota-info">
            <table>
                <tr>
                    <td>No Nota</td>
                    <td>: <strong><?= $transaksi['no_nota'] ?></strong></td>
                </tr>
                <tr>
                    <td>Kasir</td>
                    <td>: <?= esc($transaksi['nama_kasir'] ?? 'System') ?></td>
                </tr>
                <tr>
                    <td>Pelanggan</td>
                    <td>: <?= esc($pelanggan['nama_pelanggan']) ?></td>
                </tr>
                <tr>
                    <td>Tgl Masuk</td>
                    <td>: <?= date('d/m/Y H:i', strtotime($transaksi['tgl_masuk'])) ?></td>
                </tr>
                <tr>
                    <td>Est. Selesai</td>
                    <td>: <?= date('d/m/Y H:i', strtotime($transaksi['tgl_selesai_estimasi'])) ?></td>
                </tr>
            </table>
        </div>

        <hr class="divider">

        <table class="item-table">
            <thead>
                <tr>
                    <th>Layanan</th>
                    <th class="text-right">Berat</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($detail as $d): ?>
                <tr>
                    <td><?= esc($d['layanan']['nama_layanan'] ?? $d['nama_layanan'] ?? '-') ?></td>
                    <td class="text-right"><?= $d['berat'] ?>kg</td>
                    <td class="text-right">Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Total Berat</td>
                    <td class="text-right"><?= $transaksi['total_berat'] ?> kg</td>
                </tr>
                <tr>
                    <td colspan="2">Subtotal</td>
                    <td class="text-right">Rp <?= number_format($transaksi['total_biaya'], 0, ',', '.') ?></td>
                </tr>
                <?php if (($transaksi['poin_ditebus'] ?? 0) > 0): ?>
                <tr>
                    <td colspan="2">Poin Ditebus (<?= $transaksi['poin_ditebus'] ?> pt)</td>
                    <td class="text-right">-Rp <?= number_format($transaksi['diskon'], 0, ',', '.') ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="2">TOTAL BAYAR</td>
                    <td class="text-right">Rp <?= number_format($transaksi['grand_total'], 0, ',', '.') ?></td>
                </tr>
                <?php if (($transaksi['poin_diperoleh'] ?? 0) > 0): ?>
                <tr>
                    <td colspan="2" style="font-size: 11px; font-weight: normal; color: #555; padding-top: 8px;">Poin Diperoleh</td>
                    <td class="text-right" style="font-size: 11px; font-weight: normal; color: #555; padding-top: 8px;">+<?= $transaksi['poin_diperoleh'] ?> pt</td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="2" style="font-size: 11px; font-weight: normal; color: #555;">Total Poin Pelanggan</td>
                    <td class="text-right" style="font-size: 11px; font-weight: normal; color: #555;"><?= $pelanggan['poin'] ?> pt</td>
                </tr>
            </tfoot>
        </table>

        <div class="nota-footer">
            <hr class="divider">
            Terima kasih telah mempercayakan<br>cucian Anda kepada kami.<br>
            <strong>Sparkleen - Bersih, Rapi, Tepat Waktu</strong>
        </div>
    </div>

    <div class="btn-group no-print">
        <div class="btn-row">
            <button onclick="window.print()" class="btn btn-primary" style="flex: 1;">
                <i class="fas fa-print me-1"></i> Cetak Nota
            </button>
            <a href="/transaksi/daftar" class="btn btn-secondary" style="flex: 1;">
                <i class="fas fa-list me-1"></i> Daftar
            </a>
        </div>
        
        <?php
        $nomor = $pelanggan['no_telp'];
        if (substr($nomor, 0, 1) === '0') {
            $nomor = '62' . substr($nomor, 1);
        }
        $pesanWa = "Halo *" . esc($pelanggan['nama_pelanggan']) . "*,\n\nTerima kasih telah mencuci di *Sparkleen Laundry*.\n\nBerikut rincian pesanan Anda:\n- *No Nota*: " . $transaksi['no_nota'] . "\n- *Total Berat*: " . $transaksi['total_berat'] . " kg\n- *Grand Total*: Rp " . number_format($transaksi['grand_total'], 0, ',', '.') . "\n- *Est. Selesai*: " . date('d/m/Y H:i', strtotime($transaksi['tgl_selesai_estimasi'])) . "\n\nPoin didapat: +" . ($transaksi['poin_diperoleh'] ?? 0) . " pt\nTotal Poin Anda: " . ($pelanggan['poin'] ?? 0) . " pt\n\nSalam bersih & rapi,\n*Sparkleen Laundry*";
        $waLink = "https://api.whatsapp.com/send?phone=" . urlencode($nomor) . "&text=" . urlencode($pesanWa);
        ?>
        
        <a href="<?= $waLink ?>" target="_blank" class="btn btn-success">
            <i class="fab fa-whatsapp me-1"></i> Kirim WhatsApp Manual
        </a>
    </div>
</div>
</body>
</html>