<!-- Step Indicator -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card mb-0">
            <div class="card-body py-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item active fw-bold"><i class="fas fa-user me-1"></i> Step 1: Pilih Pelanggan</li>
                        <li class="breadcrumb-item text-muted">Step 2: Pilih Layanan</li>
                        <li class="breadcrumb-item text-muted">Step 3: Konfirmasi</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Pilih Pelanggan</h3>
                <div class="card-tools">
                    <a href="/pelanggan/create" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-user-plus"></i> Pelanggan Baru
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if(session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible m-3">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <i class="fas fa-exclamation-circle me-1"></i> <?= session()->getFlashdata('error') ?>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Pelanggan</th>
                                <th>No Telepon</th>
                                <th width="120px" class="text-center">Pilih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pelanggan)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fas fa-users fa-2x d-block mb-2"></i>
                                    Belum ada data pelanggan. Silakan tambah pelanggan baru.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($pelanggan as $p): ?>
                            <tr>
                                <td><code><?= $p['kode_pelanggan'] ?></code></td>
                                <td><?= esc($p['nama_pelanggan']) ?></td>
                                <td><?= $p['no_telp'] ?></td>
                                <td class="text-center">
                                    <a href="/transaksi/pilih-layanan/<?= $p['id_pelanggan'] ?>" class="btn btn-success btn-sm">
                                        <i class="fas fa-arrow-right me-1"></i> Pilih
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