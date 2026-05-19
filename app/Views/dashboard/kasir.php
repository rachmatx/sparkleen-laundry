<?php
// app/Views/dashboard/kasir.php
?>

<!-- ═══ Ringkasan Pesanan Hari Ini ══════════════════════════════════════════ -->
<div class="row mb-3">
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3 class="fs-4"><?= $pesananMasukHariIni ?></h3>
                <p>Pesanan Masuk Hari Ini</p>
            </div>
            <div class="small-box-icon"><i class="fas fa-shopping-bag"></i></div>
            <a href="/transaksi/daftar" class="small-box-footer link-light link-underline-opacity-0">
                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3 class="fs-4"><?= $totalDiproses ?></h3>
                <p>Cucian Perlu Diproses</p>
            </div>
            <div class="small-box-icon"><i class="fas fa-tshirt"></i></div>
            <a href="/transaksi/daftar" class="small-box-footer link-dark link-underline-opacity-0">
                Lihat Daftar <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3 class="fs-4"><?= date('d M Y') ?></h3>
                <p>Tanggal Hari Ini</p>
            </div>
            <div class="small-box-icon"><i class="fas fa-calendar-day"></i></div>
            <a href="/transaksi" class="small-box-footer link-light link-underline-opacity-0">
                Buat Transaksi <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- ═══ Status Cucian Detail ════════════════════════════════════════════════ -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tasks me-2 text-warning"></i>
                    Status Cucian yang Perlu Diproses
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Menunggu -->
                    <div class="col-lg-4 col-md-4">
                        <div class="d-flex align-items-center p-3 rounded border" style="background: #fff8e1;">
                            <div class="me-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                                     style="width:52px;height:52px;background:#f59e0b;font-size:1.4rem;">
                                    <i class="fas fa-hourglass-start"></i>
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold fs-4 text-warning"><?= $statusMenunggu ?></div>
                                <div class="text-muted small">Menunggu Proses</div>
                            </div>
                        </div>
                    </div>
                    <!-- Proses -->
                    <div class="col-lg-4 col-md-4">
                        <div class="d-flex align-items-center p-3 rounded border" style="background: #e3f2fd;">
                            <div class="me-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                                     style="width:52px;height:52px;background:#0ea5e9;font-size:1.4rem;">
                                    <i class="fas fa-spinner"></i>
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold fs-4 text-info"><?= $statusProses ?></div>
                                <div class="text-muted small">Sedang Dicuci</div>
                            </div>
                        </div>
                    </div>
                    <!-- Setrika -->
                    <div class="col-lg-4 col-md-4">
                        <div class="d-flex align-items-center p-3 rounded border" style="background: #f3e8ff;">
                            <div class="me-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                                     style="width:52px;height:52px;background:#8b5cf6;font-size:1.4rem;">
                                    <i class="fas fa-wind"></i>
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold fs-4" style="color:#8b5cf6;"><?= $statusSetrika ?></div>
                                <div class="text-muted small">Sedang Disetrika</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Quick Actions ════════════════════════════════════════════════════════ -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt me-2 text-primary"></i>
                    Aksi Cepat
                </h3>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="/transaksi" class="btn btn-primary" id="btn-transaksi-baru">
                        <i class="fas fa-plus-circle me-2"></i>Transaksi Baru
                    </a>
                    <a href="/transaksi/daftar" class="btn btn-info text-white" id="btn-daftar-transaksi">
                        <i class="fas fa-list-alt me-2"></i>Daftar Transaksi
                    </a>
                    <a href="/pelanggan" class="btn btn-secondary" id="btn-data-pelanggan">
                        <i class="fas fa-users me-2"></i>Data Pelanggan
                    </a>
                    <a href="/layanan" class="btn btn-outline-success" id="btn-data-layanan">
                        <i class="fas fa-soap me-2"></i>Data Layanan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ 5 Transaksi Terbaru ═════════════════════════════════════════════════ -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clock me-2"></i>
                    5 Transaksi Terbaru
                </h3>
                <div class="card-tools">
                    <a href="/transaksi/daftar" class="btn btn-sm btn-outline-secondary" id="btn-lihat-semua-trx">
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
                            <?php helper('shift'); ?>
                            <?php foreach ($transaksiTerbaru as $t): ?>
                            <tr>
                                <td>
                                    <code class="text-primary"><?= esc($t['no_nota']) ?></code>
                                </td>
                                <td><?= esc($t['nama_pelanggan']) ?></td>
                                <td>
                                    <?= date('d/m/Y H:i', strtotime($t['tgl_masuk'])) ?><br>
                                    <small class="text-muted"><i class="fas fa-clock"></i> <?= deteksi_shift($t['tgl_masuk']) ?></small>
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
                                    <a href="/transaksi/detail/<?= $t['id_transaksi'] ?>"
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
</div>
