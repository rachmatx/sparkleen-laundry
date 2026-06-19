<div class="row">
    <div class="col-12">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">Data Inventaris</h3>
                <div class="card-tools d-flex gap-2 align-items-center">
                    <form method="get" action="<?= base_url('inventaris') ?>" class="m-0">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" id="liveSearchInput"
                                   name="q"
                                   class="form-control border-start-0 ps-0"
                                   placeholder="Cari nama barang..."
                                   value="<?= esc($keyword ?? '') ?>"
                                   data-page="inventaris"
                                   data-sort="<?= esc($sort ?? 'nama_barang') ?>"
                                   data-dir="<?= esc($dir ?? 'ASC') ?>"
                                   autocomplete="off">
                        </div>
                    </form>
                    <small id="liveSearchInfo" class="text-muted text-nowrap"></small>
                    <?php if($jumlahKritis > 0): ?>
                    <a href="<?= base_url('/inventaris/warning') ?>" class="btn btn-danger btn-sm text-nowrap">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <?= $jumlahKritis ?> Stok Kritis
                    </a>
                    <?php endif; ?>
                    <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                    <a href="<?= base_url('/inventaris/create') ?>" class="btn btn-warning btn-sm text-dark text-nowrap">
                        <i class="fas fa-plus me-1"></i> Tambah
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if(session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible m-3 mb-0">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <i class="fas fa-check-circle me-1"></i> <?= esc(session()->getFlashdata('success')) ?>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50" class="text-center">No</th>
                                <th data-sort="nama_barang" class="text-center">Nama Barang</th>
                                <th data-sort="stok" class="text-center">Stok</th>
                                <th data-sort="satuan" class="text-center">Satuan</th>
                                <th data-sort="harga_beli" class="text-center">Harga Beli</th>
                                <th class="text-center">Stok Minimal</th>
                                <th class="text-center">Status Stok</th>
                                <th data-sort="is_aktif" class="text-center">Status Aktif</th>
                                <th width="160" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="liveSearchTbody">
                            <?php if(empty($inventaris)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-boxes fa-2x d-block mb-2 opacity-25"></i>
                                    Belum ada data inventaris.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $no = ($page - 1) * $limit + 1; foreach($inventaris as $inv): ?>
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
                                    <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                                    <a href="<?= base_url('/inventaris/edit/' . ($inv['id_inventaris'])) ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                    <?php if ($inv['is_aktif'] == 1): ?>
                                        <form action="<?= base_url('inventaris/toggle/' . ($inv['id_inventaris'])) ?>" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menonaktifkan barang ini?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-power-off me-1"></i> Nonaktif
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= base_url('inventaris/toggle/' . ($inv['id_inventaris'])) ?>" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin mengaktifkan barang ini?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-power-off me-1"></i> Aktifkan
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($hasPrev || $hasNext): ?>
            <div class="card-footer live-search-pagination d-flex justify-content-between align-items-center bg-transparent border-top">
                <div>
                    <span class="text-muted small">Halaman <strong><?= $page ?></strong></span>
                </div>
                <div class="btn-group">
                    <?php if ($hasPrev): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-chevron-left me-1"></i> Sebelumnya
                        </a>
                    <?php endif; ?>
                    <?php if ($hasNext): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-outline-secondary btn-sm ms-2">
                            Selanjutnya <i class="fas fa-chevron-right ms-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
