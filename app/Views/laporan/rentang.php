<!-- Filter & Export -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Filter Laporan Rentang Tanggal</h3>
                <div class="card-tools">
                    <a href="/laporan" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="get" action="/laporan/rentang" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tanggal Awal</label>
                        <input type="date" class="form-control" name="tgl_awal" value="<?= $tgl_awal ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tanggal Akhir</label>
                        <input type="date" class="form-control" name="tgl_akhir" value="<?= $tgl_akhir ?>">
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Tampilkan
                        </button>
                    </div>
                    <div class="col-md-auto">
                        <a href="/laporan/export_rentang_pdf?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-warning text-dark">
                            <i class="fas fa-file-pdf me-1"></i> Export PDF
                        </a>
                    </div>
                    <div class="col-md-auto">
                        <a href="/laporan/export_rentang_excel?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-success">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3>Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></h3>
                <p>Total Pendapatan</p>
            </div>
            <div class="small-box-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3><?= $jumlahTransaksi ?></h3>
                <p>Jumlah Transaksi</p>
            </div>
            <div class="small-box-icon">
                <i class="fas fa-receipt"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3><?= number_format($totalBerat, 1) ?> kg</h3>
                <p>Total Berat</p>
            </div>
            <div class="small-box-icon">
                <i class="fas fa-weight"></i>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Data -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    Detail Transaksi &mdash; <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?>
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No Nota</th>
                                <th>Pelanggan</th>
                                <th>Tgl Masuk</th>
                                <th class="text-center">Total Berat</th>
                                <th class="text-end">Grand Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Status Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($transaksi)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    Tidak ada transaksi pada rentang tanggal ini.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($transaksi as $t): ?>
                            <tr>
                                <td><code><?= $t['no_nota'] ?></code></td>
                                <td><?= esc($t['nama_pelanggan']) ?></td>
                                <td><?= date('d/m/Y', strtotime($t['tgl_masuk'])) ?></td>
                                <td class="text-center"><?= $t['total_berat'] ?> kg</td>
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
            <?php if ($hasPrev || $hasNext): ?>
            <div class="card-footer d-flex justify-content-between align-items-center bg-transparent border-top">
                <div>
                    <span class="text-muted small">Halaman <strong><?= $page ?></strong> | Dicetak pada: <?= date('d/m/Y H:i:s') ?></span>
                </div>
                <div class="btn-group">
                    <?php if ($hasPrev): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-chevron-left me-1"></i> Sebelumnya
                        </a>
                    <?php endif; ?>
                    <?php if ($hasNext): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-outline-secondary btn-sm ms-2">
                            Selanjutnya <i class="fas fa-chevron-right ms-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="card-footer text-muted small">
                Dicetak pada: <?= date('d/m/Y H:i:s') ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>