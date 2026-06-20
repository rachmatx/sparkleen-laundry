<?= $this->extend('pelanggan_app/layout') ?>
<?= $this->section('content') ?>

<div class="top-nav">
    <a href="<?= base_url('pelanggan-bayar/dashboard') ?>" class="back-btn"><i class="fas fa-arrow-left"></i></a>
    <h5>Top Up Saldo</h5>
</div>

<div class="content-area">
    <div class="text-center mb-4">
        <div class="bg-primary-custom text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 60px; height: 60px;">
            <i class="fas fa-wallet fa-2x"></i>
        </div>
        <h4 class="fw-bold">Isi Saldo Sparkleen Pay</h4>
        <p class="text-muted small">Pilih nominal top up atau masukkan jumlah secara manual.</p>
    </div>

    <form id="topup-form">
        <div class="mb-4">
            <label class="form-label fw-bold small text-muted">Nominal Top Up</label>
            <div class="input-group input-group-lg mb-3 shadow-sm rounded-3 overflow-hidden">
                <span class="input-group-text bg-white border-0 fw-bold text-dark">Rp</span>
                <input type="text" class="form-control border-0 fw-bold fs-4" id="amount_display" placeholder="0" autocomplete="off" style="box-shadow: none;">
                <input type="hidden" name="amount" id="amount_raw">
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-4">
                <button type="button" class="btn btn-outline-primary w-100 rounded-3 py-2 fw-bold btn-quick" data-val="50000">50K</button>
            </div>
            <div class="col-4">
                <button type="button" class="btn btn-outline-primary w-100 rounded-3 py-2 fw-bold btn-quick" data-val="100000">100K</button>
            </div>
            <div class="col-4">
                <button type="button" class="btn btn-outline-primary w-100 rounded-3 py-2 fw-bold btn-quick" data-val="250000">250K</button>
            </div>
            <div class="col-6">
                <button type="button" class="btn btn-outline-primary w-100 rounded-3 py-2 fw-bold btn-quick" data-val="500000">500K</button>
            </div>
            <div class="col-6">
                <button type="button" class="btn btn-outline-primary w-100 rounded-3 py-2 fw-bold btn-quick" data-val="1000000">1 Juta</button>
            </div>
        </div>

        <div class="glass-card p-3 mb-4 bg-light border-0 text-center">
            <p class="small text-muted mb-0"><i class="fas fa-info-circle text-info me-1"></i> Transaksi akan diproses melalui Virtual Account Bank Transfer.</p>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg rounded-pill fw-bold shadow-sm" id="btn-submit">
            Proses Top Up
        </button>
    </form>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    const display = $('#amount_display');
    const raw = $('#amount_raw');

    display.on('input', function() {
        let val = $(this).val().replace(/[^0-9]/g, '');
        if(!val) {
            $(this).val('');
            raw.val('');
            return;
        }
        raw.val(val);
        $(this).val(parseInt(val).toLocaleString('id-ID'));
        
        // hapus aktif state dari tombol quick
        $('.btn-quick').removeClass('active');
    });

    $('.btn-quick').click(function() {
        $('.btn-quick').removeClass('active');
        $(this).addClass('active');
        let val = $(this).data('val');
        raw.val(val);
        display.val(parseInt(val).toLocaleString('id-ID'));
    });

    $('#topup-form').submit(function(e) {
        e.preventDefault();
        const amount = raw.val();
        if(!amount || amount < 10000) {
            Swal.fire('Error', 'Minimal top up adalah Rp 10.000', 'warning');
            return;
        }

        const btn = $('#btn-submit');
        const ogText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Memproses...').prop('disabled', true);

        $.ajax({
            url: '<?= base_url('pelanggan-bayar/prosesTopup') ?>',
            type: 'POST',
            data: {
                amount: amount,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({
                        title: 'Top Up Berhasil!',
                        text: 'Saldo Sparkleen Pay Anda telah bertambah.',
                        icon: 'success',
                        confirmButtonText: 'Tutup'
                    }).then(() => {
                        window.location.href = '<?= base_url('pelanggan-bayar/dashboard') ?>';
                    });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                    btn.html(ogText).prop('disabled', false);
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                btn.html(ogText).prop('disabled', false);
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
