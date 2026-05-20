<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Tambah Pelanggan Baru</h3>
            </div>
            <form action="/pelanggan/store" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <?php if(session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <strong><i class="fas fa-exclamation-triangle me-1"></i> Validasi Gagal:</strong>
                        <ul class="mb-0 mt-1">
                            <?php foreach(session()->getFlashdata('errors') as $err): ?>
                            <li><?= esc($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode Pelanggan</label>
                        <input type="text" class="form-control bg-light" name="kode_pelanggan"
                               value="<?= $kode ?>" readonly>
                        <div class="form-text">Kode dibuat otomatis oleh sistem.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_pelanggan"
                               placeholder="Masukkan nama lengkap" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No Telepon <span class="text-danger">*</span></label>
                        <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" name="no_telp"
                               placeholder="Contoh: 0812xxxxxxxx" required>
                        <div class="form-text">Harus diawali dengan 08 atau 62, panjang 9-14 digit.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="alamat" rows="3"
                                  placeholder="Masukkan alamat lengkap" required></textarea>
                    </div>
                </div>
                <!-- UX: Back (kiri), Submit (kanan) -->
                <div class="card-footer d-flex align-items-center">
                    <a href="/pelanggan" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary ms-auto">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>