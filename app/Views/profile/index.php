<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Profil Saya</h3>
            </div>
            <form action="/profile/update" method="post">
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
                    
                    <?php if(session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <strong><i class="fas fa-check-circle me-1"></i> Berhasil:</strong> <?= esc(session()->getFlashdata('success')) ?>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_karyawan"
                                   value="<?= esc($user['nama_karyawan']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username"
                                   value="<?= esc($user['username']) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">No Telepon <span class="text-danger">*</span></label>
                            <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" name="no_telp"
                                   value="<?= esc($user['no_telp']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" name="email"
                                   value="<?= esc($user['email'] ?? '') ?>"
                                   placeholder="contoh@email.com">
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3 text-secondary"><i class="fas fa-lock me-2"></i> Keamanan Akun</h5>

                    <div class="row">
                        <div class="col-md-6">
                            <?= view('partials/password_field', [
                                'id'           => 'password_profile',
                                'name'         => 'password',
                                'label'        => 'Password Baru',
                                'placeholder'  => 'Kosongkan jika tidak ingin mengganti',
                                'helpText'     => 'Min. 8 karakter (kombinasi huruf & angka/simbol). Kosongkan jika tidak ingin mengubah.',
                                'autocomplete' => 'new-password',
                            ]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= view('partials/password_field', [
                                'id'           => 'password_profile_confirm',
                                'name'         => 'password_confirm',
                                'label'        => 'Konfirmasi Password Baru',
                                'placeholder'  => 'Ulangi password baru',
                                'autocomplete' => 'new-password',
                            ]) ?>
                        </div>
                    </div>
                    
                    <div class="mt-4 p-3 bg-light rounded border">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle text-primary fa-2x me-3"></i>
                            <div>
                                <strong>Level Akses Anda:</strong> <span class="badge text-bg-secondary ms-1"><?= ucfirst($user['level']) ?></span><br>
                                <small class="text-muted">Gaji Pokok: Rp <?= number_format($user['gaji_pokok'], 0, ',', '.') ?></small>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Simpan Perubahan Profil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
