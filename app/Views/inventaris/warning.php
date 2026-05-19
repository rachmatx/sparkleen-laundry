<div class="row">
    <div class="col-12">
        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Peringatan Stok Kritis
                </h3>
                <div class="card-tools">
                    <a href="/inventaris" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if(empty($inventaris)): ?>
                <div class="text-center py-5 text-success">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <h5>Semua Stok Aman</h5>
                    <p class="text-muted">Tidak ada barang yang mencapai batas stok minimal.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-danger">
                            <tr>
                                <th>Nama Barang</th>
                                <th class="text-center">Stok Saat Ini</th>
                                <th class="text-center">Stok Minimal</th>
                                <th class="text-center">Kekurangan</th>
                                <th width="120" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($inventaris as $inv): ?>
                            <tr>
                                <td><strong><?= esc($inv['nama_barang']) ?></strong></td>
                                <td class="text-center text-danger fw-bold">
                                    <?= number_format($inv['stok'], 2) ?> <?= $inv['satuan'] ?>
                                </td>
                                <td class="text-center">
                                    <?= number_format($inv['stok_minimal'], 2) ?> <?= $inv['satuan'] ?>
                                </td>
                                <td class="text-center text-danger">
                                    <?= number_format(max(0, $inv['stok_minimal'] - $inv['stok']), 2) ?> <?= $inv['satuan'] ?>
                                </td>
                                <td class="text-center">
                                    <a href="/inventaris/edit/<?= $inv['id_inventaris'] ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-plus-circle me-1"></i> Update
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
