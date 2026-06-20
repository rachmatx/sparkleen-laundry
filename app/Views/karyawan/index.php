<div class="row">
    <div class="col-12">
        <div class="card card-outline card-success">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h3 class="card-title m-0">Daftar Karyawan</h3>
                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end w-100">
                    <form method="get" action="<?= base_url('karyawan') ?>" class="m-0 d-flex flex-column flex-md-row gap-2 w-100 justify-content-md-end">
                        <div class="input-group input-group-sm w-100" style="max-width: 250px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" id="liveSearchInput"
                                   name="q"
                                   class="form-control border-start-0 ps-0"
                                   placeholder="Cari nama, username, level..."
                                   data-page="karyawan"
                                   data-sort="<?= esc($sort ?? 'id_karyawan') ?>"
                                   data-dir="<?= esc($dir ?? 'ASC') ?>"
                                   autocomplete="off">
                        </div>
                    </form>
                    <small id="liveSearchInfo" class="text-muted text-nowrap align-self-md-center"></small>
                    <a href="<?= base_url('/karyawan/create') ?>" class="btn btn-success btn-sm text-nowrap w-100 w-md-auto text-center" style="max-width: 250px;">
                        <i class="fas fa-plus"></i> Tambah Karyawan
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if(session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible m-3">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <i class="fas fa-check-circle me-1"></i> <?= esc(session()->getFlashdata('success')) ?>
                    </div>
                <?php endif; ?>
                <?php if(session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible m-3">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <i class="fas fa-exclamation-circle me-1"></i> <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50px">No</th>
                                <th data-sort="nama_karyawan">Nama Karyawan</th>
                                <th data-sort="username">Username</th>
                                <th data-sort="level">Level</th>
                                <th data-sort="no_telp">No Telepon</th>
                                <th data-sort="gaji_pokok">Gaji Pokok</th>
                                <th data-sort="is_aktif" class="text-center">Status</th>
                                <th width="160px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="liveSearchTbody">
                            <?php if(empty($karyawan)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    Belum ada data karyawan.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $no = ($page - 1) * $limit + 1; foreach($karyawan as $k): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= esc($k['nama_karyawan']) ?></td>
                                <td><?= $k['username'] ?></td>
                                <td>
                                    <?php
                                        $levelClass = match($k['level']) {
                                            'pemilik' => 'text-bg-primary',
                                            'admin'   => 'text-bg-success',
                                            default   => 'text-bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $levelClass ?>"><?= ucfirst($k['level']) ?></span>
                                </td>
                                <td><?= $k['no_telp'] ?></td>
                                <td>Rp <?= number_format($k['gaji_pokok'], 0, ',', '.') ?></td>
                                <td class="text-center">
                                    <?php if ($k['is_aktif'] == 1): ?>
                                        <span class="badge text-bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php 
                                    $canManage = false;
                                    if (session()->get('level') === 'pemilik') {
                                        $canManage = true;
                                    } elseif (session()->get('level') === 'admin' && $k['level'] === 'kasir') {
                                        $canManage = true;
                                    }
                                    ?>
                                    <?php if($canManage): ?>
                                    <a href="<?= base_url('/karyawan/edit/' . ($k['id_karyawan'])) ?>" class="btn btn-warning btn-sm text-dark me-1">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if ($k['is_aktif'] == 1): ?>
                                        <form action="<?= base_url('karyawan/toggle/' . ($k['id_karyawan'])) ?>" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menonaktifkan karyawan ini?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-power-off"></i> Nonaktif
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= base_url('karyawan/toggle/' . ($k['id_karyawan'])) ?>" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin mengaktifkan karyawan ini?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-power-off"></i> Aktifkan
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fas fa-lock"></i></span>
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