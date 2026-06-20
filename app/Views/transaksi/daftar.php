<?= $this->extend('backend/template/layout') ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h3 class="card-title m-0">Daftar Transaksi</h3>
                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end w-100">
                    <form method="get" action="<?= base_url('transaksi/daftar') ?>" class="m-0 d-flex flex-column flex-md-row gap-2 w-100 justify-content-md-end">
                        <?php if(!empty($statusFilter)): ?>
                            <input type="hidden" name="status" value="<?= esc($statusFilter) ?>">
                        <?php endif; ?>
                        <div class="input-group input-group-sm w-100" style="max-width: 260px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" id="liveSearchInput"
                                   name="q"
                                   class="form-control border-start-0 ps-0"
                                   placeholder="Cari no nota, nama, HP, atau kode pelanggan..."
                                   value="<?= esc($keyword ?? '') ?>"
                                   data-page="transaksi"
                                   data-status="<?= esc($statusFilter ?? 'semua') ?>"
                                   data-sort="<?= esc($sort ?? 'id_transaksi') ?>"
                                   data-dir="<?= esc($dir ?? 'DESC') ?>"
                                   autocomplete="off">
                        </div>
                    </form>
                    <small id="liveSearchInfo" class="text-muted text-nowrap align-self-md-center"></small>
                    <?php if(in_array(session()->get('level'), ['admin', 'kasir'])): ?>
                    <a href="<?= base_url('/transaksi') ?>" class="btn btn-primary btn-sm text-nowrap w-100 w-md-auto text-center" style="max-width: 260px;">
                        <i class="fas fa-plus"></i> Transaksi Baru
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="nav nav-tabs px-3 pt-3 mb-0 border-bottom-0 gap-1">
                    <li class="nav-item">
                        <a class="nav-link tab-status-filter <?= $statusFilter == 'semua' ? 'active fw-bold bg-light border-bottom-0' : 'text-secondary' ?>"
                           href="<?= base_url('/transaksi/daftar?status=semua') ?>" data-status="semua">Semua</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link tab-status-filter <?= $statusFilter == 'proses' ? 'active fw-bold bg-light border-bottom-0' : 'text-secondary' ?>"
                           href="<?= base_url('/transaksi/daftar?status=proses') ?>" data-status="proses"><i class="fas fa-spinner text-info me-1"></i> Proses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link tab-status-filter <?= $statusFilter == 'selesai' ? 'active fw-bold bg-light border-bottom-0' : 'text-secondary' ?>"
                           href="<?= base_url('/transaksi/daftar?status=selesai') ?>" data-status="selesai"><i class="fas fa-check text-success me-1"></i> Selesai</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link tab-status-filter <?= $statusFilter == 'belum_diambil' ? 'active fw-bold bg-light border-bottom-0' : 'text-secondary' ?>"
                           href="<?= base_url('/transaksi/daftar?status=belum_diambil') ?>" data-status="belum_diambil"><i class="fas fa-shopping-bag text-warning me-1"></i> Belum Diambil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link tab-status-filter <?= $statusFilter == 'belum_lunas' ? 'active fw-bold bg-light border-bottom-0' : 'text-secondary' ?>"
                           href="<?= base_url('/transaksi/daftar?status=belum_lunas') ?>" data-status="belum_lunas"><i class="fas fa-exclamation-triangle text-danger me-1"></i> Belum Lunas</a>
                    </li>
                </ul>
                <div class="table-responsive pt-0">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0" id="transaksiTable">
                        <thead class="table-light">
                            <tr>
                                <th data-sort="no_nota">No Nota</th>
                                <th data-sort="nama_pelanggan">Pelanggan</th>
                                <th data-sort="tgl_masuk">Tgl Masuk</th>
                                <th data-sort="total_berat" class="text-center">Berat</th>
                                <th data-sort="grand_total" class="text-end">Grand Total</th>
                                <th data-sort="status" class="text-center">Status</th>
                                <th data-sort="status_pembayaran" class="text-center">Pembayaran</th>
                                <th width="170px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="liveSearchTbody">
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
                                        <a href="<?= base_url('/transaksi/detail/' . ($t['id_transaksi'])) ?>"
                                           class="btn btn-info text-white btn-quick-detail"
                                           data-id="<?= $t['id_transaksi'] ?>" title="Quick View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= base_url('/transaksi/nota/' . ($t['id_transaksi'])) ?>"
                                           target="_blank" class="btn btn-secondary" title="Cetak Nota">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <?php if($t['status'] != 'diambil' && in_array(session()->get('level'), ['admin', 'kasir'])): ?>
                                        <a href="<?= base_url('/transaksi/ubah-status/' . ($t['id_transaksi'])) ?>"
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

