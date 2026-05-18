<!DOCTYPE html>
<html>
<head>
    <title>Daftar Transaksi Laundry</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-success { background: #28a745; }
        .btn-info { background: #17a2b8; }
        .btn-sm { padding: 3px 6px; font-size: 12px; }
        .status-menunggu { background: #ffc107; color: #000; padding: 3px 8px; border-radius: 3px; }
        .status-proses { background: #17a2b8; color: #fff; padding: 3px 8px; border-radius: 3px; }
        .status-setrika { background: #6c757d; color: #fff; padding: 3px 8px; border-radius: 3px; }
        .status-selesai { background: #28a745; color: #fff; padding: 3px 8px; border-radius: 3px; }
        .status-diambil { background: #007bff; color: #fff; padding: 3px 8px; border-radius: 3px; }
        .header { margin-bottom: 20px; }
        .search-box { margin-bottom: 20px; }
        .search-box input { padding: 8px; width: 300px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Daftar Transaksi Laundry</h1>
        <a href="/dashboard" class="btn">Kembali ke Dashboard</a>
        <a href="/transaksi" class="btn btn-success">Transaksi Baru</a>
    </div>

    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Cari no nota atau nama pelanggan..." onkeyup="searchTable()">
    </div>

    <table>
        <thead>
            <tr>
                <th>No Nota</th>
                <th>Pelanggan</th>
                <th>Tgl Masuk</th>
                <th>Total Berat</th>
                <th>Grand Total</th>
                <th>Status</th>
                <th>Status Bayar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($transaksi as $t): ?>
            <tr>
                <td><?= $t['no_nota'] ?></td>
                <td><?= $t['nama_pelanggan'] ?></td>
                <td><?= date('d/m/Y', strtotime($t['tgl_masuk'])) ?></td>
                <td><?= $t['total_berat'] ?> kg</td>
                <td>Rp <?= number_format($t['grand_total'], 0, ',', '.') ?></td>
                <td>
                    <span class="status-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                </td>
                <td>
                    <?php if($t['status_pembayaran'] == 'lunas'): ?>
                        <span style="background: #28a745; color:#fff; padding:3px 8px; border-radius:3px;">Lunas</span>
                    <?php elseif($t['status_pembayaran'] == 'cicil'): ?>
                        <span style="background: #ffc107; color:#000; padding:3px 8px; border-radius:3px;">Cicil</span>
                    <?php else: ?>
                        <span style="background: #dc3545; color:#fff; padding:3px 8px; border-radius:3px;">Belum</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/transaksi/detail/<?= $t['id_transaksi'] ?>" class="btn btn-info btn-sm">Detail</a>
                    <a href="/transaksi/nota/<?= $t['id_transaksi'] ?>" class="btn btn-sm">Nota</a>
                    <?php if($t['status'] != 'diambil'): ?>
                    <a href="/transaksi/ubah-status/<?= $t['id_transaksi'] ?>" class="btn btn-warning btn-sm">Update Status</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        function searchTable() {
            let input = document.getElementById('searchInput');
            let filter = input.value.toLowerCase();
            let rows = document.querySelectorAll('tbody tr');
            
            for (let i = 0; i < rows.length; i++) {
                let text = rows[i].innerText.toLowerCase();
                if (text.includes(filter)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>