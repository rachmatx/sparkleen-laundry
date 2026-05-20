<?php
// app/Views/dashboard/owner.php

// Helper: format growth badge
$growthPositive = $growthNominal >= 0;
?>

<!-- ═══ Header Info Bulan ══════════════════════════════════════════════════════ -->
<div class="row mb-3 align-items-center">
    <div class="col-md-8 mb-2 mb-md-0">
        <div class="alert alert-info d-flex align-items-center mb-0 py-2 shadow-sm">
            <i class="fas fa-calendar-alt me-3 fa-lg"></i>
            <div>
                Menampilkan data untuk periode: <strong><?= $bulanIni ?></strong>.
                Perbandingan dengan bulan lalu: <strong><?= $bulanLaluLabel ?></strong>.
            </div>
        </div>
    </div>
    <div class="col-md-4 text-md-end">
        <div class="dropdown d-inline-block">
            <button class="btn btn-primary dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-file-export me-2"></i>Export Laporan
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item py-2" href="/laporan/export_harian_pdf"><i class="fas fa-file-pdf text-danger me-2"></i>Export Harian PDF</a></li>
                <li><a class="dropdown-item py-2" href="/laporan/export_bulanan_pdf"><i class="fas fa-file-pdf text-danger me-2"></i>Export Bulanan PDF</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item py-2" href="/laporan/export_harian_excel"><i class="fas fa-file-excel text-success me-2"></i>Export Harian Excel</a></li>
                <li><a class="dropdown-item py-2" href="/laporan/export_bulanan_excel"><i class="fas fa-file-excel text-success me-2"></i>Export Bulanan Excel</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- ═══ Card KPI Utama ════════════════════════════════════════════════════════ -->
<div class="row mb-3">
    <!-- Omzet Bulan Ini -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="small-box text-bg-success h-100 d-flex flex-column">
            <div class="inner flex-grow-1">
                <h3 class="fs-5 text-truncate">Rp <?= number_format($omzetBulanIni, 0, ',', '.') ?></h3>
                <p>
                    Omzet Bulan Ini<br>
                    <small class="opacity-75">Total pendapatan kotor</small>
                </p>
            </div>
            <div class="small-box-icon"><i class="fas fa-money-bill-wave"></i></div>
            <a href="/laporan/bulanan" class="small-box-footer link-light link-underline-opacity-0 mt-auto">
                Lihat Laporan <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <!-- Perbandingan vs Bulan Lalu -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="small-box <?= $growthPositive ? 'text-bg-info' : 'text-bg-danger' ?> h-100 d-flex flex-column">
            <div class="inner flex-grow-1">
                <h3 class="fs-5 text-truncate">
                    <?= $growthPositive ? '+' : '' ?><?= $growthPersen ?>%
                </h3>
                <p>
                    Growth vs Bulan Lalu<br>
                    <small class="opacity-75">
                        <?= $growthPositive ? '+' : '' ?>Rp <?= number_format(abs($growthNominal), 0, ',', '.') ?>
                    </small>
                </p>
            </div>
            <div class="small-box-icon">
                <i class="fas <?= $growthPositive ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' ?>"></i>
            </div>
            <a href="/laporan/bulanan" class="small-box-footer link-light link-underline-opacity-0 mt-auto">
                Detail <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <!-- Estimasi Keuntungan Bersih -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="small-box <?= $keuntunganBersih >= 0 ? 'text-bg-primary' : 'text-bg-danger' ?> h-100 d-flex flex-column">
            <div class="inner flex-grow-1">
                <h3 class="fs-5 text-truncate">
                    Rp <?= number_format($keuntunganBersih, 0, ',', '.') ?>
                </h3>
                <p>
                    Estimasi Keuntungan<br>
                    <small class="opacity-75">Omzet dikurangi biaya</small>
                </p>
            </div>
            <div class="small-box-icon"><i class="fas fa-chart-pie"></i></div>
            <a href="#section-biaya" class="small-box-footer link-light link-underline-opacity-0 mt-auto">
                Rincian Biaya <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <!-- Pelanggan Baru -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="small-box text-bg-warning h-100 d-flex flex-column">
            <div class="inner flex-grow-1">
                <h3 class="fs-5 text-truncate"><?= $pelangganBaruBulanIni ?></h3>
                <p>
                    Pelanggan Baru Bulan Ini<br>
                    <small class="opacity-75">dari <?= $totalPelanggan ?> total pelanggan</small>
                </p>
            </div>
            <div class="small-box-icon"><i class="fas fa-user-plus"></i></div>
            <a href="/pelanggan" class="small-box-footer link-dark link-underline-opacity-0 mt-auto">
                Kelola Data <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- ═══ Baris Grafik + Rincian Biaya ═════════════════════════════════════════ -->
