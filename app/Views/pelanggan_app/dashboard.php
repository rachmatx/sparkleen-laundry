<?= $this->extend('pelanggan_app/layout') ?>
<?= $this->section('content') ?>

<div class="top-nav justify-content-between">
    <div class="d-flex align-items-center">
        <div class="bg-primary-custom text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:36px; height:36px; font-weight:bold;">
            T
        </div>
        <span class="fw-bold">Hai, Test User!</span>
    </div>
    <a href="<?= base_url('pelanggan-bayar/logout') ?>" class="text-danger"><i class="fas fa-sign-out-alt"></i></a>
</div>

<div class="content-area pt-2">
    <!-- Balance Card -->
    <div class="glass-card bg-primary-custom text-white p-4 mb-4" style="background: linear-gradient(135deg, #0ea5e9, #3b82f6); border:none;">
        <p class="mb-1 text-white-50 small fw-semibold">Saldo Sparkleen Pay</p>
        <h2 class="fw-bold mb-3">Rp <?= number_format($saldo, 0, ',', '.') ?></h2>
        
        <div class="d-flex gap-3">
            <a href="<?= base_url('pelanggan-bayar/topup') ?>" class="btn btn-light btn-sm rounded-pill px-3 fw-bold text-primary text-decoration-none">
                <i class="fas fa-plus me-1"></i> Top Up
            </a>
            <a href="<?= base_url('pelanggan-bayar/riwayat') ?>" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-bold text-decoration-none">
                <i class="fas fa-history me-1"></i> Riwayat
            </a>
        </div>
    </div>

    <h6 class="fw-bold mb-3 text-muted">Layanan Pembayaran</h6>
    
    <div class="row g-3">
        <div class="col-6">
            <a href="<?= base_url('pelanggan-bayar/scan') ?>" class="text-decoration-none">
                <div class="glass-card p-3 text-center h-100 transition-all shadow-sm">
                    <div class="d-inline-flex bg-secondary-custom rounded-circle p-3 mb-2">
                        <i class="fas fa-qrcode fa-2x text-primary-custom"></i>
                    </div>
                    <div class="fw-bold text-dark small">Scan QRIS</div>
                </div>
            </a>
        </div>
        <div class="col-6">
            <a href="<?= base_url('pelanggan-bayar/transfer') ?>" class="text-decoration-none">
                <div class="glass-card p-3 text-center h-100 transition-all shadow-sm">
                    <div class="d-inline-flex bg-secondary-custom rounded-circle p-3 mb-2">
                        <i class="fas fa-university fa-2x text-primary-custom"></i>
                    </div>
                    <div class="fw-bold text-dark small">Transfer Bank</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Promo Banner -->
    <div class="mt-4 rounded-4 overflow-hidden position-relative shadow-sm" style="background: linear-gradient(45deg, #f87171, #fb923c); color:white;">
        <div class="p-4">
            <h5 class="fw-bold mb-1">Promo Khusus!</h5>
            <p class="small mb-0 opacity-75">Dapatkan diskon 10% dengan Sparkleen Pay di transaksi pertamamu.</p>
        </div>
        <i class="fas fa-percent position-absolute text-white opacity-25" style="font-size: 5rem; right: -10px; bottom: -20px;"></i>
    </div>
</div>

<?= $this->endSection() ?>
