<!DOCTYPE html>
<html>
<head>
    <title>Laporan Bulanan Laundry</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header h1 { color: #007bff; font-size: 24px; }
        .btn-group { display: flex; gap: 10px; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-info { background: #17a2b8; color: white; }
        
        .filter-card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .filter-form { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-weight: bold; font-size: 14px; }
        .filter-group select, .filter-group input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; width: 150px; }
        
        .summary { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px; }
        .summary-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .summary-card h3 { font-size: 28px; color: #28a745; margin-bottom: 10px; }
        .summary-card p { color: #666; font-size: 14px; }
        
        .chart-card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .chart-card h3 { margin-bottom: 15px; color: #333; }
        canvas { max-height: 300px; width: 100%; }
        
        .table-card { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #007bff; color: white; padding: 12px; text-align: left; }
        td { padding: 10px 12px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f8f9fa; }
        
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; display: inline-block; }
        .status-menunggu { background: #ffc107; color: #000; }
        .status-proses { background: #17a2b8; color: white; }
        
        .footer { text-align: center; margin-top: 20px; padding: 15px; color: #666; font-size: 12px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📆 Laporan Bulanan Laundry</h1>
            <div class="btn-group">
                <a href="/dashboard" class="btn btn-primary">🏠 Dashboard</a>
                <a href="/laporan" class="btn btn-danger">🔙 Kembali</a>
            </div>
        </div>
        
        <div class="filter-card">
            <form method="get" action="/laporan/bulanan" class="filter-form">
                <div class="filter-group">
                    <label>📅 Bulan</label>
                    <select name="bulan">
                        <option value="01" <?= $bulan == '01' ? 'selected' : '' ?>>Januari</option>
                        <option value="02" <?= $bulan == '02' ? 'selected' : '' ?>>Februari</option>
                        <option value="03" <?= $bulan == '03' ? 'selected' : '' ?>>Maret</option>
                        <option value="04" <?= $bulan == '04' ? 'selected' : '' ?>>April</option>
                        <option value="05" <?= $bulan == '05' ? 'selected' : '' ?>>Mei</option>
                        <option value="06" <?= $bulan == '06' ? 'selected' : '' ?>>Juni</option>
                        <option value="07" <?= $bulan == '07' ? 'selected' : '' ?>>Juli</option>
                        <option value="08" <?= $bulan == '08' ? 'selected' : '' ?>>Agustus</option>
                        <option value="09" <?= $bulan == '09' ? 'selected' : '' ?>>September</option>
                        <option value="10" <?= $bulan == '10' ? 'selected' : '' ?>>Oktober</option>
                        <option value="11" <?= $bulan == '11' ? 'selected' : '' ?>>November</option>
                        <option value="12" <?= $bulan == '12' ? 'selected' : '' ?>>Desember</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>📆 Tahun</label>
                    <select name="tahun">
                        <?php for($y = date('Y')-2; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
                <div class="filter-group">
                    <a href="/laporan/export_bulanan_pdf?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn btn-warning">📄 Export PDF</a>
                </div>
                <div class="filter-group">
                    <a href="/laporan/export_bulanan_excel?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn btn-info">📊 Export Excel</a>
                </div>
            </form>
        </div>
        
        <div class="summary">
            <div class="summary-card">
                <h3>Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></h3>
                <p>💰 Total Pendapatan</p>
            </div>
            <div class="summary-card">
                <h3><?= $jumlahTransaksi ?></h3>
                <p>📋 Jumlah Transaksi</p>
            </div>
        </div>
        
        <?php if(!empty($pendapatanPerHari)): ?>
        <div class="chart-card">
            <h3>📊 Grafik Pendapatan Harian</h3>
            <canvas id="chartPendapatan"></canvas>
        </div>
        <?php endif; ?>
        
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No Nota</th>
                        <th>Pelanggan</th>
                        <th>Total Berat</th>
                        <th>Grand Total</th>
                        <th>Status</th>
                        <th>Status Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($transaksi)): ?>
                        <tr><td colspan="7" style="text-align:center">📭 Tidak ada transaksi pada bulan ini</td></tr>
                    <?php else: ?>
                        <?php foreach($transaksi as $t): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($t['tgl_masuk'])) ?></td>
                            <td><?= $t['no_nota'] ?></td>
                            <td><?= $t['nama_pelanggan'] ?></td>
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
    
    <?php if(!empty($pendapatanPerHari)): ?>
    <script>
        const ctx = document.getElementById('chartPendapatan').getContext('2d');
        const labels = <?= json_encode(array_map(function($d) { 
            return date('d/m', strtotime($d['tanggal'])); 
        }, $pendapatanPerHari)) ?>;
        const data = <?= json_encode(array_column($pendapatanPerHari, 'total')) ?>;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: data,
                    backgroundColor: 'rgba(0, 123, 255, 0.6)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>