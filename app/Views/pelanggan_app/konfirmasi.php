<?= $this->extend('pelanggan_app/layout') ?>
<?= $this->section('content') ?>

<div class="top-nav">
    <a href="<?= base_url('pelanggan-bayar/dashboard') ?>" class="back-btn"><i class="fas fa-times"></i></a>
    <h5>Konfirmasi Pembayaran</h5>
</div>

<div class="content-area">
    
    <div class="glass-card p-4 text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-secondary-custom rounded-circle mb-3" style="width: 60px; height: 60px;">
            <i class="fas fa-store fa-2x text-primary-custom"></i>
        </div>
        <h5 class="fw-bold mb-1"><?= esc($merchant) ?></h5>
        <p class="text-muted small mb-3">Merchant ID: <?= esc($trx_id) ?></p>
        
        <h2 class="fw-bold mb-0">Rp <?= number_format($amount, 0, ',', '.') ?></h2>
    </div>

    <!-- PIN Input UI -->
    <div class="glass-card p-4 text-center">
        <h6 class="fw-bold mb-3">Masukkan PIN Pembayaran</h6>
        <p class="text-muted small mb-4">Silakan masukkan 6 digit PIN Sparkleen Pay Anda (123456).</p>
        
        <form id="pinForm">
            <input type="password" id="pinInput" class="form-control form-control-lg text-center fw-bold tracking-widest border-0 bg-light mx-auto" style="max-width: 200px; letter-spacing: 10px;" maxlength="6" autocomplete="off" required>
            
            <button type="submit" class="btn btn-primary-custom w-100 btn-lg mt-4" id="btnBayar">
                <i class="fas fa-lock me-2"></i> Bayar Sekarang
            </button>
        </form>
    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $('#pinForm').on('submit', function(e) {
        e.preventDefault();
        const pin = $('#pinInput').val();
        
        if (pin.length !== 6) {
            Swal.fire('Perhatian', 'PIN harus 6 digit.', 'warning');
            return;
        }

        $('#btnBayar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');

        $.ajax({
            url: '<?= base_url('pelanggan-bayar/prosesBayar') ?>',
            type: 'POST',
            data: {
                pin: pin,
                trx_id: '<?= esc($trx_id) ?>',
                amount: <?= (float)$amount ?>,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        window.location.href = '<?= base_url('pelanggan-bayar/dashboard') ?>';
                    });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                    $('#btnBayar').prop('disabled', false).html('<i class="fas fa-lock me-2"></i> Bayar Sekarang');
                }
            },
            error: function() {
                Swal.fire('Error', 'Gagal memproses pembayaran ke server.', 'error');
                $('#btnBayar').prop('disabled', false).html('<i class="fas fa-lock me-2"></i> Bayar Sekarang');
            }
        });
    });
</script>
<?= $this->endSection() ?>
