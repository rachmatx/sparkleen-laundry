<?php
// app/Views/dashboard/admin.php
?>

<!-- ═══ Card Ringkasan Hari Ini ══════════════════════════════════════════════ -->
<div class="row mb-3">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3 class="fs-5"><?= $transaksiHariIni ?></h3>
                <p>Transaksi Hari Ini</p>
            </div>
            <div class="small-box-icon"><i class="fas fa-receipt"></i></div>
            <a href="<?= base_url('/transaksi/daftar') ?>" class="small-box-footer link-light link-underline-opacity-0">
                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3 class="fs-6">Rp <?= number_format($pendapatanHariIni, 0, ',', '.') ?></h3>
                <p>Pendapatan Hari Ini</p>
            </div>
            <div class="small-box-icon"><i class="fas fa-money-bill-wave"></i></div>
            <a href="<?= base_url('/laporan/rentang') ?>" class="small-box-footer link-light link-underline-opacity-0">
                Lihat Laporan <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="small-box <?= !empty($stokMenipis) ? 'text-bg-danger' : 'text-bg-secondary' ?>">
            <div class="inner">
                <h3 class="fs-5"><?= count($stokMenipis) ?></h3>
                <p>Stok Inventaris Menipis</p>
            </div>
            <div class="small-box-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <a href="<?= base_url('/inventaris') ?>" class="small-box-footer link-light link-underline-opacity-0">
                Kelola Inventaris <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3 class="fs-5"><?= count($logAktivitas) ?></h3>
                <p>Log Aktivitas Terkini</p>
            </div>
            <div class="small-box-icon"><i class="fas fa-history"></i></div>
            <a href="#section-log" class="small-box-footer link-light link-underline-opacity-0">
                Lihat Log <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- ═══ Grafik Pendapatan 7 Hari ══════════════════════════════════════════════ -->
<div class="row mb-3">
    <div class="col-md-8">
        <div class="card card-outline card-success h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line me-2 text-success"></i>
                    Pendapatan 7 Hari Terakhir
                </h3>
                <div class="card-tools">
                    <span class="badge text-bg-success">Bar Chart</span>
                </div>
            </div>
            <div class="card-body">
                <canvas id="chartPendapatanAdmin" style="height:240px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-outline card-info h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar me-2 text-info"></i>
                    Jumlah Transaksi 7 Hari
                </h3>
            </div>
            <div class="card-body">
                <canvas id="chartTransaksiAdmin" style="height:240px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Stok Inventaris Menipis ═══════════════════════════════════════════════ -->
