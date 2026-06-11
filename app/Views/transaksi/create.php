<?= $this->extend('backend/template/layout') ?>
<?= $this->section('content') ?>

<!-- POS Single Page Layout -->
<div class="row">
    <!-- ═══ Kolom Kiri: Form Layanan & Pelanggan ═══════════════════════════════════ -->
    <div class="col-md-4">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header">
                <h3 class="card-title">Pilih Layanan</h3>
            </div>
            <!-- Form untuk tambah ke keranjang (hanya menyimpan layanan) -->
            <form action="/transaksi/addToCart" method="post" id="formAddToCart">
                <?= csrf_field() ?>
                <!-- Hidden input untuk menyimpan state pilihan pelanggan sementara -->
                <input type="hidden" name="id_pelanggan_state" id="id_pelanggan_state" value="<?= session()->get('pos_id_pelanggan') ?>">

                <div class="card-body">
                    <?php if(session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger py-2 mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Pilih Pelanggan (State Selector) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pelanggan <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="select_pelanggan" required>
                            <option value="">-- Cari atau Pilih Pelanggan --</option>
                            <?php foreach($pelanggan as $p): ?>
                                <option value="<?= $p['id_pelanggan'] ?>" <?= (session()->get('pos_id_pelanggan') == $p['id_pelanggan']) ? 'selected' : '' ?>>
                                    <?= esc($p['nama_pelanggan']) ?> - <?= $p['no_telp'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">Belum terdaftar? <a href="/pelanggan/create" target="_blank">Tambah Pelanggan Baru</a></small>
                    </div>
                    <hr>
                    <!-- Pilih Layanan -->
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
                        <label class="form-label fw-semibold">Berat (kg) / Jumlah <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" inputmode="decimal" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')" class="form-control"
                                   name="berat" placeholder="Contoh: 2.5" required>
                            <span class="input-group-text">kg / pcs</span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-cart-plus me-1"></i> Tambah ke Keranjang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══ Kolom Kanan: Keranjang & Checkout ═══════════════════════════════════════════ -->
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
                    <i class="fas fa-check me-1"></i> <?= esc(session()->getFlashdata('success')) ?>
                </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Layanan</th>
                                <th class="text-center">Berat / Qty</th>
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
                                <td class="text-center"><?= $t['berat'] ?></td>
                                <td class="text-center">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm btn-hapus-keranjang" data-id="<?= $t['id_temp'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="fas fa-shopping-cart fa-2x d-block mb-2 opacity-25"></i>
                                    Keranjang masih kosong.<br>Silakan tambah layanan di sebelah kiri.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if(!empty($temp)): ?>
                        <tfoot class="table-secondary">
                            <tr>
                                <th>Total</th>
                                <th class="text-center"><?= $totalBerat ?></th>
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
                <!-- Form konfirmasi simpan (Checkout) -->
                <form action="/transaksi/simpanTransaksi" method="post" id="formSimpan">
                    <?= csrf_field() ?>
                    <!-- Actual ID Pelanggan to be submitted -->
                    <input type="hidden" name="id_pelanggan" id="input_id_pelanggan_checkout" value="<?= session()->get('pos_id_pelanggan') ?>">

                    <!-- Loyalitas Poin Pelanggan (Akan diisi dinamis via JS jika perlu, atau sembunyikan jika pelanggan blm dipilih) -->
                    <div id="loyaltySection" class="border rounded p-3 mb-3 text-start bg-light d-none">
                        <h6 class="fw-bold mb-2 text-primary"><i class="fas fa-gift me-1"></i> Penukaran Poin Loyalitas</h6>
                        <p class="text-muted small mb-2">
                            Pelanggan ini memiliki <strong id="poin_tersedia">0 poin</strong>.
                            Setiap <strong>1 poin</strong> bernilai potongan diskon <strong>Rp 300</strong>.
                        </p>
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           name="poin_ditebus" 
                                           id="poin_ditebus" 
                                           min="0" 
                                           max="0" 
                                           placeholder="0" 
                                           oninput="hitungDiskonPoin(this, <?= $totalBiaya ?>)">
                                    <span class="input-group-text">poin</span>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="d-block text-muted small">Potongan Diskon:</span>
                                <strong class="text-success" id="diskon_poin_text">Rp 0</strong>
                            </div>
                        </div>
                        <div class="mt-2 text-end">
                            <span class="d-block text-muted small">Total Tagihan Akhir:</span>
                            <h4 class="fw-bold text-dark mb-0" id="total_akhir_text">Rp <?= number_format($totalBiaya, 0, ',', '.') ?></h4>
                        </div>
                    </div>

                    <!-- Penggunaan Inventaris -->
                    <?php if(!empty($inventaris)): ?>
                    <div class="border rounded p-3 mb-3 text-start bg-light">
                        <h6 class="fw-bold mb-2"><i class="fas fa-boxes me-1"></i> Catat Pemakaian Inventaris <small class="text-muted fw-normal">(Opsional)</small></h6>
                        <p class="text-muted small mb-3">Centang barang yang digunakan dan isi jumlah pemakaiannya.</p>
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
                                            <?php if($inv['stok'] <= $inv['stok_minimal']): ?>
                                                <span class="badge bg-danger ms-1" style="font-size: 0.65em;">Stok Tipis!</span>
                                            <?php endif; ?>
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
                        <input type="datetime-local" class="form-control" id="tgl_selesai_estimasi" name="tgl_selesai_estimasi" required value="<?= date('Y-m-d\TH:i', strtotime('+2 days')) ?>">
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100" id="btnSimpanTransaksi">
                        <i class="fas fa-check-circle me-1"></i> Konfirmasi &amp; Simpan Transaksi
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tambahkan script Select2 jika belum ada di template -->
<!-- Kita asumsikan AdminLTE sudah memuat Select2, jika belum harus ditambahkan -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// Data Pelanggan yang sedang aktif (akan diperbarui via AJAX)
var dataPelanggan = {
    <?php foreach($pelanggan as $p): ?>
    "<?= $p['id_pelanggan'] ?>": {
        poin: <?= $p['poin'] ?? 0 ?>,
        nama: "<?= esc($p['nama_pelanggan']) ?>"
    },
    <?php endforeach; ?>
};

$(document).ready(function() {
    // Inisialisasi Select2 untuk pencarian pelanggan via AJAX
    $('#select_pelanggan').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari atau Pilih Pelanggan --',
        allowClear: true,
        ajax: {
            url: '/pelanggan/ajaxSearch',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // keyword pencarian
                };
            },
            processResults: function (data) {
                return {
                    results: data.results
                };
            },
            cache: true
        },
        minimumInputLength: 0
    });

    // Ketika pelanggan dipilih
    $('#select_pelanggan').on('select2:select', function(e) {
        var data = e.params.data;
        var id = data.id;
        
        // Update cache dataPelanggan
        dataPelanggan[id] = {
            poin: data.poin,
            nama: data.text
        };

        $('#id_pelanggan_state').val(id);
        $('#input_id_pelanggan_checkout').val(id);
        updateLoyaltySection(id);
    });

    $('#select_pelanggan').on('select2:clear', function(e) {
        $('#id_pelanggan_state').val('');
        $('#input_id_pelanggan_checkout').val('');
        updateLoyaltySection('');
    });

    // Jalankan pertama kali jika ada session pos_id_pelanggan
    var currentId = $('#select_pelanggan').val();
    if(currentId) {
        updateLoyaltySection(currentId);
    }

    // Validasi saat Checkout
    $('#formSimpan').on('submit', function(e) {
        var id = $('#input_id_pelanggan_checkout').val();
        if(!id) {
            e.preventDefault();
            alert('Mohon pilih pelanggan terlebih dahulu sebelum Checkout!');
            $('#select_pelanggan').select2('open');
        }
    });

    // Handle hapus keranjang menjaga state pelanggan
    $('.btn-hapus-keranjang').on('click', function() {
        if(confirm('Hapus item ini?')) {
            var idTemp = $(this).data('id');
            var idPelanggan = $('#id_pelanggan_state').val();
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '/transaksi/removeFromCart/' + idTemp;
            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '<?= csrf_token() ?>';
            csrfInput.value = '<?= csrf_hash() ?>';
            form.appendChild(csrfInput);
            var idPelangganInput = document.createElement('input');
            idPelangganInput.type = 'hidden';
            idPelangganInput.name = 'id_pelanggan_state';
            idPelangganInput.value = idPelanggan;
            form.appendChild(idPelangganInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
});

function updateLoyaltySection(id_pelanggan) {
    if(!id_pelanggan || !dataPelanggan[id_pelanggan]) {
        $('#loyaltySection').addClass('d-none');
        return;
    }

    var poin = dataPelanggan[id_pelanggan].poin;
    if(poin > 0) {
        $('#loyaltySection').removeClass('d-none');
        $('#poin_tersedia').text(poin + ' poin');
        $('#poin_ditebus').attr('max', poin);
        
        // Reset perhitungan poin jika pelanggan diganti
        $('#poin_ditebus').val('');
        hitungDiskonPoin(document.getElementById('poin_ditebus'), <?= $totalBiaya ?? 0 ?>);
    } else {
        $('#loyaltySection').addClass('d-none');
    }
}

function hitungDiskonPoin(input, totalBiaya) {
    const poinMax = parseInt(input.getAttribute('max')) || 0;
    let poin = parseInt(input.value) || 0;
    
    if (poin < 0) {
        poin = 0;
        input.value = '';
    }
    if (poin > poinMax) {
        poin = poinMax;
        input.value = poinMax;
    }
    
    const diskon = poin * 300;
    
    // Pastikan diskon tidak melebihi total biaya
    let diskonTerpakai = diskon;
    if (diskonTerpakai > totalBiaya) {
        diskonTerpakai = totalBiaya;
        // Sesuaikan input poin dengan batas max totalBiaya
        const poinBatas = Math.floor(totalBiaya / 300);
        input.value = poinBatas;
        poin = poinBatas;
    }
    
    const totalAkhir = totalBiaya - (poin * 300);
    
    document.getElementById('diskon_poin_text').textContent = 'Rp ' + (poin * 300).toLocaleString('id-ID');
    document.getElementById('total_akhir_text').textContent = 'Rp ' + totalAkhir.toLocaleString('id-ID');
}

// -------------------------------------------------------------
// Script untuk Inventaris (Rekomendasi)
// -------------------------------------------------------------
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

// Data Rekomendasi Resep dari Controller
const resepRekomendasi = <?= json_encode($resepRekomendasi ?? []) ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Jalankan auto pre-fill resep
    for (const [id_inv, jumlah] of Object.entries(resepRekomendasi)) {
        const checkbox = document.getElementById('inv_' + id_inv);
        if (checkbox) {
            checkbox.checked = true;
            toggleInvInput(checkbox);
            
            const detail = checkbox.closest('.inv-item').querySelector('.inv-detail');
            const jumlahInput = detail.querySelector('.inv-jumlah');
            if (jumlahInput) {
                // Tampilkan nilai desimal dengan presisi
                jumlahInput.value = parseFloat(jumlah).toFixed(4);
            }
            const ketInput = detail.querySelector('.inv-ket');
            if (ketInput) {
                ketInput.value = 'Rekomendasi sistem (resep)';
            }
        }
    }
});
</script>

<?= $this->endSection() ?>
