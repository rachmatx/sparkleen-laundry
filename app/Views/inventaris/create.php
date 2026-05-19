<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">Tambah Barang Inventaris</h3>
            </div>
            <form action="/inventaris/store" method="post">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_barang"
                               placeholder="Contoh: Sabun Cuci, Pewangi, Plastik" required autofocus>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Stok Awal <span class="text-danger">*</span></label>
                            <input type="text" inputmode="decimal" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')" class="form-control"
                                   name="stok" placeholder="0" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Satuan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="satuan"
                                   placeholder="kg / liter / pcs" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga Beli per Satuan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="harga_beli"
                                   placeholder="0" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Stok Minimal (Batas Peringatan) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control"
                               name="stok_minimal" placeholder="5" required>
                        <div class="form-text">Sistem akan memberi peringatan jika stok mencapai batas ini.</div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center">
                    <a href="/inventaris" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-warning text-dark ms-auto">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