<!-- Modal Quick View Transaksi -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="quickViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="quickViewModalLabel"><i class="fas fa-receipt me-2"></i> Detail Transaksi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="quickViewContent">
                <!-- Content loaded via AJAX -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat detail transaksi...</p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <a href="#" id="btnFullDetail" class="btn btn-outline-primary">Lihat Halaman Penuh</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Tab filter: update data-status pada input live search, lalu trigger pencarian ulang
document.addEventListener('DOMContentLoaded', function () {
    const tabLinks = document.querySelectorAll('.tab-status-filter');
    const liveInput = document.getElementById('liveSearchInput');

    tabLinks.forEach(function (tab) {
        tab.addEventListener('click', function (e) {
            // Jika ada keyword aktif, cegah navigasi dan filter via AJAX
            if (liveInput && liveInput.value.trim() !== '') {
                e.preventDefault();
                const newStatus = this.dataset.status;
                liveInput.dataset.status = newStatus;

                // Update active class
                tabLinks.forEach(t => t.classList.remove('active', 'fw-bold', 'bg-light', 'border-bottom-0'));
                tabLinks.forEach(t => t.classList.add('text-secondary'));
                this.classList.add('active', 'fw-bold', 'bg-light', 'border-bottom-0');
                this.classList.remove('text-secondary');

                // Trigger input event agar live-search.js menjalankan pencarian
                liveInput.dispatchEvent(new Event('input'));
            }
            // Jika tidak ada keyword, biarkan navigasi normal (reload halaman dengan tab baru)
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quickViewModal = new bootstrap.Modal(document.getElementById('quickViewModal'));
    const quickViewContent = document.getElementById('quickViewContent');
    const btnFullDetail = document.getElementById('btnFullDetail');

    // Fungsi bind quick view — dipanggil ulang setelah live search render baris baru
    window.bindQuickView = function () {
        const btnQuickDetails = document.querySelectorAll('.btn-quick-detail');
        btnQuickDetails.forEach(btn => {
            // Hindari double-bind
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';

            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const idTransaksi = this.getAttribute('data-id');
                const fullUrl = this.getAttribute('href');
                
                btnFullDetail.setAttribute('href', fullUrl);
                
                quickViewContent.innerHTML = `
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Memuat detail transaksi...</p>
                    </div>
                `;
                
                quickViewModal.show();
                
                fetch(window.SITE_URL + '/transaksi/ajaxDetail/' + idTransaksi)
                    .then(response => response.json())
                    .then(data => {
                        if(data.status === 'success') {
                            const t = data.transaksi;
                            
                            const formatRp = (num) => 'Rp ' + parseInt(num).toLocaleString('id-ID');
                            
                            let badgeStatus = 'bg-dark';
                            if(t.status === 'menunggu') badgeStatus = 'bg-warning text-dark';
                            if(t.status === 'proses') badgeStatus = 'bg-info text-dark';
                            if(t.status === 'setrika') badgeStatus = 'bg-secondary';
                            if(t.status === 'selesai') badgeStatus = 'bg-success';
                            if(t.status === 'diambil') badgeStatus = 'bg-primary';

                            let badgeBayar = t.status_pembayaran === 'lunas' ? 'bg-success' : (t.status_pembayaran === 'cicil' ? 'bg-warning text-dark' : 'bg-danger');

                            let itemsHtml = '';
                            data.detail.forEach(d => {
                                itemsHtml += `
                                    <tr>
                                        <td>${d.nama_layanan}</td>
                                        <td class="text-center">${d.berat} kg</td>
                                        <td class="text-end">${formatRp(d.harga_satuan)}</td>
                                        <td class="text-end fw-bold">${formatRp(d.subtotal)}</td>
                                    </tr>
                                `;
                            });

                            let html = `
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr><td width="120" class="text-muted">No. Nota</td><td><code class="fs-6">${t.no_nota}</code></td></tr>
                                            <tr><td class="text-muted">Pelanggan</td><td class="fw-bold">${t.nama_pelanggan} <br><small class="text-muted fw-normal">${t.no_telp}</small></td></tr>
                                            <tr><td class="text-muted">Kasir</td><td>${t.nama_karyawan}</td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr><td width="120" class="text-muted">Status Cucian</td><td><span class="badge ${badgeStatus} fs-6">${t.status.toUpperCase()}</span></td></tr>
                                            <tr><td class="text-muted">Pembayaran</td><td><span class="badge ${badgeBayar} fs-6">${t.status_pembayaran.toUpperCase()}</span></td></tr>
                                            <tr><td class="text-muted">Tgl Masuk</td><td>${t.tgl_masuk}</td></tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <h6 class="fw-bold border-bottom pb-2"><i class="fas fa-list me-2"></i>Rincian Layanan</h6>
                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Layanan</th>
                                                <th class="text-center" width="80">Berat</th>
                                                <th class="text-end" width="120">Harga/Kg</th>
                                                <th class="text-end" width="130">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${itemsHtml}
                                        </tbody>
                                        <tfoot class="table-light fw-bold">
                                            <tr>
                                                <td colspan="3" class="text-end">Total Biaya</td>
                                                <td class="text-end">${formatRp(t.total_biaya)}</td>
                                            </tr>
                                            ${t.diskon > 0 ? `
                                            <tr>
                                                <td colspan="3" class="text-end text-success">Diskon (Poin)</td>
                                                <td class="text-end text-success">-${formatRp(t.diskon)}</td>
                                            </tr>
                                            ` : ''}
                                            <tr>
                                                <td colspan="3" class="text-end">Grand Total</td>
                                                <td class="text-end fs-5 text-primary">${formatRp(t.grand_total)}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            `;
                            
                            if(data.pembayaran && data.pembayaran.length > 0) {
                                let payHtml = '';
                                data.pembayaran.forEach(p => {
                                    payHtml += `
                                        <tr>
                                            <td>${p.tgl_bayar}</td>
                                            <td>${p.metode_bayar.toUpperCase()}</td>
                                            <td class="text-end text-success fw-bold">+${formatRp(p.nominal_bayar)}</td>
                                        </tr>
                                    `;
                                });
                                
                                html += `
                                    <h6 class="fw-bold border-bottom pb-2"><i class="fas fa-money-bill-wave me-2"></i>Riwayat Pembayaran</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>Metode</th>
                                                    <th class="text-end">Nominal</th>
                                                </tr>
                                            </thead>
                                            <tbody>${payHtml}</tbody>
                                        </table>
                                    </div>
                                `;
                            }

                            quickViewContent.innerHTML = html;
                        } else {
                            quickViewContent.innerHTML = `
                                <div class="alert alert-danger m-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i> ${data.message}
                                </div>
                            `;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        quickViewContent.innerHTML = `
                            <div class="alert alert-danger m-3">
                                <i class="fas fa-exclamation-triangle me-2"></i> Terjadi kesalahan saat memuat data.
                            </div>
                        `;
                    });
            });
        });
    };

    // Bind pertama kali saat halaman load
    window.bindQuickView();
});
</script>

<?= $this->endSection() ?>
