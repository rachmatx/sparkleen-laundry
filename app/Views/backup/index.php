<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-outline card-info">
            <div class="card-header text-center">
                <h3 class="card-title fw-bold text-info w-100 float-none mb-0">
                    <i class="fas fa-database me-2"></i>Backup Database Sistem
                </h3>
            </div>
            <div class="card-body">
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-times-circle me-2"></i> <?= esc(session()->getFlashdata('error')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="text-center mb-4">
                    <i class="fas fa-server fa-4x text-info opacity-75 mb-3"></i>
                    <p class="text-muted">
                        Simpan salinan database Anda secara berkala untuk mengamankan data transaksi, informasi pelanggan, dan resep bahan baku dari risiko kehilangan data.
                    </p>
                </div>

                <div class="alert alert-warning py-3">
                    <h6 class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Peringatan Keamanan:</h6>
                    <p class="mb-0 small">
                        File backup (.sql) berisi data kredensial, logs, dan seluruh rekaman transaksi laundry. 
                        Simpan file hasil backup di lokasi penyimpanan eksternal yang aman dan jangan disebarkan secara sembarangan.
                    </p>
                </div>

                <div class="text-center mt-4">
                    <form action="/backup/download" method="POST" class="d-inline-block text-start" style="min-width: 300px;">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Konfirmasi Password Anda <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan password untuk otorisasi" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-info text-white btn-lg px-4 py-2 shadow-sm w-100">
                                <i class="fas fa-download me-2"></i> Ekspor &amp; Download SQL (.sql)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-footer text-center">
                <small class="text-muted">
                    Database: <strong><?= \Config\Database::connect()->database ?></strong> &bull; Host: <strong><?= \Config\Database::connect()->hostname ?></strong>
                </small>
            </div>
        </div>
    </div>
</div>
