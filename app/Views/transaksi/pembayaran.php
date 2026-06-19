<?= $this->extend('backend/template/layout') ?>
<?= $this->section('content') ?>

<div class="row">
    <!-- Form Pembayaran -->
    <div class="col-md-5">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Form Pembayaran</h3>
            </div>
            <div class="card-body">
                <?php if(session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <i class="fas fa-check-circle me-1"></i> <?= esc(session()->getFlashdata('success')) ?>
                    </div>
                <?php endif; ?>
                <?php if(session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <i class="fas fa-exclamation-circle me-1"></i> <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <!-- Ringkasan Tagihan -->
                <div class="info-box mb-3 shadow-none border">
                    <span class="info-box-icon bg-light text-secondary">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text text-muted small">No Nota</span>
                        <span class="info-box-number fs-6"><?= $transaksi['no_nota'] ?></span>
                        <span class="progress-description text-muted small"><?= esc($namaPelanggan) ?></span>
                    </div>
                </div>

                <div class="row mb-3 text-center">
                    <div class="col-6">
                        <div class="p-3 border rounded">
                            <div class="text-muted small">Total Tagihan</div>
                            <div class="fw-bold">Rp <?= number_format($transaksi['grand_total'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border rounded <?= $sisa > 0 ? 'border-danger' : 'border-success' ?>">
                            <div class="text-muted small">Sisa Tagihan</div>
                            <div class="fw-bold <?= $sisa > 0 ? 'text-danger' : 'text-success' ?>">
                                Rp <?= number_format($sisa, 0, ',', '.') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if($sisa > 0): ?>
                <hr>
                <form action="<?= base_url('transaksi/simpanPembayaran') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_transaksi" value="<?= $transaksi['id_transaksi'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nominal Bayar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" inputmode="numeric" id="input_nominal_display" class="form-control"
                                   required placeholder="Masukkan nominal" autocomplete="off">
                            <input type="hidden" name="nominal_bayar" id="input_nominal_raw">
                        </div>
                        <div id="info-kembalian" class="form-text mt-2 fw-bold" style="font-size: 1rem;"></div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_metode_pembayaran" id="metode_bayar" required>
                            <?php foreach($metode_list as $m): ?>
                                <option value="<?= $m['id'] ?>" data-nama="<?= strtolower($m['nama_metode']) ?>">
                                    <?= ucfirst($m['nama_metode']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Payment details container for QRIS and Bank Transfer -->
                    <div id="payment-details-container" class="mb-4 d-none">
                        <div id="details-qris" class="p-3 border rounded text-center bg-light d-none">
                            <div class="fw-semibold text-success mb-2">
                                <i class="fas fa-qrcode me-1"></i> Scan QRIS Pembayaran
                            </div>
                            <div class="d-inline-block p-3 bg-white border rounded shadow-sm mb-2">
                                <?php if(isset($qrCodeDataUri) && $qrCodeDataUri): ?>
                                    <img src="<?= $qrCodeDataUri ?>" alt="QR Code Pembayaran">
                                <?php else: ?>
                                    <div class="text-danger small">Gagal memuat QR Code</div>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted">Scan QR di atas menggunakan e-wallet atau aplikasi m-banking Anda</div>
                            <div class="fw-bold mt-1 text-dark">Sparkleen Laundry & Dry Clean</div>
                        </div>
                        <div id="details-transfer" class="p-3 border rounded bg-light d-none">
                            <div class="fw-semibold text-primary mb-2">
                                <i class="fas fa-university me-1"></i> Informasi Rekening Bank
                            </div>
                            <div class="row g-2 text-start small">
                                <div class="col-4 text-muted">Bank</div>
                                <div class="col-8 fw-bold">Bank Mandiri</div>
                                <div class="col-4 text-muted">No. Rekening</div>
                                <div class="col-8 fw-bold text-primary fs-6 d-flex align-items-center">
                                    <span id="bank-acc-num">137-00-998877-6</span>
                                    <button type="button" class="btn btn-xs btn-outline-secondary ms-2 py-0 px-1" onclick="copyBankAccount()" style="font-size: 0.7rem;">
                                        <i class="far fa-copy me-1"></i> Salin
                                    </button>
                                </div>
                                <div class="col-4 text-muted">Atas Nama</div>
                                <div class="col-8 fw-bold">Sparkleen Laundry Group</div>
                            </div>
                            <div class="small text-muted mt-2 border-top pt-2">
                                <i class="fas fa-info-circle me-1 text-info"></i> Harap kirimkan bukti transfer ke kasir setelah transaksi selesai.
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-money-bill-wave me-1"></i> Proses Pembayaran
                    </button>
                </form>
                <?php else: ?>
                <?php endif; ?>
            </div>
            <div class="card-footer" style="background:#fff; box-shadow: 0 -2px 8px rgba(0,0,0,0.08);">
                <a href="<?= base_url('/transaksi/detail/' . ($transaksi['id_transaksi'])) ?>" class="btn btn-secondary w-100">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Detail
                </a>
            </div>
        </div>
    </div>

    <!-- Riwayat Pembayaran -->
    <div class="col-md-7">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Riwayat Pembayaran</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th class="text-end">Nominal</th>
                                <th>Metode</th>
                                <th class="text-end">Sisa Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pembayaran)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fas fa-receipt fa-2x d-block mb-2 opacity-25"></i>
                                    Belum ada riwayat pembayaran.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($pembayaran as $p): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($p['tgl_bayar'])) ?></td>
                                <td class="text-end">Rp <?= number_format($p['nominal_bayar'], 0, ',', '.') ?></td>
                                <td><?= ucfirst($p['metode_bayar']) ?></td>
                                <td class="text-end">Rp <?= number_format($p['sisa_tagihan'], 0, ',', '.') ?></td>
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
document.addEventListener('DOMContentLoaded', function() {
    const selectMetode = document.getElementById('metode_bayar');
    const container = document.getElementById('payment-details-container');
    const detailsQris = document.getElementById('details-qris');
    const detailsTransfer = document.getElementById('details-transfer');

    if (selectMetode && container && detailsQris && detailsTransfer) {
        selectMetode.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (!option) return;
            const val = option.getAttribute('data-nama');
            // Hide all details first
            container.classList.add('d-none');
            detailsQris.classList.add('d-none');
            detailsTransfer.classList.add('d-none');

            if (val === 'qris') {
                container.classList.remove('d-none');
                detailsQris.classList.remove('d-none');
            } else if (val === 'transfer') {
                container.classList.remove('d-none');
                detailsTransfer.classList.remove('d-none');
            }
        });

        // Trigger change initially in case of validation/reload state
        selectMetode.dispatchEvent(new Event('change'));
    }

    // Auto Kembalian Calculator
    const inputDisplay = document.getElementById('input_nominal_display');
    const inputRaw = document.getElementById('input_nominal_raw');
    const infoKembalian = document.getElementById('info-kembalian');
    const sisaTagihan = <?= $sisa ?>;

    if (inputDisplay && inputRaw && infoKembalian) {
        inputDisplay.addEventListener('input', function(e) {
            // Remove non-digits
            let val = this.value.replace(/[^0-9]/g, '');
            if (!val) {
                this.value = '';
                inputRaw.value = '';
                infoKembalian.innerHTML = '';
                return;
            }
            
            // Set raw value for backend
            inputRaw.value = val;
            
            // Format display with dots
            this.value = parseInt(val, 10).toLocaleString('id-ID');
            
            // Calculate kembalian
            let nominalBayar = parseInt(val, 10);
            let selisih = nominalBayar - sisaTagihan;
            
            if (selisih >= 0) {
                infoKembalian.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Kembalian: Rp ' + selisih.toLocaleString('id-ID') + '</span>';
            } else {
                let kurang = Math.abs(selisih);
                infoKembalian.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Kurang: Rp ' + kurang.toLocaleString('id-ID') + '</span>';
            }
        });
    }
});

function copyBankAccount() {
    const accNum = document.getElementById('bank-acc-num').innerText;
    navigator.clipboard.writeText(accNum).then(function() {
        // Floating toast animation
        const toast = document.createElement('div');
        toast.innerHTML = '<i class="fas fa-check-circle me-1"></i> Nomor rekening disalin!';
        toast.style.position = 'fixed';
        toast.style.top = '25px';
        toast.style.right = '25px';
        toast.style.backgroundColor = '#10b981';
        toast.style.color = '#ffffff';
        toast.style.padding = '12px 24px';
        toast.style.borderRadius = '8px';
        toast.style.boxShadow = '0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1)';
        toast.style.zIndex = '9999';
        toast.style.fontSize = '0.9rem';
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        toast.style.transform = 'translateY(-10px)';
        toast.style.opacity = '0';
        document.body.appendChild(toast);
        
        // Trigger reflow & animate in
        setTimeout(() => {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        }, 10);

        // Animate out & remove
        setTimeout(() => {
            toast.style.transform = 'translateY(-10px)';
            toast.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 2000);
    }).catch(function(err) {
        console.error('Gagal menyalin text: ', err);
    });
}
</script>

<?= $this->endSection() ?>