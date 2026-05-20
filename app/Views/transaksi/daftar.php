<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Daftar Transaksi</h3>
                <div class="card-tools d-flex gap-2">
                    <div class="input-group input-group-sm" style="width: 260px;">
                        <input type="text" id="searchInput" class="form-control"
                               placeholder="Cari no nota atau nama pelanggan..." onkeyup="searchTable()">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <?php if(in_array(session()->get('level'), ['admin', 'kasir'])): ?>
                    <a href="/transaksi" class="btn btn-primary btn-sm text-nowrap">
                        <i class="fas fa-plus"></i> Transaksi Baru
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0" id="transaksiTable">
                        <thead class="table-light">
                            <tr>
                                <th>No Nota</th>
                                <th>Pelanggan</th>
                                <th>Tgl Masuk</th>
                                <th class="text-center">Berat</th>
                                <th class="text-end">Grand Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Pembayaran</th>
                                <th width="170px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($transaksi)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    Belum ada data transaksi.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($transaksi as $t): ?>
                            <tr>
                                <td><code><?= $t['no_nota'] ?></code></td>
                                <td><?= esc($t['nama_pelanggan']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($t['tgl_masuk'])) ?></td>
                                <td class="text-center"><?= $t['total_berat'] ?> kg</td>
                                <td class="text-end">Rp <?= number_format($t['grand_total'], 0, ',', '.') ?></td>
                                <td class="text-center">
                                    <?php
                                        $statusClass = match($t['status']) {
                                            'menunggu' => 'text-bg-warning',
                                            'proses'   => 'text-bg-info',
                                            'setrika'  => 'text-bg-secondary',
                                            'selesai'  => 'text-bg-success',
                                            'diambil'  => 'text-bg-primary',
                                            default    => 'text-bg-dark',
                                        };
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= ucfirst($t['status']) ?></span>
                                </td>
                                <td class="text-center">
                                    <?php
                                        $bayarClass = match($t['status_pembayaran']) {
                                            'lunas' => 'text-bg-success',
                                            'cicil' => 'text-bg-warning',
                                            default => 'text-bg-danger',
                                        };
                                    ?>
                                    <span class="badge <?= $bayarClass ?>">
                                        <?= ucfirst($t['status_pembayaran']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/transaksi/detail/<?= $t['id_transaksi'] ?>"
                                           class="btn btn-info text-white" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/transaksi/nota/<?= $t['id_transaksi'] ?>"
                                           target="_blank" class="btn btn-secondary" title="Cetak Nota">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <?php if($t['status'] != 'diambil' && in_array(session()->get('level'), ['admin', 'kasir'])): ?>
                                        <a href="/transaksi/ubah-status/<?= $t['id_transaksi'] ?>"
                                           class="btn btn-warning text-dark" title="Update Status">
                                            <i class="fas fa-exchange-alt"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
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

<script>
    function searchTable() {
        const filter = document.getElementById('searchInput').value.toLowerCase();
        const rows   = document.querySelectorAll('#transaksiTable tbody tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
        });
    }
</script>