<div class="row mb-3">
    <!-- Pie Chart Metode Pembayaran -->
    <div class="col-md-4">
        <div class="card card-outline card-primary h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie me-2 text-primary"></i>
                    Metode Pembayaran
                </h3>
                <div class="card-tools">
                    <span class="badge text-bg-primary">Bulan Ini</span>
                </div>
            </div>
            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                <?php if (empty($metodePembayaran)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    Belum ada data pembayaran bulan ini.
                </div>
                <?php else: ?>
                <canvas id="chartMetodeBayar" style="max-height:230px;"></canvas>
                <div class="mt-3 w-100">
                    <?php
                    $totalSemuaPembayaran = array_sum(array_column($metodePembayaran, 'total'));
                    $metodeLabelMap = [
                        'cash'     => ['label' => 'Cash',     'color' => '#198754'],
                        'qris'     => ['label' => 'QRIS',     'color' => '#0d6efd'],
                        'transfer' => ['label' => 'Transfer',  'color' => '#0dcaf0'],
                    ];
                    foreach ($metodePembayaran as $mp):
                        $key   = strtolower($mp['metode_bayar']);
                        $info  = $metodeLabelMap[$key] ?? ['label' => ucfirst($mp['metode_bayar']), 'color' => '#6c757d'];
                        $persen = $totalSemuaPembayaran > 0 ? round(($mp['total'] / $totalSemuaPembayaran) * 100, 1) : 0;
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle d-inline-block"
                                  style="width:10px;height:10px;background:<?= $info['color'] ?>;"></span>
                            <span class="small"><?= $info['label'] ?> <span class="text-muted ms-1">(<?= $persen ?>%)</span></span>
                        </div>
                        <span class="small fw-semibold">Rp <?= number_format($mp['total'], 0, ',', '.') ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Rincian Biaya & Keuntungan -->
    <div class="col-md-4" id="section-biaya">
        <div class="card card-outline card-secondary h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calculator me-2 text-secondary"></i>
                    Rincian Estimasi Keuntungan
                </h3>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted">Omzet Bulan Ini</td>
                        <td class="text-end fw-semibold text-success">
                            Rp <?= number_format($omzetBulanIni, 0, ',', '.') ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">
                            <i class="fas fa-minus text-danger me-1" style="font-size:0.7rem;"></i>
                            Total Gaji Karyawan
                        </td>
                        <td class="text-end text-danger">
                            Rp <?= number_format($totalGajiKaryawan, 0, ',', '.') ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">
                            <i class="fas fa-minus text-danger me-1" style="font-size:0.7rem;"></i>
                            Pembelian Inventaris
                        </td>
                        <td class="text-end text-danger">
                            Rp <?= number_format($totalBeliInventarisBulanIni, 0, ',', '.') ?>
                        </td>
                    </tr>
                    <tr class="table-<?= $keuntunganBersih >= 0 ? 'success' : 'danger' ?> fw-bold">
                        <td>Estimasi Keuntungan Bersih</td>
                        <td class="text-end">
                            Rp <?= number_format($keuntunganBersih, 0, ',', '.') ?>
                        </td>
                    </tr>
                </table>

                <hr class="my-3">

                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Omzet Bulan Lalu</span>
                    <span>Rp <?= number_format($omzetBulanLalu, 0, ',', '.') ?></span>
                </div>
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted">Selisih</span>
                    <span class="fw-semibold <?= $growthPositive ? 'text-success' : 'text-danger' ?>">
                        <?= $growthPositive ? '+' : '' ?>Rp <?= number_format($growthNominal, 0, ',', '.') ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-muted">Pertumbuhan</span>
                    <span class="badge <?= $growthPositive ? 'text-bg-success' : 'text-bg-danger' ?>">
                        <?= $growthPositive ? '+' : '' ?><?= $growthPersen ?>%
                    </span>
                </div>

                <div class="mt-3">
                    <div class="small text-muted mb-1">Pertumbuhan Pelanggan Bulan Ini</div>
                    <div class="progress" style="height:20px;">
                        <?php
                        $pct = $totalPelanggan > 0 ? round(($pelangganBaruBulanIni / $totalPelanggan) * 100, 1) : 0;
                        ?>
                        <div class="progress-bar bg-warning text-dark fw-semibold"
                             style="width:<?= max($pct, 3) ?>%">
                            <?= $pelangganBaruBulanIni ?> baru (<?= $pct ?>%)
                        </div>
                    </div>
                    <div class="small text-muted mt-1">
                        <?= $pelangganBaruBulanIni ?> dari <?= $totalPelanggan ?> total pelanggan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Piutang & Tagihan -->
    <div class="col-md-4">
        <div class="card card-outline card-warning h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-hand-holding-usd me-2 text-warning"></i>
                    Status Piutang & Tagihan
                </h3>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <?php
                $lunas = $piutangStats['lunas'] ?? 0;
                $piutang = $piutangStats['piutang'] ?? 0;
                $totalTagihan = $lunas + $piutang;
                $persenLunas = $totalTagihan > 0 ? round(($lunas / $totalTagihan) * 100) : 0;
                $persenPiutang = $totalTagihan > 0 ? round(($piutang / $totalTagihan) * 100) : 0;
                ?>
                
                <div class="text-center mb-4">
                    <h5 class="text-muted mb-1">Total Nilai Transaksi</h5>
                    <h3 class="fw-bold mb-0">Rp <?= number_format($totalTagihan, 0, ',', '.') ?></h3>
                    <small class="text-muted">(Bulan Ini)</small>
                </div>

                <!-- Progress Bar -->
                <div class="progress mb-3" style="height: 25px; border-radius: 12px; box-shadow: inset 0 1px 2px rgba(0,0,0,.1);">
                    <?php if($lunas > 0): ?>
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated text-center fw-bold" role="progressbar" style="width: <?= max($persenLunas, 5) ?>%" aria-valuenow="<?= $persenLunas ?>" aria-valuemin="0" aria-valuemax="100">
                        <?= $persenLunas ?>%
                    </div>
                    <?php endif; ?>
                    <?php if($piutang > 0): ?>
                    <div class="progress-bar bg-warning text-dark text-center fw-bold" role="progressbar" style="width: <?= max($persenPiutang, 5) ?>%" aria-valuenow="<?= $persenPiutang ?>" aria-valuemin="0" aria-valuemax="100">
                        <?= $persenPiutang ?>%
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Legend / Rincian -->
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <div class="d-flex align-items-center">
                        <span class="rounded-circle bg-success me-2" style="width:12px; height:12px;"></span>
                        <span class="fw-medium">Lunas</span>
                    </div>
                    <span class="fw-bold text-success">Rp <?= number_format($lunas, 0, ',', '.') ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center px-1">
                    <div class="d-flex align-items-center">
                        <span class="rounded-circle bg-warning me-2" style="width:12px; height:12px;"></span>
                        <span class="fw-medium">Belum Lunas / Cicil</span>
                    </div>
                    <span class="fw-bold text-warning" style="text-shadow: 0 0 1px rgba(0,0,0,0.2);">Rp <?= number_format($piutang, 0, ',', '.') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Top Layanan & Top Kasir ═══════════════════════════════════════════════ -->
<div class="row">
    <!-- Top 5 Layanan Terlaris -->
    <div class="col-md-6 mb-3">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-star me-2 text-warning"></i>
                    Top 5 Layanan Terlaris
                </h3>
                <div class="card-tools">
                    <span class="badge text-bg-success">Bulan Ini</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="30">#</th>
                                <th>Layanan</th>
                                <th class="text-center">Order</th>
                                <th class="text-end">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topLayanan)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Belum ada data layanan bulan ini.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($topLayanan as $i => $lay): ?>
                            <tr>
                                <td class="text-center fw-bold text-warning"><?= $i + 1 ?></td>
                                <td><?= esc($lay['nama_layanan']) ?></td>
                                <td class="text-center">
                                    <span class="badge text-bg-info"><?= $lay['jumlah_order'] ?>x</span>
                                </td>
                                <td class="text-end fw-semibold">
                                    Rp <?= number_format($lay['total_pendapatan'], 0, ',', '.') ?>
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

    <!-- Top 5 Performa Kasir -->
    <div class="col-md-6 mb-3">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-trophy me-2 text-warning"></i>
                    Top 5 Performa Kasir
                </h3>
                <div class="card-tools">
                    <span class="badge text-bg-primary">Bulan Ini</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="30">#</th>
                                <th>Nama Kasir</th>
                                <th class="text-center">Transaksi</th>
                                <th class="text-end">Total Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topKasir)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Belum ada data kinerja kasir bulan ini.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($topKasir as $i => $k): ?>
                            <tr>
                                <td class="text-center">
                                    <?php if ($i == 0): ?>
                                        <i class="fas fa-medal text-warning"></i>
                                    <?php elseif ($i == 1): ?>
                                        <i class="fas fa-medal text-secondary"></i>
                                    <?php elseif ($i == 2): ?>
                                        <i class="fas fa-medal" style="color:#cd7f32"></i>
                                    <?php else: ?>
                                        <?= $i + 1 ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($k['nama_karyawan']) ?></td>
                                <td class="text-center">
                                    <span class="badge text-bg-primary fw-bold"><?= $k['jumlah_transaksi'] ?></span>
                                </td>
                                <td class="text-end fw-semibold">
                                    Rp <?= number_format($k['total_omzet'], 0, ',', '.') ?>
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

<!-- ═══ Chart.js ═════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (!empty($metodePembayaran)): ?>
    const metodeLabelMap = {
        cash:     { label: 'Cash',     color: '#198754' },
        qris:     { label: 'QRIS',     color: '#0d6efd' },
        transfer: { label: 'Transfer',  color: '#0dcaf0' },
    };

    const rawMetode = <?= json_encode($metodePembayaran) ?>;

    const pieLabels = rawMetode.map(m => {
        const key = m.metode_bayar.toLowerCase();
        return (metodeLabelMap[key] || { label: m.metode_bayar }).label;
    });

    const pieData = rawMetode.map(m => parseFloat(m.total));

    const pieColors = rawMetode.map(m => {
        const key = m.metode_bayar.toLowerCase();
        return (metodeLabelMap[key] || { color: '#6c757d' }).color;
    });

    new Chart(document.getElementById('chartMetodeBayar'), {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: pieColors,
                borderWidth: 2,
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '60%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const val = ctx.raw;
                            const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                            return ctx.label + ': Rp ' + val.toLocaleString('id-ID') + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });
<?php endif; ?>
</script>
