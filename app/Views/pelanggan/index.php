<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h3 class="card-title m-0">Daftar Pelanggan</h3>
                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end w-100">
                    <form method="get" action="<?= base_url('pelanggan') ?>" class="m-0 d-flex flex-column flex-md-row gap-2 w-100 justify-content-md-end">
                        <select name="status" class="form-select form-select-sm w-100" style="max-width: 150px;" onchange="document.getElementById('liveSearchInput').dataset.status = this.value; document.getElementById('liveSearchInput').dispatchEvent(new Event('input'));">
                            <option value="">Semua Status</option>
                            <option value="1" <?= (isset($status) && $status === '1') ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= (isset($status) && $status === '0') ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                        <div class="input-group input-group-sm w-100" style="max-width: 250px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted" id="liveSearchIcon"></i>
                            </span>
                            <input type="text" id="liveSearchInput"
                                   name="q"
                                   class="form-control border-start-0 ps-0"
                                   placeholder="Cari nama, HP, kode..."
                                   value="<?= esc($keyword ?? '') ?>"
                                   data-page="pelanggan"
                                   data-status="<?= esc($status ?? '') ?>"
                                   data-sort="<?= esc($sort ?? 'id_pelanggan') ?>"
                                   data-dir="<?= esc($dir ?? 'DESC') ?>"
                                   autocomplete="off">
                        </div>
                    </form>
                    <small id="liveSearchInfo" class="text-muted text-nowrap align-self-md-center"></small>
                    <?php if(in_array(session()->get('level'), ['admin', 'kasir'])): ?>
                        <a href="<?= base_url('/pelanggan/create') ?>" class="btn btn-primary btn-sm text-nowrap w-100 w-md-auto text-center" style="max-width: 250px;">
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
                                <th data-sort="kode_pelanggan">Kode</th>
                                <th data-sort="nama_pelanggan">Nama Pelanggan</th>
                                <th data-sort="no_telp">No Telepon</th>
                                <th data-sort="alamat">Alamat</th>
                                <th data-sort="is_aktif" class="text-center">Status</th>
                                <th width="160px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="liveSearchTbody">
                            <?php if(empty($pelanggan)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-users fa-2x d-block mb-2 opacity-25"></i>
                                    Belum ada data pelanggan.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $no = ($page - 1) * $limit + 1; foreach($pelanggan as $p): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><code><?= $p['kode_pelanggan'] ?></code></td>
                                <td><?= esc($p['nama_pelanggan']) ?></td>
                                <td><?= $p['no_telp'] ?></td>
                                <td><?= esc($p['alamat']) ?></td>
                                <td class="text-center">
                                    <?php if ($p['is_aktif'] == 1): ?>
                                        <span class="badge text-bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if(in_array(session()->get('level'), ['admin', 'kasir'])): ?>
                                    <a href="<?= base_url('/pelanggan/edit/' . ($p['id_pelanggan'])) ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                    <?php endif; ?>
                                    <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                                        <?php if ($p['is_aktif'] == 1): ?>
                                            <form action="<?= base_url('pelanggan/toggle/' . ($p['id_pelanggan'])) ?>" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menonaktifkan pelanggan ini?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-power-off me-1"></i> Nonaktif
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form action="<?= base_url('pelanggan/toggle/' . ($p['id_pelanggan'])) ?>" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin mengaktifkan pelanggan ini?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-power-off me-1"></i> Aktifkan
                                                </button>
                                            </form>
                                        <?php endif; ?>
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