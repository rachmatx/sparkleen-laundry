<div class="row">
    <div class="col-12">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">Data Inventaris</h3>
                <div class="card-tools d-flex gap-2">
                    <?php if($jumlahKritis > 0): ?>
                    <a href="/inventaris/warning" class="btn btn-danger btn-sm">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <?= $jumlahKritis ?> Stok Kritis
                    </a>
                    <?php endif; ?>
                    <a href="/inventaris/create" class="btn btn-warning btn-sm text-dark">
                        <i class="fas fa-plus me-1"></i> Tambah Barang
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if(session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible m-3 mb-0">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <i class="fas fa-check-circle me-1"></i> <?= session()->getFlashdata('success') ?>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50" class="text-center">No</th>
                                <th class="text-center">Nama Barang</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Satuan</th>
                                <th class="text-center">Harga Beli</th>
                                <th class="text-center">Stok Minimal</th>
                                <th class="text-center">Status Stok</th>
                                <th class="text-center">Status Aktif</th>
                                <th width="160" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($inventaris)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-boxes fa-2x d-block mb-2 opacity-25"></i>
                                    Belum ada data inventaris.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $no = 1; foreach($inventaris as $inv): ?>
                            <?php $kritis = $inv['stok'] <= $inv['stok_minimal']; ?>
                            <tr class="<?= $kritis ? 'table-danger' : '' ?>">
                                <td class="text-center"><?= $no++ ?></td>
                                <td class="text-center">
                                    <?= esc($inv['nama_barang']) ?>
                                    <?php if($kritis): ?>
                                        <span class="badge text-bg-danger ms-1">
                                            <i class="fas fa-exclamation-triangle"></i> Kritis
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold <?= $kritis ? 'text-danger' : '' ?>">
                                    <?= number_format($inv['stok'], 2) ?>
                                </td>
                                <td class="text-center"><?= $inv['satuan'] ?></td>
                                <td class="text-center">Rp <?= number_format($inv['harga_beli'], 0, ',', '.') ?></td>
                                <td class="text-center"><?= number_format($inv['stok_minimal'], 2) ?> <?= $inv['satuan'] ?></td>
                                <td class="text-center">
                                    <?php if($kritis): ?>
                                        <span class="badge text-bg-danger">Stok Kritis</span>
                                    <?php elseif($inv['stok'] <= $inv['stok_minimal'] * 1.5): ?>
                                        <span class="badge text-bg-warning text-dark">Hampir Habis</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-success">Aman</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($inv['is_aktif'] == 1): ?>
                                        <span class="badge text-bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="/inventaris/edit/<?= $inv['id_inventaris'] ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                    <?php if ($inv['is_aktif'] == 1): ?>
                                        <a href="#"
                                           onclick="if(confirm('Yakin ingin menonaktifkan barang ini?')) window.location.href='/inventaris/toggle/<?= $inv['id_inventaris'] ?>'"
                                           class="btn btn-danger btn-sm">
                                            <i class="fas fa-power-off me-1"></i> Nonaktif
                                        </a>
                                    <?php else: ?>
                                        <a href="#"
                                           onclick="if(confirm('Yakin ingin mengaktifkan barang ini?')) window.location.href='/inventaris/toggle/<?= $inv['id_inventaris'] ?>'"
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-power-off me-1"></i> Aktifkan
                                        </a>
                                    <?php endif; ?>
                                </td>
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
