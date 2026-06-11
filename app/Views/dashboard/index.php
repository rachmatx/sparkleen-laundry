<!-- ═══ Card Ringkasan ════════════════════════════════════════════════════ -->
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3 class="fs-5">Rp <?= number_format($pendapatanHariIni, 0, ',', '.') ?></h3>
                <p>Pendapatan Hari Ini</p>
            </div>
            <div class="small-box-icon"><i class="fas fa-money-bill-wave"></i></div>
            <a href="/laporan/rentang" class="small-box-footer link-light link-underline-opacity-0">
                Lihat Laporan <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3 class="fs-5"><?= $transaksiHariIni ?></h3>
                <p>Transaksi Hari Ini</p>
            </div>
            <div class="small-box-icon"><i class="fas fa-receipt"></i></div>
            <a href="/transaksi/daftar" class="small-box-footer link-light link-underline-opacity-0">
                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3 class="fs-5"><?= $transaksiProses ?></h3>
                <p>Sedang Diproses</p>
            </div>
            <div class="small-box-icon"><i class="fas fa-spinner"></i></div>
            <a href="/transaksi/daftar" class="small-box-footer link-dark link-underline-opacity-0">
                Lihat Daftar <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3 class="fs-5"><?= $jumlahPelanggan ?></h3>
                <p>Total Pelanggan</p>
            </div>
            <div class="small-box-icon"><i class="fas fa-users"></i></div>
            <a href="/pelanggan" class="small-box-footer link-light link-underline-opacity-0">
                Kelola Data <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<?php if(!empty($stokKritis)): ?>
<!-- Alert Stok Kritis -->
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-warning alert-dismissible d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
            <div class="flex-grow-1">
                <strong>Peringatan Stok!</strong>
                <?= count($stokKritis) ?> barang inventaris memiliki stok di bawah batas minimal.
                <a href="/inventaris/warning" class="ms-2 alert-link">Lihat Detail</a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ Grafik ════════════════════════════════════════════════════════════ -->
<div class="row mb-3">
    <!-- Grafik Pendapatan -->
    <div class="col-md-7">
        <div class="card card-outline card-success h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line me-2 text-success"></i>
                    Pendapatan 7 Hari Terakhir
                </h3>
            </div>
            <div class="card-body">
                <canvas id="chartPendapatan" style="height: 220px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Grafik Transaksi -->
    <div class="col-md-5">
        <div class="card card-outline card-info h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar me-2 text-info"></i>
                    Transaksi 7 Hari Terakhir
                </h3>
            </div>
            <div class="card-body">
                <canvas id="chartTransaksi" style="height: 220px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Tabel Bawah ═══════════════════════════════════════════════════════ -->
<div class="row">
    <!-- Top 5 Pelanggan -->
    <div class="col-md-5">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-trophy me-2 text-warning"></i>
                    Top 5 Pelanggan Teraktif
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="30">#</th>
                                <th>Nama</th>
                                <th class="text-center">Transaksi</th>
                                <th class="text-end">Total Belanja</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($topPelanggan)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    Belum ada data transaksi.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($topPelanggan as $i => $p): ?>
                            <tr>
                                <td class="text-center">
                                    <?php if($i == 0): ?>
                                        <i class="fas fa-medal text-warning"></i>
                                    <?php elseif($i == 1): ?>
                                        <i class="fas fa-medal text-secondary"></i>
                                    <?php elseif($i == 2): ?>
                                        <i class="fas fa-medal" style="color:#cd7f32"></i>
                                    <?php else: ?>
                                        <?= $i + 1 ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($p['nama_pelanggan']) ?></td>
                                <td class="text-center">
                                    <span class="badge text-bg-info"><?= $p['jumlah_transaksi'] ?>x</span>
                                </td>
                                <td class="text-end">Rp <?= number_format($p['total_belanja'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaksi Terbaru -->
    <div class="col-md-7">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clock me-2"></i>
                    Transaksi Terbaru
                </h3>
                <div class="card-tools">
                    <a href="/transaksi/daftar" class="btn btn-sm btn-outline-secondary">
                        Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No Nota</th>
                                <th>Pelanggan</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($transaksiTerbaru)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Belum ada transaksi.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($transaksiTerbaru as $t): ?>
                            <tr>
                                <td>
                                    <a href="/transaksi/detail/<?= $t['id_transaksi'] ?? '#' ?>"
                                       class="text-decoration-none">
                                        <code><?= $t['no_nota'] ?></code>
                                    </a>
                                </td>
                                <td><?= esc($t['nama_pelanggan']) ?></td>
                                <td class="text-end">Rp <?= number_format($t['grand_total'], 0, ',', '.') ?></td>
                                <td class="text-center">
                                    <?php
                                    $sc = match($t['status']) {
                                        'menunggu' => 'text-bg-warning',
                                        'proses'   => 'text-bg-info',
                                        'setrika'  => 'text-bg-secondary',
                                        'selesai'  => 'text-bg-success',
                                        'diambil'  => 'text-bg-primary',
                                        default    => 'text-bg-dark',
                                    };
                                    ?>
                                    <span class="badge <?= $sc ?>"><?= ucfirst($t['status']) ?></span>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $bc = match($t['status_pembayaran']) {
                                        'lunas' => 'text-bg-success',
                                        'cicil' => 'text-bg-warning',
                                        default => 'text-bg-danger',
                                    };
                                    ?>
                                    <span class="badge <?= $bc ?>"><?= ucfirst($t['status_pembayaran']) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Quick Actions ════════════════════════════════════════════════════ -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-body py-2">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="text-muted small me-2">Aksi Cepat:</span>
                    <a href="/transaksi" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Transaksi Baru
                    </a>
                    <a href="/transaksi/daftar" class="btn btn-info btn-sm text-white">
                        <i class="fas fa-list me-1"></i> Daftar Transaksi
                    </a>
                    <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                    <a href="/laporan/rentang" class="btn btn-success btn-sm">
                        <i class="fas fa-chart-bar me-1"></i> Laporan Hari Ini
                    </a>
                    <a href="/inventaris" class="btn btn-warning btn-sm text-dark">
                        <i class="fas fa-boxes me-1"></i> Inventaris
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Chart.js ══════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Data dari PHP
    const labels      = <?= json_encode(array_column($chart7Hari, 'tanggal')) ?>;
    const dataPendapatan = <?= json_encode(array_column($chart7Hari, 'pendapatan')) ?>;
    const dataTransaksi  = <?= json_encode(array_column($chart7Hari, 'transaksi')) ?>;

    // Gradien untuk line chart
    const ctxP = document.getElementById('chartPendapatan').getContext('2d');
    const gradP = ctxP.createLinearGradient(0, 0, 0, 220);
    gradP.addColorStop(0, 'rgba(25,135,84,.35)');
    gradP.addColorStop(1, 'rgba(25,135,84,0)');

    new Chart(ctxP, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Pendapatan',
                data: dataPendapatan,
                borderColor: 'rgb(25,135,84)',
                backgroundColor: gradP,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgb(25,135,84)',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'Rp ' + ctx.raw.toLocaleString('id-ID')
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') }
                }
            }
        }
    });

    // Bar chart transaksi
    new Chart(document.getElementById('chartTransaksi'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Transaksi',
                data: dataTransaksi,
                backgroundColor: 'rgba(13,110,253,.65)',
                borderColor: 'rgb(13,110,253)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
</script>