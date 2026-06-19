<?= $this->extend('backend/template/layout') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">Update Status Transaksi</h3>
            </div>
            <form action="<?= base_url('transaksi/prosesUbahStatus') ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger">
                            <?= esc(session()->getFlashdata('error')) ?>
                        </div>
                    <?php endif; ?>
                    
                    <input type="hidden" name="id_transaksi" value="<?= $transaksi['id_transaksi'] ?>">

                    <div class="alert alert-light border py-2 mb-4">
                        <small class="text-muted d-block">No Nota</small>
                        <strong><?= $transaksi['no_nota'] ?></strong>
                        <hr class="my-2">
                        <small class="text-muted d-block">Status Saat Ini</small>
                        <span class="badge text-bg-secondary fs-6"><?= ucfirst($transaksi['status']) ?></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status Baru <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_status_transaksi" id="status_baru" required>
                            <?php foreach($status_list as $s): ?>
                                <?php if($s['id'] == $transaksi['id_status_transaksi']) continue; ?>
                                <option value="<?= $s['id'] ?>" data-nama="<?= $s['nama_status'] ?>">
                                    <?= ucfirst($s['nama_status']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan (Opsional)</label>
                        <textarea class="form-control" name="catatan" rows="3" placeholder="Contoh: Ada kerusakan, perlu perhatian khusus..."></textarea>
                    </div>

                    <?php 
                        $has_pin_db = !empty($transaksi['pin_pengambilan']);
                        $show_pin = $has_pin_db && $is_pin_required;
                    ?>
                    <div class="mb-3" id="wrap-pin" style="display:none;" data-show-pin="<?= $show_pin ? '1' : '0' ?>">
                        <label class="form-label fw-semibold text-danger">PIN Pengambilan (Dari WA Pelanggan) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pin_pengambilan" id="pin_pengambilan" placeholder="Masukkan 4 digit PIN" maxlength="4" pattern="\d{4}">
                        <small class="text-muted">Wajib diisi untuk keamanan saat menyerahkan cucian. (Bisa diabaikan oleh Pemilik jika darurat).</small>
                    </div>

                     <div class="mb-0" id="wrap-kirim-wa" style="display:none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="kirim_wa" value="1" id="kirim_wa" checked>
                            <label class="form-check-label" for="kirim_wa" id="label_kirim_wa">
                                <i class="fab fa-whatsapp text-success"></i>
                                Kirim notifikasi WhatsApp ke pelanggan (order selesai)
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1 ms-4">
                            Otomatis jika API dikonfigurasi; jika belum, tampil tombol kirim manual.
                        </small>
                    </div>

                </div>
                <div class="card-footer d-flex align-items-center">
                    <a href="<?= base_url('/transaksi/detail/' . ($transaksi['id_transaksi'])) ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-warning text-dark ms-auto">
                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const select = document.getElementById('status_baru');
    const wrapWa   = document.getElementById('wrap-kirim-wa');
    const labelWa  = document.getElementById('label_kirim_wa');
    const wrapPin  = document.getElementById('wrap-pin');
    const inputPin = document.getElementById('pin_pengambilan');
    
    if (!select) return;

    function toggleFields() {
        const option = select.options[select.selectedIndex];
        if (!option) return;
        const val = option.getAttribute('data-nama');
        
        if (wrapWa) {
            if (val === 'selesai' || val === 'diambil') {
                wrapWa.style.display = 'block';
                if (labelWa) {
                    labelWa.innerHTML = '<i class="fab fa-whatsapp text-success me-1"></i> Kirim notifikasi WhatsApp ke pelanggan (order ' + val + ')';
                }
            } else {
                wrapWa.style.display = 'none';
            }
        }

        if (wrapPin) {
            const showPin = wrapPin.getAttribute('data-show-pin') === '1';
            if (val === 'diambil' && showPin) {
                wrapPin.style.display = 'block';
                inputPin.setAttribute('required', 'required');
            } else {
                wrapPin.style.display = 'none';
                inputPin.removeAttribute('required');
            }
        }
    }

    select.addEventListener('change', toggleFields);
    toggleFields();
})();
</script>

<?= $this->endSection() ?>
