<?= $this->extend('pelanggan_app/layout') ?>
<?= $this->section('content') ?>

<div class="content-area d-flex flex-column justify-content-center" style="min-height: 100vh;">
    <div class="text-center mb-5">
        <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm mb-3" style="width: 80px; height: 80px;">
            <i class="fas fa-wallet fa-3x text-primary-custom"></i>
        </div>
        <h2 class="fw-bold mb-1">Sparkleen Pay</h2>
        <p class="text-muted small">Aplikasi Pelanggan Prototype</p>
    </div>

    <div class="glass-card p-4">
        <h5 class="fw-bold mb-4 text-center">Masuk ke Akun Anda</h5>
        
        <?php if(session()->getFlashdata('error')): ?>
            <div class="alert alert-danger py-2 small rounded-3">
                <i class="fas fa-exclamation-circle me-1"></i> <?= esc(session()->getFlashdata('error')) ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('pelanggan-bayar/doLogin') ?>" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label small text-muted fw-semibold">Username</label>
                <input type="text" name="username" class="form-control form-control-lg bg-light border-0" placeholder="Masukkan test" value="test" required>
            </div>
            <div class="mb-4">
                <label class="form-label small text-muted fw-semibold">Password</label>
                <input type="password" name="password" class="form-control form-control-lg bg-light border-0" placeholder="Masukkan test123" value="test123" required>
            </div>
            <button type="submit" class="btn btn-primary-custom w-100 btn-lg fs-6">Masuk Sekarang</button>
        </form>
    </div>
    
    <div class="mt-5 text-center text-muted small">
        &copy; 2026 Sparkleen Laundry
    </div>
</div>

<?= $this->endSection() ?>
