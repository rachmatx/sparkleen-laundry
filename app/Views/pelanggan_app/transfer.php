<?= $this->extend('pelanggan_app/layout') ?>
<?= $this->section('content') ?>

<div class="top-nav">
    <a href="<?= base_url('pelanggan-bayar/dashboard') ?>" class="back-btn"><i class="fas fa-arrow-left"></i></a>
    <h5>Transfer Bank</h5>
</div>

<div class="content-area">
    <div class="glass-card p-4">
        <form id="transferForm">
            <div class="mb-4">
                <label class="form-label small fw-semibold text-muted">Bank Tujuan</label>
                <select class="form-select form-select-lg border-0 bg-light" id="bank" required>
                    <option value="" disabled selected>Pilih Bank</option>
                    <option value="Mandiri">Bank Mandiri</option>
                    <option value="BCA">Bank BCA</option>
                    <option value="BNI">Bank BNI</option>
                    <option value="BRI">Bank BRI</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold text-muted">Nomor Rekening Tujuan</label>
                <input type="text" class="form-control form-control-lg border-0 bg-light" id="rekening" placeholder="Contoh: 137-00-998877-6" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold text-muted">Nominal Transfer</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text border-0 bg-light fw-bold text-muted">Rp</span>
                    <input type="number" class="form-control border-0 bg-light fw-bold" id="nominal" placeholder="0" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary-custom w-100 btn-lg mt-3" id="btnLanjut">
                Lanjut
            </button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $('#transferForm').on('submit', function(e) {
        e.preventDefault();
        const rekening = $('#rekening').val();
        const nominal = $('#nominal').val();

        $('#btnLanjut').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memeriksa...');

        $.ajax({
            url: '<?= base_url('pelanggan-bayar/cekTransfer') ?>',
            type: 'POST',
            data: {
                rekening: rekening,
                nominal: nominal,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    window.location.href = `<?= base_url('pelanggan-bayar/konfirmasi') ?>?trx_id=${res.transaction_id}&merchant=${encodeURIComponent(res.merchant_name)}&amount=${res.amount}`;
                } else {
                    Swal.fire('Info', res.message, 'info');
                    $('#btnLanjut').prop('disabled', false).text('Lanjut');
                }
            },
            error: function() {
                Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                $('#btnLanjut').prop('disabled', false).text('Lanjut');
            }
        });
    });
</script>
<?= $this->endSection() ?>
