<div class="row">
    <div class="col-12">
        <div class="card card-outline card-info">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h3 class="card-title m-0">Daftar Layanan Laundry</h3>
                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end ms-auto">
                    <form method="get" action="<?= base_url('layanan') ?>" class="m-0 d-flex flex-column flex-md-row gap-2 justify-content-md-end">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted" id="liveSearchIcon"></i>
                            </span>
                            <input type="text" id="liveSearchInput"
                                   name="q"
                                   class="form-control border-start-0 ps-0"
                                   placeholder="Cari nama layanan..."
                                   value="<?= esc($keyword ?? '') ?>"
                                   data-page="layanan"
                                   data-sort="<?= esc($sort ?? 'id_layanan') ?>"
                                   data-dir="<?= esc($dir ?? 'DESC') ?>"
                                   autocomplete="off">
                        </div>
                    </form>
                    <small id="liveSearchInfo" class="text-muted text-nowrap align-self-md-center"></small>
                    <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                    <a href="<?= base_url('/layanan/create') ?>" class="btn btn-info btn-sm text-nowrap text-center text-white">
                        <i class="fas fa-plus"></i> Tambah Layanan
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
                <?php if(session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible m-3 mb-0">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <i class="fas fa-exclamation-circle me-1"></i> <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50px">No</th>
                                <th data-sort="nama_layanan">Nama Layanan</th>
                                <th data-sort="harga_per_kg">Harga per KG</th>
                                <th data-sort="estimasi_hari">Estimasi Selesai</th>
                                <th data-sort="is_aktif" class="text-center">Status</th>
                                <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                                <th width="160px" class="text-center">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="liveSearchTbody">
                            <?php if(empty($layanan)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-soap fa-2x d-block mb-2 opacity-25"></i>
                                    Belum ada data layanan.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $no = ($page - 1) * $limit + 1; foreach($layanan as $l): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= esc($l['nama_layanan']) ?></td>
                                <td>Rp <?= number_format($l['harga_per_kg'], 0, ',', '.') ?> / kg</td>
                                <td><span class="badge text-bg-secondary"><?= $l['estimasi_hari'] ?> hari</span></td>
                                <td class="text-center">
                                    <?php if ($l['is_aktif'] == 1): ?>
                                        <span class="badge text-bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1 flex-wrap">
                                        <?php if(session()->get('level') === 'admin'): ?>
                                        <a href="<?= base_url('/layanan/resep/' . ($l['id_layanan'])) ?>" class="btn btn-info btn-sm text-white" title="Resep">
                                            <i class="fas fa-flask"></i> Resep
                                        </a>
                                        <?php endif; ?>
                                        <a href="<?= base_url('/layanan/edit/' . ($l['id_layanan'])) ?>" class="btn btn-warning btn-sm text-dark" title="Edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($l['is_aktif'] == 1): ?>
                                            <form action="<?= base_url('layanan/toggle/' . ($l['id_layanan'])) ?>" method="POST" class="d-inline" data-confirm="Yakin ingin menonaktifkan layanan ini?">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-danger btn-sm" title="Nonaktifkan">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form action="<?= base_url('layanan/toggle/' . ($l['id_layanan'])) ?>" method="POST" class="d-inline" data-confirm="Yakin ingin mengaktifkan layanan ini?">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-success btn-sm" title="Aktifkan">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php endif; ?>
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