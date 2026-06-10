<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Laundry - <?= $transaksi['no_nota'] ?></title>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- QRCode JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; background: #f0f0f0; color: #000; }
        .wrapper { display: flex; flex-direction: column; align-items: center; padding: 20px; }
        .nota {
            background: #fff;
            width: 58mm; /* Sesuaikan dengan lebar thermal 58mm */
            padding: 10px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,.15);
        }
        .nota-header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
        .nota-header h2 { font-size: 16px; letter-spacing: 1px; margin-bottom: 2px; }
        .nota-header small { font-size: 10px; }
        .nota-info { margin-bottom: 10px; }
        .nota-info table { width: 100%; }
        .nota-info td { padding: 1px 0; font-size: 11px; }
        .nota-info td:first-child { width: 35%; }
        .divider { border: none; border-top: 1px dashed #000; margin: 10px 0; }
        .item-table { width: 100%; border-collapse: collapse; }
        .item-table th { font-size: 10px; text-transform: uppercase; border-bottom: 1px dashed #000; padding: 4px 0; text-align: left; }
        .item-table td { font-size: 11px; padding: 2px 0; vertical-align: top; }
        .item-table .text-right { text-align: right; }
        .item-table tfoot td { border-top: 1px dashed #000; padding-top: 6px; }
        .item-table tfoot tr:first-child td { padding-top: 8px; }
        .nota-footer { text-align: center; margin-top: 10px; font-size: 10px; line-height: 1.5; }
        .btn-group { display: flex; flex-direction: column; gap: 8px; width: 100%; max-width: 360px; margin-top: 20px; }
        .btn-row { display: flex; gap: 8px; width: 100%; }
        .btn {
            display: inline-block; padding: 10px 12px;
            border-radius: 4px; text-decoration: none;
            font-family: Arial, sans-serif; font-size: 14px; cursor: pointer;
            border: 1px solid #ccc; background: #fff;
            text-align: center;
            white-space: nowrap;
        }
        .btn-primary { background: #0d6efd; color: #fff; border-color: #0d6efd; }
        .btn-secondary { background: #6c757d; color: #fff; border-color: #6c757d; }
        .btn-success { background: #25d366; color: #fff; border-color: #25d366; }
        @media print {
            @page { margin: 0; }
            body { background: #fff; margin: 0; padding: 0; align-items: flex-start; }
            .no-print { display: none !important; }
            .wrapper { padding: 0; width: 100%; }
            .nota { box-shadow: none; width: 58mm; padding: 2mm; margin: 0 auto; }
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
                    <th colspan="2">Layanan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($detail as $d): ?>
                <tr>
                    <td colspan="2"><strong><?= esc($d['layanan']['nama_layanan'] ?? $d['nama_layanan'] ?? '-') ?></strong></td>
                </tr>
                <tr>
                    <td style="padding-bottom: 6px;"><?= $d['berat'] ?> x <?= number_format($d['harga_satuan'], 0, ',', '.') ?></td>
                    <td class="text-right" style="padding-bottom: 6px;"><?= number_format($d['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td>Total Berat</td>
                    <td class="text-right"><?= $transaksi['total_berat'] ?> kg</td>
                </tr>
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right"><?= number_format($transaksi['total_biaya'], 0, ',', '.') ?></td>
                </tr>
                <?php if (($transaksi['poin_ditebus'] ?? 0) > 0): ?>
                <tr>
                    <td>Poin (<?= $transaksi['poin_ditebus'] ?>)</td>
                    <td class="text-right">-<?= number_format($transaksi['diskon'], 0, ',', '.') ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td><strong>TOTAL</strong></td>
                    <td class="text-right"><strong><?= number_format($transaksi['grand_total'], 0, ',', '.') ?></strong></td>
                </tr>
                <?php if (($transaksi['poin_diperoleh'] ?? 0) > 0): ?>
                <tr>
                    <td style="font-size: 10px; font-weight: normal; padding-top: 4px;">+ Poin Didapat</td>
                    <td class="text-right" style="font-size: 10px; font-weight: normal; padding-top: 4px;"><?= $transaksi['poin_diperoleh'] ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td style="font-size: 10px; font-weight: normal;">Sisa Poin</td>
                    <td class="text-right" style="font-size: 10px; font-weight: normal;"><?= $pelanggan['poin'] ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="nota-footer">
            <hr class="divider">
            <div id="qrcode" style="display: flex; justify-content: center; margin: 10px 0;"></div>
            <small style="font-size: 9px;">Scan untuk lacak pesanan</small><br>
            Terima kasih telah mempercayakan<br>cucian Anda kepada kami.<br>
            <strong>Sparkleen - Bersih, Rapi, Tepat Waktu</strong>
        </div>
    </div>

    <div class="btn-group no-print">
        <div class="btn-row">
            <a href="/transaksi" class="btn btn-secondary" style="flex: 1;">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
            <button onclick="window.print()" class="btn btn-primary" style="flex: 1;">
                <i class="fas fa-print me-1"></i> Cetak
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
        $pin = session()->getFlashdata('pin_pengambilan_plain') ?: '(Tersembunyi)';
        $pesanWa = "Halo *" . esc($pelanggan['nama_pelanggan']) . "*,\n\nTerima kasih telah mencuci di *Sparkleen Laundry*.\n\nBerikut rincian pesanan Anda:\n- *No Nota*: " . $transaksi['no_nota'] . "\n- *Total Berat*: " . $transaksi['total_berat'] . " kg\n- *Grand Total*: Rp " . number_format($transaksi['grand_total'], 0, ',', '.') . "\n- *Est. Selesai*: " . date('d/m/Y H:i', strtotime($transaksi['tgl_selesai_estimasi'])) . "\n\n🔒 *PIN PENGAMBILAN: {$pin}*\n_Harap tunjukkan PIN ini kepada Kasir saat mengambil cucian. Jangan berikan kepada siapapun!_\n\nPoin didapat: +" . ($transaksi['poin_diperoleh'] ?? 0) . " pt\nTotal Poin Anda: " . ($pelanggan['poin'] ?? 0) . " pt\n\nSalam bersih & rapi,\n*Sparkleen Laundry*";
        $waLink = "https://api.whatsapp.com/send?phone=" . urlencode($nomor) . "&text=" . urlencode($pesanWa);
        ?>
        
        <a href="<?= $waLink ?>" target="_blank" class="btn btn-success">
            <i class="fab fa-whatsapp me-1"></i> Kirim WhatsApp Manual
        </a>
    </div>
</div>
<script>
    // URL Lacak Publik (contoh: https://domain.com/lacak?kode=LAU...)
    // Jika belum ada fitur /lacak, ini bisa diarahkan ke manapun.
    const lacakUrl = "<?= base_url('/lacak?kode=' . $transaksi['no_nota']) ?>";
    new QRCode(document.getElementById("qrcode"), {
        text: lacakUrl,
        width: 80,
        height: 80,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.L
    });
</script>
</body>
</html>