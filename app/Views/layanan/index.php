<div class="row">
    <div class="col-12">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Daftar Layanan Laundry</h3>
                <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                <div class="card-tools">
                    <a href="/layanan/create" class="btn btn-info btn-sm text-white">
                        <i class="fas fa-plus me-1"></i> Tambah Layanan
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if(session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible m-3 mb-0">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <i class="fas fa-check-circle me-1"></i> <?= session()->getFlashdata('success') ?>
                    </div>
                <?php endif; ?>
                <?php if(session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible m-3 mb-0">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <i class="fas fa-exclamation-circle me-1"></i> <?= session()->getFlashdata('error') ?>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50px">No</th>
                                <th>Nama Layanan</th>
                                <th>Harga per KG</th>
                                <th>Estimasi Selesai</th>
                                <th class="text-center">Status</th>
                                <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                                <th width="160px" class="text-center">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($layanan)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-soap fa-2x d-block mb-2 opacity-25"></i>
                                    Belum ada data layanan.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $no = 1; foreach($layanan as $l): ?>
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
                                    <a href="/layanan/edit/<?= $l['id_layanan'] ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                    <?php if ($l['is_aktif'] == 1): ?>
                                        <a href="#"
                                           onclick="if(confirm('Yakin ingin menonaktifkan layanan ini?')) window.location.href='/layanan/toggle/<?= $l['id_layanan'] ?>'"
                                           class="btn btn-danger btn-sm">
                                            <i class="fas fa-power-off me-1"></i> Nonaktif
                                        </a>
                                    <?php else: ?>
                                        <a href="#"
                                           onclick="if(confirm('Yakin ingin mengaktifkan layanan ini?')) window.location.href='/layanan/toggle/<?= $l['id_layanan'] ?>'"
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-power-off me-1"></i> Aktifkan
                                        </a>
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
        </div>
    </div>
</div>