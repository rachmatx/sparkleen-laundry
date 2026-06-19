<div class="row">
    <div class="col-12">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history me-1"></i> Log Aktivitas</h3>
            </div>
            <div class="card-body">
                <form method="get" action="<?= base_url('log-aktivitas') ?>" class="row g-2 align-items-end mb-3">
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Modul</label>
                        <select name="modul" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="karyawan" <?= ($filter['modul'] ?? '') === 'karyawan' ? 'selected' : '' ?>>Karyawan</option>
                            <option value="inventaris" <?= ($filter['modul'] ?? '') === 'inventaris' ? 'selected' : '' ?>>Inventaris</option>
                            <option value="Keamanan" <?= ($filter['modul'] ?? '') === 'Keamanan' ? 'selected' : '' ?>>Keamanan</option>
                            <option value="Autentikasi" <?= ($filter['modul'] ?? '') === 'Autentikasi' ? 'selected' : '' ?>>Autentikasi</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Aksi</label>
                        <select name="aksi" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <?php foreach (['tambah', 'ubah', 'hapus', 'aktifkan', 'nonaktifkan', 'Login', 'Gagal Login', 'Blokir Login', 'Backup Database'] as $a): ?>
                            <option value="<?= $a ?>" <?= ($filter['aksi'] ?? '') === $a ? 'selected' : '' ?>><?= label_aksi_log($a) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Dari tanggal</label>
                        <input type="date" name="dari" class="form-control form-control-sm" value="<?= esc($filter['dari'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Sampai tanggal</label>
                        <input type="date" name="sampai" class="form-control form-control-sm" value="<?= esc($filter['sampai'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Cari Karyawan / Detail</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik kata kunci..." value="<?= esc($filter['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-info btn-sm text-white w-100" title="Filter">
                             <i class="fas fa-search"></i>
                        </button>
                        <a href="<?= base_url('/log-aktivitas') ?>" class="btn btn-secondary btn-sm w-100" title="Reset"><i class="fas fa-undo"></i></a>
                    </div>
                </form>

                <p class="text-muted small mb-0">
                    Mencatat aktivitas perubahan data (Karyawan, Inventaris) serta log keamanan sistem (maks. 30 entri per halaman).
                </p>
            </div>
            <div class="card-body p-0 border-top">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50" class="text-center">No</th>
                                <th>Waktu</th>
                                <th>Pelaku</th>
                                <th>Modul</th>
                                <th>Aksi</th>
                                <th>Objek</th>
                                <th>Detail</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    Belum ada log aktivitas.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $no = ($page - 1) * $limit + 1; foreach ($logs as $log): ?>
                            <?php $detail = $log['detail'] ? json_decode($log['detail'], true) : null; ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td class="text-nowrap small"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <strong><?= esc($log['nama_karyawan']) ?></strong>
                                    <br><span class="badge <?= badge_level_karyawan($log['level_karyawan']) ?>"><?= ucfirst($log['level_karyawan']) ?></span>
                                </td>
                                <td><?= label_modul_log($log['modul']) ?></td>
                                <td><span class="badge <?= badge_aksi_log($log['aksi']) ?>"><?= label_aksi_log($log['aksi']) ?></span></td>
                                <td>
                                    <?php if ($log['nama_referensi']): ?>
                                        <?= esc($log['nama_referensi']) ?>
                                        <?php if ($log['id_referensi']): ?>
                                            <br><small class="text-muted">ID: <?= (int) $log['id_referensi'] ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <?php if (is_array($detail) && $detail !== []): ?>
                                        <ul class="mb-0 ps-3">
                                        <?php foreach ($detail as $field => $val): ?>
                                            <?php if (is_array($val) && isset($val['dari'], $val['ke'])): ?>
                                            <li><code><?= esc($field) ?></code>: <?= esc((string) $val['dari']) ?> → <?= esc((string) $val['ke']) ?></li>
                                            <?php else: ?>
                                            <li><code><?= esc($field) ?></code>: <?= esc(is_scalar($val) ? (string) $val : json_encode($val)) ?></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        </ul>
                                    <?php elseif (!empty($log['detail'])): ?>
                                        <?= esc($log['detail']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= esc($log['ip_address'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($hasPrev || $hasNext): ?>
            <div class="card-footer d-flex justify-content-between align-items-center bg-transparent border-top">
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
