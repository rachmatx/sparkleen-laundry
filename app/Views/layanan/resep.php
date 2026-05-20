<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title fw-bold text-info"><i class="fas fa-flask me-2"></i>Formula / Resep Bahan Baku</h3>
            </div>
            <form action="/layanan/resep/store/<?= $layanan['id_layanan'] ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="alert alert-light border py-2 mb-4">
                        <small class="text-muted d-block">Nama Layanan</small>
                        <h5 class="fw-bold mb-1 text-dark"><?= esc($layanan['nama_layanan']) ?></h5>
                        <span class="badge text-bg-secondary">Harga: Rp <?= number_format($layanan['harga_per_kg'], 0, ',', '.') ?> / kg</span>
                    </div>

                    <p class="text-muted mb-4 small">
                        <i class="fas fa-info-circle text-info me-1"></i>
                        Tentukan jumlah pemakaian default barang inventaris (bahan baku) <strong>per 1 KG</strong> cucian untuk layanan ini. 
                        Saat kasir mencatat transaksi, sistem akan mengalikan nilai di bawah ini dengan berat cucian secara otomatis.
                        <em>Kosongkan atau isi 0 jika bahan tidak digunakan untuk layanan ini.</em>
                    </p>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Barang Inventaris</th>
                                    <th width="150px" class="text-center">Stok Saat Ini</th>
                                    <th width="200px" class="text-center">Jumlah Pemakaian Default <br><small>(per 1 KG)</small></th>
                                    <th width="100px" class="text-center">Satuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($inventaris)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Belum ada barang aktif di modul Inventaris.
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($inventaris as $inv): ?>
                                    <tr>
                                        <td>
                                            <strong><?= esc($inv['nama_barang']) ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                                $stok = (float)$inv['stok'];
                                                $badge = 'bg-success';
                                                if($stok <= 0) $badge = 'bg-danger';
                                                elseif($stok < 5) $badge = 'bg-warning text-dark';
                                            ?>
                                            <span class="badge <?= $badge ?>"><?= $stok ?> <?= esc($inv['satuan']) ?></span>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" 
                                                       step="0.0001" 
                                                       min="0" 
                                                       class="form-control text-center" 
                                                       name="jumlah_default[<?= $inv['id_inventaris'] ?>]"
                                                       value="<?= $currentRecipe[$inv['id_inventaris']] ?? '0' ?>"
                                                       placeholder="0.00">
                                            </div>
                                        </td>
                                        <td class="text-center fw-semibold text-muted">
                                            <?= esc($inv['satuan']) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center">
                    <a href="/layanan" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-info text-white ms-auto">
                        <i class="fas fa-save me-1"></i> Simpan Resep
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
