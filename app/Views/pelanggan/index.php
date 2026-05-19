<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Daftar Pelanggan</h3>
                <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                <div class="card-tools">
                    <a href="/pelanggan/create" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Tambah Pelanggan
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
                                <th>Kode</th>
                                <th>Nama Pelanggan</th>
                                <th>No Telepon</th>
                                <th>Alamat</th>
                                <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                                <th width="160px" class="text-center">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pelanggan)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-users fa-2x d-block mb-2 opacity-25"></i>
                                    Belum ada data pelanggan.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $no = 1; foreach($pelanggan as $p): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><code><?= $p['kode_pelanggan'] ?></code></td>
                                <td><?= esc($p['nama_pelanggan']) ?></td>
                                <td><?= $p['no_telp'] ?></td>
                                <td><?= esc($p['alamat']) ?></td>
                                <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                                <td class="text-center">
                                    <a href="/pelanggan/edit/<?= $p['id_pelanggan'] ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                    <a href="#"
                                       onclick="if(confirm('Yakin ingin menghapus pelanggan ini?')) window.location.href='/pelanggan/delete/<?= $p['id_pelanggan'] ?>'"
                                       class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash me-1"></i> Hapus
                                    </a>
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