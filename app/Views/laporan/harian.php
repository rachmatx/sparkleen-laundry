<!DOCTYPE html>
<html>
<head>
    <title>Laporan Harian Laundry</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        
        .container { max-width: 1200px; margin: 0 auto; }
        
        /* Header */
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #007bff; font-size: 24px; }
        .btn-group { display: flex; gap: 10px; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        
        /* Filter */
        .filter-card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .filter-form { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-weight: bold; font-size: 14px; }
        .filter-group input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; width: 200px; }
        
        /* Summary Cards */
        .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px; }
        .summary-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .summary-card h3 { font-size: 28px; color: #007bff; margin-bottom: 10px; }
        .summary-card p { color: #666; font-size: 14px; }
        .summary-card.total-pendapatan h3 { color: #28a745; }
        .summary-card.total-berat h3 { color: #17a2b8; }
        
        /* Tabel */
        .table-card { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #007bff; color: white; padding: 12px; text-align: left; font-weight: 600; }
        td { padding: 10px 12px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f8f9fa; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; display: inline-block; }
        .status-menunggu { background: #ffc107; color: #000; }
        .status-proses { background: #17a2b8; color: white; }
        .status-setrika { background: #6c757d; color: white; }
        .status-selesai { background: #28a745; color: white; }
        .status-diambil { background: #007bff; color: white; }
        
        .footer { text-align: center; margin-top: 20px; padding: 15px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Laporan Harian Laundry</h1>
            <div class="btn-group">
                <a href="/dashboard" class="btn btn-primary">🏠 Dashboard</a>
                <a href="/laporan" class="btn btn-danger">🔙 Kembali</a>
            </div>
        </div>
        
        <div class="filter-card">
            <form method="get" action="/laporan/harian" class="filter-form">
                <div class="filter-group">
                    <label>📆 Pilih Tanggal</label>
                    <input type="date" name="tanggal" value="<?= $tanggal ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
                <div class="filter-group">
                    <a href="/laporan/export_harian_pdf?tanggal=<?= $tanggal ?>" class="btn btn-warning">📄 Export PDF</a>
                </div>
                <div class="filter-group">
                    <a href="/laporan/export_harian_excel?tanggal=<?= $tanggal ?>" class="btn btn-info">📊 Export Excel</a>
                </div>
            </form>
        </div>
        
        <div class="summary">
            <div class="summary-card total-pendapatan">
                <h3>Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></h3>
                <p>💰 Total Pendapatan</p>
            </div>
            <div class="summary-card">
                <h3><?= $jumlahTransaksi ?></h3>
                <p>📋 Jumlah Transaksi</p>
            </div>
            <div class="summary-card total-berat">
                <h3><?= number_format($totalBerat, 1) ?> kg</h3>
                <p>⚖️ Total Berat</p>
            </div>
        </div>
        
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>No Nota</th>
                        <th>Pelanggan</th>
                        <th>Tanggal Masuk</th>
                        <th>Total Berat</th>
                        <th>Grand Total</th>
                        <th>Status</th>
                        <th>Status Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($transaksi)): ?>
                        <tr><td colspan="7" style="text-align:center">📭 Tidak ada transaksi pada tanggal ini</td></tr>
                    <?php else: ?>
                        <?php foreach($transaksi as $t): ?>
                        <tr>
                            <td><?= $t['no_nota'] ?></td>
                            <td><?= $t['nama_pelanggan'] ?></td>
                            <td><?= date('d/m/Y', strtotime($t['tgl_masuk'])) ?></td>
                            <td><?= $t['total_berat'] ?> kg</td>
                            <td>Rp <?= number_format($t['grand_total'], 0, ',', '.') ?></td>
                            <td><span class="status-badge status-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
                            <td><span class="status-badge" style="background:<?= $t['status_pembayaran'] == 'lunas' ? '#28a745' : ($t['status_pembayaran'] == 'cicil' ? '#ffc107' : '#dc3545') ?>; color:<?= $t['status_pembayaran'] == 'cicil' ? '#000' : '#fff' ?>"><?= ucfirst($t['status_pembayaran']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            Dicetak pada: <?= date('d/m/Y H:i:s') ?>
        </div>
    </div>
</body>
</html>