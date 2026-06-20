<?= $this->extend('pelanggan_app/layout') ?>
<?= $this->section('content') ?>

<div class="top-nav">
    <a href="<?= base_url('pelanggan-bayar/dashboard') ?>" class="back-btn"><i class="fas fa-arrow-left"></i></a>
    <h5>Pengaturan Akun</h5>
</div>

<div class="content-area">
    <div class="glass-card p-4">
        <form id="pengaturanForm">
            <div class="mb-3">
                <label class="form-label small fw-semibold text-muted">Username Baru</label>
                <div class="input-group">
                    <span class="input-group-text border-0 bg-light text-muted"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control border-0 bg-light" id="username" value="<?= esc($username) ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label small fw-semibold text-muted">Password Baru</label>
                <div class="input-group">
                    <span class="input-group-text border-0 bg-light text-muted"><i class="fas fa-lock"></i></span>
                    <input type="text" class="form-control border-0 bg-light" id="password" value="<?= esc($password) ?>" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label small fw-semibold text-muted">PIN Pembayaran (6 Digit)</label>
                <div class="input-group">
                    <span class="input-group-text border-0 bg-light text-muted"><i class="fas fa-key"></i></span>
                    <input type="text" class="form-control border-0 bg-light" id="pin" value="<?= esc($pin) ?>" maxlength="6" pattern="\d{6}" required>
                </div>
                <div class="form-text small">Digunakan saat konfirmasi pembayaran. Default: 123456</div>
            </div>
            
            <button type="submit" class="btn btn-primary-custom w-100 mt-3" id="btnSimpan">
                <i class="fas fa-save me-1"></i> Simpan Pengaturan
            </button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $('#pengaturanForm').on('submit', function(e) {
        e.preventDefault();
        
        const username = $('#username').val();
        const password = $('#password').val();
        const pin = $('#pin').val();
        
        $('#btnSimpan').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: '<?= base_url('pelanggan-bayar/simpanPengaturan') ?>',
            type: 'POST',
            data: {
                username: username,
                password: password,
                pin: pin,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '<?= base_url('pelanggan-bayar/dashboard') ?>';
                    });
                } else {
                    Swal.fire('Info', res.message, 'info');
                    $('#btnSimpan').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Pengaturan');
                }
            },
            error: function() {
                Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                $('#btnSimpan').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Pengaturan');
            }
        });
    });
</script>
<?= $this->endSection() ?>
