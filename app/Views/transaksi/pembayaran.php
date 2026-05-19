<div class="row">
    <!-- Form Pembayaran -->
    <div class="col-md-5">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Form Pembayaran</h3>
            </div>
            <div class="card-body">
                <?php if(session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <i class="fas fa-check-circle me-1"></i> <?= session()->getFlashdata('success') ?>
                    </div>
                <?php endif; ?>
                <?php if(session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <i class="fas fa-exclamation-circle me-1"></i> <?= session()->getFlashdata('error') ?>
                    </div>
                <?php endif; ?>

                <!-- Ringkasan Tagihan -->
                <div class="info-box mb-3 shadow-none border">
                    <span class="info-box-icon bg-light text-secondary">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text text-muted small">No Nota</span>
                        <span class="info-box-number fs-6"><?= $transaksi['no_nota'] ?></span>
                        <span class="progress-description text-muted small"><?= esc($namaPelanggan) ?></span>
                    </div>
                </div>

                <div class="row mb-3 text-center">
                    <div class="col-6">
                        <div class="p-3 border rounded">
                            <div class="text-muted small">Total Tagihan</div>
                            <div class="fw-bold">Rp <?= number_format($transaksi['grand_total'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border rounded <?= $sisa > 0 ? 'border-danger' : 'border-success' ?>">
                            <div class="text-muted small">Sisa Tagihan</div>
                            <div class="fw-bold <?= $sisa > 0 ? 'text-danger' : 'text-success' ?>">
                                Rp <?= number_format($sisa, 0, ',', '.') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if($sisa > 0): ?>
                <hr>
                <form action="/transaksi/simpanPembayaran" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_transaksi" value="<?= $transaksi['id_transaksi'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nominal Bayar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" name="nominal_bayar"
                                   required placeholder="Masukkan nominal">
                        </div>
                        <div class="form-text">Jika bayar lebih, sistem otomatis menghitung kembalian.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                        <select class="form-select" name="metode_bayar" required>
                            <option value="cash">Cash</option>
                            <option value="qris">QRIS</option>
                            <option value="transfer">Transfer Bank</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-money-bill-wave me-1"></i> Proses Pembayaran
                    </button>
                </form>
                <?php else: ?>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="/transaksi/detail/<?= $transaksi['id_transaksi'] ?>" class="btn btn-secondary w-100">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Detail
                </a>
            </div>
        </div>
    </div>

    <!-- Riwayat Pembayaran -->
    <div class="col-md-7">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Riwayat Pembayaran</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th class="text-end">Nominal</th>
                                <th>Metode</th>
                                <th class="text-end">Sisa Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pembayaran)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fas fa-receipt fa-2x d-block mb-2 opacity-25"></i>
                                    Belum ada riwayat pembayaran.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($pembayaran as $p): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($p['tgl_bayar'])) ?></td>
                                <td class="text-end">Rp <?= number_format($p['nominal_bayar'], 0, ',', '.') ?></td>
                                <td><?= ucfirst($p['metode_bayar']) ?></td>
                                <td class="text-end">Rp <?= number_format($p['sisa_tagihan'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>