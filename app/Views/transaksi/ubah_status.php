<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">Update Status Transaksi</h3>
            </div>
            <form action="/transaksi/prosesUbahStatus" method="post">
                <div class="card-body">
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
                        <select class="form-select" name="status" required>
                            <?php
                            $statuses = ['menunggu', 'proses', 'setrika', 'selesai', 'diambil'];
                            $current = $transaksi['status'];
                            $found = false;
                            foreach($statuses as $s):
                                if($s == $current):
                                    $found = true;
                                    continue;
                                endif;
                            ?>
                                <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                            <?php if(!$found): ?>
                                <option value="proses">Proses</option>
                                <option value="setrika">Setrika</option>
                                <option value="selesai">Selesai</option>
                                <option value="diambil">Diambil</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan (Opsional)</label>
                        <textarea class="form-control" name="catatan" rows="3" placeholder="Contoh: Ada kerusakan, perlu perhatian khusus..."></textarea>
                    </div>

                </div>
                <div class="card-footer d-flex align-items-center">
                    <a href="/transaksi/detail/<?= $transaksi['id_transaksi'] ?>" class="btn btn-secondary">
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