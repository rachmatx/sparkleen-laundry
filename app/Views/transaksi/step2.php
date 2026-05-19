<!-- Step Indicator -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card mb-0">
            <div class="card-body py-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="/transaksi">Step 1: Pilih Pelanggan</a></li>
                        <li class="breadcrumb-item active fw-bold">
                            <i class="fas fa-box me-1"></i> Step 2: Pilih Layanan &amp; Inventaris
                        </li>
                        <li class="breadcrumb-item text-muted">Step 3: Konfirmasi</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- ═══ Kolom Kiri: Form + Inventaris ═══════════════════════════════════ -->
    <div class="col-md-4">
        <!-- Info Pelanggan + Form Layanan -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-header">
                <h3 class="card-title">Tambah Layanan</h3>
            </div>
            <form action="/transaksi/addToCart" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="alert alert-light border py-2 mb-3">
                        <small class="text-muted d-block">Pelanggan</small>
                        <strong><?= esc($pelanggan['nama_pelanggan']) ?></strong>
                        <span class="text-muted"> &mdash; <?= $pelanggan['kode_pelanggan'] ?></span>
                    </div>

                    <?php if(session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger py-2 mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <?= session()->getFlashdata('error') ?>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis Layanan <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_layanan" required>
                            <option value="">-- Pilih Layanan --</option>
                            <?php foreach($layanan as $l): ?>
                            <option value="<?= $l['id_layanan'] ?>">
                                <?= esc($l['nama_layanan']) ?> &mdash; Rp <?= number_format($l['harga_per_kg'], 0, ',', '.') ?>/kg
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Berat (kg) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" inputmode="decimal" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')" class="form-control"
                                   name="berat" placeholder="Contoh: 2.5" required>
                            <span class="input-group-text">kg</span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-cart-plus me-1"></i> Tambah ke Keranjang
                    </button>
                </div>
            </form>
        </div>

        <a href="/transaksi" class="btn btn-outline-secondary w-100 mt-2">
            <i class="fas fa-arrow-left me-1"></i> Ganti Pelanggan
        </a>
    </div>

    <!-- ═══ Kolom Kanan: Keranjang ═══════════════════════════════════════════ -->
    <div class="col-md-8">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-shopping-cart me-1"></i> Keranjang Belanja
                </h3>
            </div>
            <div class="card-body p-0">
                <?php if(session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible m-2 py-2">
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                    <i class="fas fa-check me-1"></i> <?= session()->getFlashdata('success') ?>
                </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Layanan</th>
                                <th class="text-center">Berat</th>
                                <th class="text-center">Subtotal</th>
                                <th width="70" class="text-center">Hapus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalBerat = 0; $totalBiaya = 0;
                            if(!empty($temp)):
                                foreach($temp as $t):
                                    $namaLayanan = '-'; $harga = 0;
                                    foreach($layanan as $l):
                                        if($l['id_layanan'] == $t['id_layanan']):
                                            $namaLayanan = $l['nama_layanan'];
                                            $harga = $l['harga_per_kg'];
                                            break;
                                        endif;
                                    endforeach;
                                    $subtotal = $t['berat'] * $harga;
                                    $totalBerat += $t['berat'];
                                    $totalBiaya += $subtotal;
                            ?>
                            <tr>
                                <td><?= esc($namaLayanan) ?></td>
                                <td class="text-center"><?= $t['berat'] ?> kg</td>
                                <td class="text-center">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                <td class="text-center">
                                    <a href="/transaksi/removeFromCart/<?= $t['id_temp'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Hapus item ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="fas fa-shopping-cart fa-2x d-block mb-2 opacity-25"></i>
                                    Keranjang masih kosong.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if(!empty($temp)): ?>
                        <tfoot class="table-secondary">
                            <tr>
                                <th>Total</th>
                                <th class="text-center"><?= $totalBerat ?> kg</th>
                                <th class="text-center">Rp <?= number_format($totalBiaya, 0, ',', '.') ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <?php if(!empty($temp)): ?>
            <div class="card-footer text-end">
                <!-- Form konfirmasi simpan (termasuk data inventaris) -->
                <form action="/transaksi/simpanTransaksi" method="post" id="formSimpan">
                    <?= csrf_field() ?>
                    <!-- Hidden inputs inventaris akan diisi oleh JavaScript -->
                    <div id="hiddenInventarisContainer"></div>
                    <!-- Penggunaan Inventaris -->
                    <?php if(!empty($inventaris)): ?>
                    <div class="border rounded p-3 mb-3 text-start bg-light">
                        <h6 class="fw-bold mb-2"><i class="fas fa-boxes me-1"></i> Catat Pemakaian Inventaris <small class="text-muted fw-normal">(Opsional)</small></h6>
                        <p class="text-muted small mb-3">Centang barang yang digunakan dan isi jumlah pemakaiannya. Data ini akan tersimpan bersamaan dengan transaksi.</p>
                        <div id="inventarisList" class="row">
                            <?php foreach($inventaris as $inv): ?>
                            <div class="col-md-6 mb-2">
                                <div class="border rounded p-2 inv-item bg-white">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input inv-check" type="checkbox"
                                               name="inv_id[]"
                                               form="formSimpan"
                                               id="inv_<?= $inv['id_inventaris'] ?>"
                                               value="<?= $inv['id_inventaris'] ?>"
                                               onchange="toggleInvInput(this)">
                                        <label class="form-check-label fw-semibold" for="inv_<?= $inv['id_inventaris'] ?>">
                                            <?= esc($inv['nama_barang']) ?>
                                            <span class="text-muted small d-block">(Stok: <?= number_format($inv['stok'], 2) ?> <?= $inv['satuan'] ?>)</span>
                                        </label>
                                    </div>
                                    <div class="inv-detail d-none mt-2">
                                        <div class="input-group input-group-sm mb-1">
                                            <input type="text" inputmode="decimal"
                                                   oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')"
                                                   name="inv_jumlah_<?= $inv['id_inventaris'] ?>"
                                                   form="formSimpan"
                                                   class="form-control form-control-sm inv-jumlah"
                                                   placeholder="Jumlah pakai">
                                            <span class="input-group-text"><?= $inv['satuan'] ?></span>
                                        </div>
                                        <input type="text"
                                               name="inv_ket_<?= $inv['id_inventaris'] ?>"
                                               form="formSimpan"
                                               class="form-control form-control-sm inv-ket"
                                               placeholder="Keterangan (opsional)">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Estimasi Selesai -->
                    <div class="border rounded p-3 mb-3 text-start bg-light">
                        <h6 class="fw-bold mb-2"><i class="fas fa-clock me-1"></i> Estimasi Waktu Selesai</h6>
                        <p class="text-muted small mb-2">Tentukan tanggal dan jam perkiraan laundry selesai diproses.</p>
                        <input type="datetime-local" class="form-control" id="tgl_selesai_estimasi" name="tgl_selesai_estimasi" required value="<?= date('Y-m-d\TH:i', strtotime('+2 days')) ?>">
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100">
                        <i class="fas fa-check-circle me-1"></i> Konfirmasi &amp; Simpan Transaksi
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleInvInput(checkbox) {
    const detail = checkbox.closest('.inv-item').querySelector('.inv-detail');
    const jumlah = detail.querySelector('.inv-jumlah');
    const ket    = detail.querySelector('.inv-ket');

    if (checkbox.checked) {
        detail.classList.remove('d-none');
        jumlah.focus();
    } else {
        detail.classList.add('d-none');
        jumlah.value = '';
        ket.value    = '';
    }
}
</script>