<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title">Tambah Karyawan Baru</h3>
            </div>
            <form action="<?= base_url('karyawan/store') ?>" method="post">
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
                                   value="<?= old('nama_karyawan') ?>"
                                   placeholder="Masukkan nama lengkap" required autofocus>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username"
                                   value="<?= old('username') ?>"
                                   placeholder="Untuk login" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" name="email"
                               value="<?= old('email') ?>"
                               placeholder="contoh@email.com (opsional)">
                        <div class="form-text">Bisa digunakan untuk login sebagai alternatif username.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= view('partials/password_field', [
                                'id'          => 'password_karyawan_create',
                                'name'        => 'password',
                                'label'       => 'Password',
                                'placeholder' => 'Min. 8 karakter (kombinasi huruf & angka/simbol)',
                                'required'    => true,
                            ]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= view('partials/password_field', [
                                'id'           => 'password_karyawan_create_confirm',
                                'name'         => 'password_confirm',
                                'label'        => 'Konfirmasi Password',
                                'placeholder'  => 'Ulangi password',
                                'required'     => true,
                                'autocomplete' => 'new-password',
                            ]) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Level Akses <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_level" required>
                                <option value="">-- Pilih Level --</option>
                                <?php foreach($level_list as $lvl): ?>
                                    <?php 
                                        // Pemilik bisa lihat semua, Admin hanya bisa lihat kasir (id=2)
                                        if (session()->get('level') === 'admin' && $lvl['nama_level'] !== 'kasir') {
                                            continue;
                                        }
                                    ?>
                                    <option value="<?= $lvl['id'] ?>" <?= old('id_level') == $lvl['id'] ? 'selected' : '' ?>>
                                        <?= ucfirst($lvl['nama_level']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">No Telepon <span class="text-danger">*</span></label>
                            <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" name="no_telp"
                                   value="<?= old('no_telp') ?>"
                                   placeholder="Contoh: 0812xxxxxxxx" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Gaji Pokok</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" name="gaji_pokok"
                                   value="<?= old('gaji_pokok', 0) ?>">
                        </div>
                    </div>

                </div>
                <div class="card-footer d-flex align-items-center">
                    <a href="<?= base_url('/karyawan') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-success ms-auto">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>