<div class="row mb-3">
    <div class="col-md-6">
        <div class="card card-outline <?= !empty($stokMenipis) ? 'card-danger' : 'card-secondary' ?>">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-boxes me-2 <?= !empty($stokMenipis) ? 'text-danger' : 'text-secondary' ?>"></i>
                    Stok Inventaris Menipis
                </h3>
                <div class="card-tools">
                    <a href="<?= base_url('/inventaris') ?>" class="btn btn-sm btn-outline-danger">
                        Kelola <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Barang</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Minimal</th>
                                <th class="text-center">Satuan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stokMenipis)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Semua stok inventaris aman.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($stokMenipis as $inv): ?>
                            <tr>
                                <td><?= esc($inv['nama_barang']) ?></td>
                                <td class="text-center">
                                    <span class="badge text-bg-danger fw-bold"><?= $inv['stok'] ?></span>
                                </td>
                                <td class="text-center text-muted"><?= $inv['stok_minimal'] ?></td>
                                <td class="text-center text-muted"><?= esc($inv['satuan']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Log Aktivitas Terbaru ══════════════════════════════════════════ -->
    <div class="col-md-6" id="section-log">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history me-2 text-primary"></i>
                    Log Aktivitas Terbaru
                </h3>
            </div>
            <div class="card-body p-0" style="max-height:320px;overflow-y:auto;">
                <?php if (empty($logAktivitas)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    Belum ada log aktivitas.
                </div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($logAktivitas as $log): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start py-2 px-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge text-bg-secondary"><?= ucfirst($log['modul'] ?? '-') ?></span>
                                <i class="fas fa-arrow-right text-muted small"></i>
                                <span class="badge text-bg-success"><?= ucfirst($log['aksi'] ?? '-') ?></span>
                            </div>
<?php helper('shift'); ?>
                            <div class="text-muted" style="font-size:0.8rem;">
                                <i class="fas fa-user me-1"></i><?= esc($log['nama_karyawan'] ?? 'Sistem') ?>
                                <span class="ms-2"><i class="fas fa-tag me-1"></i><?= esc($log['nama_referensi'] ?? '-') ?></span>
                                <span class="ms-2"><i class="fas fa-clock me-1"></i><?= deteksi_shift($log['created_at']) ?></span>
                            </div>
                        </div>
                        <div class="text-end text-muted" style="font-size:0.75rem; min-width:90px;">
                            <?= date('d/m H:i', strtotime($log['created_at'])) ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Quick Actions Admin ══════════════════════════════════════════════════ -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="card-body py-2">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="text-muted small me-2 fw-semibold">Aksi Cepat:</span>
                    <a href="<?= base_url('/transaksi') ?>" class="btn btn-primary btn-sm" id="btn-admin-trx-baru">
                        <i class="fas fa-plus me-1"></i> Transaksi Baru
                    </a>
                    <a href="<?= base_url('/karyawan') ?>" class="btn btn-secondary btn-sm" id="btn-admin-karyawan">
                        <i class="fas fa-id-badge me-1"></i> Kelola Karyawan
                    </a>
                    <a href="<?= base_url('/inventaris') ?>" class="btn btn-warning btn-sm text-dark" id="btn-admin-inventaris">
                        <i class="fas fa-boxes me-1"></i> Kelola Inventaris
                    </a>
                    <a href="<?= base_url('/laporan/rentang') ?>" class="btn btn-success btn-sm" id="btn-admin-laporan">
                        <i class="fas fa-chart-bar me-1"></i> Laporan Harian
                    </a>
                    <a href="<?= base_url('/pelanggan') ?>" class="btn btn-outline-info btn-sm" id="btn-admin-pelanggan">
                        <i class="fas fa-users me-1"></i> Data Pelanggan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ 5 Transaksi Terbaru & Cucian Jatuh Tempo ═════════════════════════════════════ -->
<div class="row mb-3">
    <!-- 5 Transaksi Terbaru -->
    <div class="col-lg-7 mb-3 mb-lg-0">
        <div class="card card-outline card-secondary h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clock me-2"></i>
                    5 Transaksi Terbaru
                </h3>
                <div class="card-tools">
                    <a href="<?= base_url('/transaksi/daftar') ?>" class="btn btn-sm btn-outline-secondary" id="btn-lihat-semua-trx-admin">
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
                                <th>Waktu & Shift</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Pembayaran</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transaksiTerbaru)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block text-muted"></i>
                                    Belum ada transaksi.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($transaksiTerbaru as $t): ?>
                            <tr>
                                <td>
                                    <code class="text-primary"><?= esc($t['no_nota']) ?></code>
                                </td>
                                <td><?= esc($t['nama_pelanggan']) ?></td>
                                <td>
                                    <?= date('d/m/Y H:i', strtotime($t['tgl_masuk'])) ?><br>
                                    <small class="text-muted"><i class="fas fa-clock"></i> <?= function_exists('deteksi_shift') ? deteksi_shift($t['tgl_masuk']) : '' ?></small>
                                </td>
                                <td class="text-end fw-semibold">
                                    Rp <?= number_format($t['grand_total'], 0, ',', '.') ?>
                                </td>
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
                                <td class="text-center">
                                    <a href="<?= base_url('/transaksi/detail/' . ($t['id_transaksi'])) ?>"
                                       class="btn btn-sm btn-outline-primary" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
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

    <!-- Cucian Jatuh Tempo Hari Ini -->
    <div class="col-lg-5">
        <div class="card card-outline card-danger h-100">
            <div class="card-header">
                <h3 class="card-title text-danger fw-bold">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Daftar Prioritas (Jatuh Tempo)
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No Nota</th>
                                <th>Pelanggan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cucianJatuhTempo)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                                    Tidak ada cucian yang menunggak hari ini.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($cucianJatuhTempo as $ct): ?>
                            <tr>
                                <td>
                                    <code class="text-danger"><?= esc($ct['no_nota']) ?></code><br>
                                    <span class="badge text-bg-warning"><?= ucfirst($ct['status']) ?></span>
                                </td>
                                <td>
                                    <?= esc($ct['nama_pelanggan']) ?><br>
                                    <small class="text-danger"><i class="fas fa-clock"></i> <?= date('d/m/Y', strtotime($ct['tgl_selesai_estimasi'])) ?></small>
                                </td>
                                <td class="text-center">
                                    <a href="<?= base_url('/transaksi/detail/' . ($ct['id_transaksi'])) ?>" class="btn btn-sm btn-outline-danger" title="Selesaikan">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
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
    const labelsAdmin       = <?= json_encode(array_column($chart7Hari, 'tanggal')) ?>;
    const dataPendAdmin     = <?= json_encode(array_column($chart7Hari, 'pendapatan')) ?>;
    const dataTrxAdmin      = <?= json_encode(array_column($chart7Hari, 'transaksi')) ?>;

    // Bar chart pendapatan
    const ctxPAdmin = document.getElementById('chartPendapatanAdmin').getContext('2d');
    const gradAdmin = ctxPAdmin.createLinearGradient(0, 0, 0, 240);
    gradAdmin.addColorStop(0, 'rgba(25,135,84,0.8)');
    gradAdmin.addColorStop(1, 'rgba(25,135,84,0.2)');

    new Chart(ctxPAdmin, {
        type: 'bar',
        data: {
            labels: labelsAdmin,
            datasets: [{
                label: 'Pendapatan',
                data: dataPendAdmin,
                backgroundColor: gradAdmin,
                borderColor: 'rgb(25,135,84)',
                borderWidth: 1.5,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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

    // Line chart transaksi
    const ctxTAdmin = document.getElementById('chartTransaksiAdmin').getContext('2d');
    const gradT = ctxTAdmin.createLinearGradient(0, 0, 0, 240);
    gradT.addColorStop(0, 'rgba(13,202,240,0.3)');
    gradT.addColorStop(1, 'rgba(13,202,240,0)');

    new Chart(ctxTAdmin, {
        type: 'line',
        data: {
            labels: labelsAdmin,
            datasets: [{
                label: 'Transaksi',
                data: dataTrxAdmin,
                borderColor: 'rgb(13,202,240)',
                backgroundColor: gradT,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgb(13,202,240)',
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
</script>
