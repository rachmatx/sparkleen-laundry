<?php
// Helper: status badge class
function statusBadgeClass(string $status): string {
    return match($status) {
        'menunggu' => 'text-bg-warning',
        'proses'   => 'text-bg-info',
        'setrika'  => 'text-bg-secondary',
        'selesai'  => 'text-bg-success',
        'diambil'  => 'text-bg-primary',
        default    => 'text-bg-dark',
    };
}
function bayarBadgeClass(string $status): string {
    return match($status) {
        'lunas'  => 'text-bg-success',
        'cicil'  => 'text-bg-warning',
        default  => 'text-bg-danger',
    };
}
?>

<?php
$flashWaLink    = session()->getFlashdata('wa_link');
$flashWaSuccess = session()->getFlashdata('wa_success');
$flashWaWarning = session()->getFlashdata('wa_warning');
$waHref         = $flashWaLink ?: ($waLink ?? null);
?>

<?php if ($flashWaSuccess): ?>
<div class="alert alert-success alert-dismissible">
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    <i class="fab fa-whatsapp me-1"></i> <?= esc($flashWaSuccess) ?>
</div>
<?php endif; ?>

<?php if ($flashWaWarning): ?>
<div class="alert alert-warning alert-dismissible">
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    <i class="fas fa-exclamation-triangle me-1"></i> <?= esc($flashWaWarning) ?>
</div>
<?php endif; ?>

<!-- Action Buttons Row -->
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex flex-wrap gap-2">
            <a href="/transaksi/daftar" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
            <a href="/transaksi/nota/<?= $transaksi['id_transaksi'] ?>" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-print me-1"></i> Cetak Nota
            </a>
            <?php if(in_array(session()->get('level'), ['admin', 'kasir'])): ?>
            <a href="/transaksi/pembayaran/<?= $transaksi['id_transaksi'] ?>" class="btn btn-success">
                <i class="fas fa-credit-card me-1"></i> Kelola Pembayaran
            </a>
            <?php if($transaksi['status'] != 'diambil'): ?>
            <a href="/transaksi/ubah-status/<?= $transaksi['id_transaksi'] ?>" class="btn btn-warning text-dark">
                <i class="fas fa-exchange-alt me-1"></i> Update Status
            </a>
            <?php endif; ?>
            <?php if (!empty($waHref) && $transaksi['status'] === 'selesai'): ?>
            <a href="<?= esc($waHref) ?>" target="_blank" rel="noopener" class="btn btn-success">
                <i class="fab fa-whatsapp me-1"></i> Kirim WhatsApp
            </a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <!-- Info Transaksi -->
    <div class="col-md-4">
        <div class="card card-outline card-info h-100">
            <div class="card-header">
                <h3 class="card-title">Informasi Transaksi</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted" width="45%">No Nota</td>
                            <td><strong><?= $transaksi['no_nota'] ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Pelanggan</td>
                            <td><?= esc($transaksi['nama_pelanggan']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kasir</td>
                            <td><?= esc($transaksi['nama_karyawan']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tgl Masuk</td>
                            <td><?= date('d/m/Y H:i', strtotime($transaksi['tgl_masuk'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Est. Selesai</td>
                            <td><?= date('d/m/Y H:i', strtotime($transaksi['tgl_selesai_estimasi'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total Berat</td>
                            <td><?= $transaksi['total_berat'] ?> kg</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Grand Total</td>
                            <td><strong>Rp <?= number_format($transaksi['grand_total'], 0, ',', '.') ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                <span class="badge <?= statusBadgeClass($transaksi['status']) ?>">
                                    <?= ucfirst($transaksi['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Pembayaran</td>
                            <td>
                                <span class="badge <?= bayarBadgeClass($transaksi['status_pembayaran']) ?>">
                                    <?= strtoupper($transaksi['status_pembayaran']) ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Detail Layanan & Log Status -->
    <div class="col-md-8">
        <!-- Detail Layanan -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Detail Layanan</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Layanan</th>
                                <th class="text-center">Berat</th>
                                <th class="text-center">Harga/kg</th>
                                <th class="text-center">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($detail as $d): ?>
                            <tr>
                                <td><?= esc($d['nama_layanan']) ?></td>
                                <td class="text-center"><?= $d['berat'] ?> kg</td>
                                <td class="text-center">Rp <?= number_format($d['harga_satuan'], 0, ',', '.') ?></td>
                                <td class="text-center">Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <th colspan="3" class="text-center">Grand Total</th>
                                <th class="text-center">Rp <?= number_format($transaksi['grand_total'], 0, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Riwayat Status -->
        <?php if(!empty($statusLog)): ?>
        <div class="card card-outline card-secondary mt-3">
            <div class="card-header">
                <h3 class="card-title">Riwayat Perubahan Status</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Status Lama</th>
                                <th>Status Baru</th>
                                <th>Diubah Oleh</th>
                                <th>Tanggal</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($statusLog as $log): ?>
                            <tr>
                                <td><?= ucfirst($log['status_lama']) ?></td>
                                <td><?= ucfirst($log['status_baru']) ?></td>
                                <td><?= esc($log['nama_karyawan']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($log['tgl_ubah'])) ?></td>
                                <td><?= $log['catatan'] ?? '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>