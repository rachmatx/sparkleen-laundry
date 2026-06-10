<div class="row mb-3">
    <div class="col-md-4">
        <div class="info-box bg-success text-white mb-3 shadow-sm rounded">
            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Sukses Hari Ini</span>
                <span class="info-box-number"><?= number_format($summary['sukses'] ?? 0, 0, ',', '.') ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-danger text-white mb-3 shadow-sm rounded">
            <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Gagal Hari Ini</span>
                <span class="info-box-number"><?= number_format($summary['gagal'] ?? 0, 0, ',', '.') ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-warning text-dark mb-3 shadow-sm rounded">
            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending / Manual Link</span>
                <span class="info-box-number"><?= number_format($summary['pending'] ?? 0, 0, ',', '.') ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="fab fa-whatsapp me-1"></i> Log Pengiriman WhatsApp</h3>
            </div>
            <div class="card-body">
                <form method="get" action="/log-whatsapp" class="row g-2 align-items-end mb-3">
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="sukses" <?= ($filter['status'] ?? '') === 'sukses' ? 'selected' : '' ?>>Sukses</option>
                            <option value="gagal" <?= ($filter['status'] ?? '') === 'gagal' ? 'selected' : '' ?>>Gagal</option>
                            <option value="pending" <?= ($filter['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending / Manual</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Tipe</label>
                        <select name="tipe" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="order_baru" <?= ($filter['tipe'] ?? '') === 'order_baru' ? 'selected' : '' ?>>Order Baru</option>
                            <option value="order_selesai" <?= ($filter['tipe'] ?? '') === 'order_selesai' ? 'selected' : '' ?>>Order Selesai</option>
                            <option value="order_diambil" <?= ($filter['tipe'] ?? '') === 'order_diambil' ? 'selected' : '' ?>>Order Diambil</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Dari Tgl</label>
                        <input type="date" name="dari" class="form-control form-control-sm" value="<?= esc($filter['dari'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Sampai Tgl</label>
                        <input type="date" name="sampai" class="form-control form-control-sm" value="<?= esc($filter['sampai'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Cari (No. Nota / Tujuan)</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik kata kunci..." value="<?= esc($filter['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-success btn-sm w-100" title="Terapkan Filter">
                             <i class="fas fa-search"></i>
                        </button>
                        <a href="/log-whatsapp" class="btn btn-secondary btn-sm w-100" title="Reset Filter"><i class="fas fa-undo"></i></a>
                    </div>
                </form>
            </div>
            <div class="card-body p-0 border-top">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50" class="text-center">No</th>
                                <th>Waktu</th>
                                <th>No. Nota</th>
                                <th>No. Tujuan</th>
                                <th>Tipe & Provider</th>
                                <th>Isi Pesan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-comment-slash fa-2x d-block mb-2"></i>
                                    Belum ada catatan log WhatsApp.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $no = ($page - 1) * $limit + 1; foreach ($logs as $log): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td class="text-nowrap small"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                                <td><?= esc($log['no_nota']) ?></td>
                                <td><?= esc($log['no_tujuan']) ?></td>
                                <td>
                                    <?php
                                    $tipeBadge = 'text-bg-secondary';
                                    if ($log['tipe_notifikasi'] === 'order_baru') $tipeBadge = 'text-bg-info';
                                    elseif ($log['tipe_notifikasi'] === 'order_selesai') $tipeBadge = 'text-bg-success';
                                    elseif ($log['tipe_notifikasi'] === 'order_diambil') $tipeBadge = 'text-bg-primary';
                                    ?>
                                    <span class="badge <?= $tipeBadge ?> mb-1"><?= ucfirst(str_replace('_', ' ', $log['tipe_notifikasi'])) ?></span>
                                    <br><small class="text-muted"><i class="fas fa-server me-1"></i><?= esc($log['provider']) ?></small>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 300px; font-size: 0.85rem;" title="<?= esc($log['isi_pesan']) ?>">
                                        <?= esc($log['isi_pesan']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $stBadge = 'text-bg-secondary';
                                    if ($log['status'] === 'sukses') $stBadge = 'text-bg-success';
                                    elseif ($log['status'] === 'gagal') $stBadge = 'text-bg-danger';
                                    elseif ($log['status'] === 'pending') $stBadge = 'text-bg-warning';
                                    ?>
                                    <span class="badge <?= $stBadge ?>"><?= ucfirst($log['status']) ?></span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <button class="btn btn-sm btn-outline-info" title="Lihat Respon Detail" data-bs-toggle="modal" data-bs-target="#modalRespon<?= $log['id_log'] ?>">
                                        <i class="fas fa-eye"></i> Detail
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal Respon -->
                            <div class="modal fade" id="modalRespon<?= $log['id_log'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fab fa-whatsapp text-success me-2"></i>Detail Log Pengiriman - #<?= $log['id_log'] ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <h6 class="fw-bold mb-2">Pesan yang Dikirim:</h6>
                                            <div class="p-3 bg-body-secondary rounded border mb-4" style="white-space: pre-wrap; font-size: 0.95rem; font-family: monospace;"><?= esc($log['isi_pesan']) ?></div>
                                            
                                            <h6 class="fw-bold mb-2">Respon Gateway (<?= esc($log['provider']) ?>):</h6>
                                            <?php
                                            $json = json_decode($log['respon_api'], true);
                                            if ($json !== null) {
                                                echo '<pre class="bg-dark text-success p-3 rounded" style="font-size: 0.9rem;">' . esc(json_encode($json, JSON_PRETTY_PRINT)) . '</pre>';
                                            } else {
                                                echo '<pre class="bg-dark text-warning p-3 rounded" style="font-size: 0.9rem;">' . esc($log['respon_api'] ?? 'Tidak ada respon / belum diproses.') . '</pre>';
                                            }
                                            ?>
                                        </div>
                                        <div class="modal-footer justify-content-between">
                                            <div>
                                                <span class="badge <?= $stBadge ?> fs-6"><?= ucfirst($log['status']) ?></span>
                                                <span class="ms-2 text-muted small"><i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></span>
                                            </div>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
