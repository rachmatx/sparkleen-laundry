<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">Edit Data Karyawan</h3>
            </div>
            <form action="/karyawan/update/<?= $karyawan['id_karyawan'] ?>" method="post">
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

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Nama Karyawan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_karyawan"
                                   value="<?= esc($karyawan['nama_karyawan']) ?>" required autofocus>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username"
                                   value="<?= esc($karyawan['username']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" name="email"
                               value="<?= esc($karyawan['email'] ?? '') ?>"
                               placeholder="contoh@email.com (opsional)">
                        <div class="form-text">Bisa digunakan untuk login sebagai alternatif username.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= view('partials/password_field', [
                                'id'          => 'password_karyawan_edit',
                                'name'        => 'password',
                                'label'       => 'Password Baru',
                                'placeholder' => 'Kosongkan jika tidak ingin mengubah',
                                'helpText'    => 'Biarkan kosong jika tidak ingin mengubah password.',
                            ]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= view('partials/password_field', [
                                'id'           => 'password_karyawan_edit_confirm',
                                'name'         => 'password_confirm',
                                'label'        => 'Konfirmasi Password Baru',
                                'placeholder'  => 'Ulangi password baru',
                                'autocomplete' => 'new-password',
                            ]) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Level Akses <span class="text-danger">*</span></label>
                            <select class="form-select" name="level" required>
                                <option value="kasir"   <?= $karyawan['level'] == 'kasir'   ? 'selected' : '' ?>>Kasir</option>
                                <?php if(session()->get('level') === 'pemilik'): ?>
                                <option value="admin"   <?= $karyawan['level'] == 'admin'   ? 'selected' : '' ?>>Admin</option>
                                <option value="pemilik" <?= $karyawan['level'] == 'pemilik' ? 'selected' : '' ?>>Pemilik</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">No Telepon <span class="text-danger">*</span></label>
                            <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" name="no_telp"
                                   value="<?= esc($karyawan['no_telp']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Gaji Pokok</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" name="gaji_pokok"
                                   value="<?= $karyawan['gaji_pokok'] ?>">
                        </div>
                    </div>

                </div>
                <div class="card-footer d-flex align-items-center">
                    <a href="/karyawan" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-warning text-dark ms-auto">
                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>