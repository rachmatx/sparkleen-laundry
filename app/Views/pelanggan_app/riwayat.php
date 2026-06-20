<?= $this->extend('pelanggan_app/layout') ?>
<?= $this->section('content') ?>

<div class="top-nav">
    <a href="<?= base_url('pelanggan-bayar/dashboard') ?>" class="back-btn"><i class="fas fa-arrow-left"></i></a>
    <h5>Riwayat Transaksi</h5>
</div>

<div class="content-area pt-2">
    <?php if(empty($history)): ?>
        <div class="text-center py-5">
            <div class="text-muted mb-3 opacity-50">
                <i class="fas fa-receipt fa-4x"></i>
            </div>
            <h6 class="text-muted">Belum ada riwayat transaksi</h6>
            <p class="small text-muted opacity-75">Transaksi Anda akan muncul di sini.</p>
        </div>
    <?php else: ?>
        <div class="list-group list-group-flush rounded-3 shadow-sm mb-4">
            <?php foreach($history as $item): ?>
                <div class="list-group-item p-3 border-0 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3 <?= $item['type'] == 'IN' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?>" style="width: 40px; height: 40px;">
                                <i class="fas <?= $item['type'] == 'IN' ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark"><?= esc($item['title']) ?></h6>
                                <p class="mb-0 small text-muted"><?= esc($item['merchant']) ?> &bull; <?= date('d M Y, H:i', strtotime($item['date'])) ?></p>
                            </div>
                        </div>
                        <div class="text-end">
                            <h6 class="mb-0 fw-bold <?= $item['type'] == 'IN' ? 'text-success' : 'text-danger' ?>">
                                <?= $item['type'] == 'IN' ? '+' : '-' ?> Rp <?= number_format($item['amount'], 0, ',', '.') ?>
                            </h6>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
