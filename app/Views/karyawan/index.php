<div class="row">
    <div class="col-12">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Daftar Karyawan</h3>
                <div class="card-tools">
                    <a href="/karyawan/create" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Tambah Karyawan
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if(session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible m-3">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <i class="fas fa-check-circle me-1"></i> <?= session()->getFlashdata('success') ?>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50px">No</th>
                                <th>Nama Karyawan</th>
                                <th>Username</th>
                                <th>Level</th>
                                <th>No Telepon</th>
                                <th>Gaji Pokok</th>
                                <th width="160px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($karyawan)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    Belum ada data karyawan.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $no = 1; foreach($karyawan as $k): ?>
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
                                    <a href="/karyawan/edit/<?= $k['id_karyawan'] ?>" class="btn btn-warning btn-sm text-dark me-1">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="#"
                                       onclick="if(confirm('Yakin ingin menghapus karyawan ini?')) window.location.href='/karyawan/delete/<?= $k['id_karyawan'] ?>'"
                                       class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
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