<?= $this->extend('pelanggan_app/layout') ?>
<?= $this->section('content') ?>

<div class="top-nav">
    <a href="<?= base_url('pelanggan-bayar/dashboard') ?>" class="back-btn"><i class="fas fa-arrow-left"></i></a>
    <h5>Scan QRIS</h5>
</div>

<div class="content-area d-flex flex-column" style="height: 100%;">
    <div class="text-center mb-4">
        <p class="text-muted small">Arahkan kamera ke QR Code yang ada di layar Kasir Sparkleen Laundry.</p>
    </div>

    <div class="flex-grow-1 position-relative">
        <!-- Scanner Box -->
        <div id="reader" class="rounded-4 overflow-hidden shadow-sm" style="width: 100%; border:none;"></div>
        
        <div id="camera-fallback" class="position-absolute top-0 start-0 w-100 h-100 d-none flex-column align-items-center justify-content-center bg-light rounded-4 shadow-sm" style="z-index: 5; border: 2px dashed #cbd5e1;">
            <div class="text-secondary mb-3">
                <i class="fas fa-camera-slash fa-3x"></i>
            </div>
            <h6 class="fw-bold text-secondary">Kamera Tidak Tersedia</h6>
            <p class="small text-muted text-center px-4 mb-3">Sistem tidak dapat mengakses kamera Anda. Silakan gunakan metode transfer untuk membayar tagihan.</p>
            <a href="<?= base_url('pelanggan-bayar/transfer') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold">
                <i class="fas fa-university me-1"></i> Gunakan Transfer
            </a>
        </div>

        <div id="loading" class="position-absolute top-0 start-0 w-100 h-100 d-none flex-column align-items-center justify-content-center" style="background: rgba(255,255,255,0.9); z-index: 10;">
            <div class="spinner-border text-primary-custom mb-2" role="status"></div>
            <div class="fw-bold text-dark small">Memverifikasi QR...</div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const html5QrCode = new Html5Qrcode("reader");
        const qrCodeSuccessCallback = (decodedText, decodedResult) => {
            // Stop scanning
            html5QrCode.stop().then((ignore) => {
                $('#loading').removeClass('d-none').addClass('d-flex');
                
                // Cek QR text via AJAX
                $.ajax({
                    url: '<?= base_url('pelanggan-bayar/cekQr') ?>',
                    type: 'POST',
                    data: {
                        qr_text: decodedText,
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            // Redirect ke halaman konfirmasi
                            window.location.href = `<?= base_url('pelanggan-bayar/konfirmasi') ?>?trx_id=${res.transaction_id}&merchant=${encodeURIComponent(res.merchant_name)}&amount=${res.amount}`;
                        } else {
                            Swal.fire('Gagal', res.message, 'error').then(() => {
                                window.location.reload();
                            });
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Gagal menghubungi server.', 'error').then(() => {
                            window.location.reload();
                        });
                    }
                });

            }).catch((err) => {
                console.error("Failed to stop scanning.", err);
            });
        };

        const config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };
        html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback)
        .catch(err => {
            console.error("Camera error:", err);
            // Tampilkan fallback kamera
            $('#reader').addClass('d-none');
            $('#camera-fallback').removeClass('d-none').addClass('d-flex');
        });
    });
</script>
<?= $this->endSection() ?>
