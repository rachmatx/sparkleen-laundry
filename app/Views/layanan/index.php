<div class="row">
    <div class="col-12">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Daftar Layanan Laundry</h3>
                <div class="card-tools d-flex gap-2 align-items-center">
                    <form method="get" action="/layanan" class="m-0">
                        <div class="input-group input-group-sm" style="width: 220px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" id="liveSearchInput"
                                   name="q"
                                   class="form-control border-start-0 ps-0"
                                   placeholder="Cari nama layanan..."
                                   data-page="layanan"
                                   data-sort="<?= esc($sort ?? 'nama_layanan') ?>"
                                   data-dir="<?= esc($dir ?? 'ASC') ?>"
                                   autocomplete="off">
                        </div>
                    </form>
                    <small id="liveSearchInfo" class="text-muted text-nowrap"></small>
                    <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                    <a href="/layanan/create" class="btn btn-info btn-sm text-white text-nowrap">
                        <i class="fas fa-plus me-1"></i> Tambah Layanan
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
                                     <?php if(session()->get('level') === 'admin'): ?>
                                     <a href="/layanan/resep/<?= $l['id_layanan'] ?>" class="btn btn-info btn-sm text-white me-1">
                                         <i class="fas fa-flask me-1"></i> Resep
                                     </a>
                                     <?php endif; ?>
                                     <a href="/layanan/edit/<?= $l['id_layanan'] ?>" class="btn btn-warning btn-sm">
                                         <i class="fas fa-edit me-1"></i> Edit
                                     </a>
                                    <?php if ($l['is_aktif'] == 1): ?>
                                        <form action="/layanan/toggle/<?= $l['id_layanan'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menonaktifkan layanan ini?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-power-off me-1"></i> Nonaktif
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="/layanan/toggle/<?= $l['id_layanan'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin mengaktifkan layanan ini?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-power-off me-1"></i> Aktifkan
                                            </button>
                                        </form>
                                    <?php endif; ?>
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