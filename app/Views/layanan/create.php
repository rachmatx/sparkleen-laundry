<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">Tambah Layanan</h3>
            </div>
            <form action="/layanan/store" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Layanan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_layanan"
                               placeholder="Contoh: Cuci Reguler" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga per KG <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" name="harga_per_kg"
                                   placeholder="8000" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estimasi Selesai (Hari) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" name="estimasi_hari"
                                   placeholder="2" min="1" required>
                            <span class="input-group-text">hari</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center">
                    <a href="/layanan" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-info text-white ms-auto">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>