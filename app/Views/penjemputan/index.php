<?php $labels = ['menunggu' => 'Menunggu', 'diproses' => 'Jemput diproses', 'dikonfirmasi' => 'Cucian diterima', 'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan']; ?>
<div class="row"><div class="col-12">
    <?php foreach (['error' => 'danger', 'success' => 'success'] as $key => $color): ?>
        <?php if ($message = session()->getFlashdata($key)): ?><div class="alert alert-<?= $color ?>" role="alert"><?= esc($message) ?></div><?php endif ?>
    <?php endforeach ?>
    <div class="card shadow-sm border-0 mb-3"><div class="card-body d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
        <div><h4 class="mb-1">Jemput laundry pelanggan</h4><p class="text-body-secondary mb-0">Urut jadwal terdekat. Proses jemput, lalu catat berat aktual saat cucian tiba di outlet.</p></div>
        <form method="get" action="<?= site_url('penjemputan') ?>" class="d-flex gap-2 align-items-center">
            <label for="pickup-filter" class="visually-hidden">Status penjemputan</label>
            <select id="pickup-filter" name="status" class="form-select">
                <?php foreach (['aktif' => 'Perlu ditindaklanjuti'] + $labels + ['semua' => 'Semua status'] as $value => $label): ?><option value="<?= esc($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?>
            </select><button class="btn btn-outline-primary" type="submit">Tampilkan</button>
        </form>
    </div></div>
    <?php if (!$rows): ?><div class="card"><div class="card-body text-center py-5"><i class="fas fa-truck fa-2x text-primary mb-3" aria-hidden="true"></i><h5>Antrean kosong</h5><p class="text-body-secondary mb-0">Belum ada permintaan pada status ini.</p></div></div><?php endif ?>
    <?php foreach ($rows as $row): ?>
    <article class="card shadow-sm border-0 mb-3"><div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
            <div><span class="badge <?= $row['status'] === 'menunggu' ? 'text-bg-primary' : 'text-bg-secondary' ?> me-2"><?= esc($labels[$row['status']] ?? $row['status']) ?></span><span class="text-body-secondary">#<?= (int) $row['id_permintaan'] ?></span></div>
            <strong><?= esc(date('d M Y', strtotime($row['tgl_jemput']))) ?> · <?= esc(ucfirst($row['slot_jemput'])) ?> (WIB)</strong>
        </div>
        <div class="row g-4"><div class="col-md-7">
            <h5 class="mb-1"><?= esc($row['nama_pelanggan']) ?></h5><p class="text-body-secondary mb-2"><?= esc($row['no_telp']) ?></p>
            <p class="mb-2"><i class="fas fa-map-marker-alt text-primary me-2" aria-hidden="true"></i><?= nl2br(esc($row['alamat_jemput'])) ?></p>
            <?php if ($row['catatan']): ?><p class="small text-body-secondary mb-2">Catatan: <?= esc($row['catatan']) ?></p><?php endif ?>
            <?php if ($row['alasan_penolakan']): ?><p class="text-danger mb-2">Alasan: <?= esc($row['alasan_penolakan']) ?></p><?php endif ?>
            <p class="mb-0"><?= esc($row['nama_layanan']) ?> · Perkiraan <?= esc(number_format((float) $row['berat_perkiraan'], 2, ',', '.')) ?> kg · Rp <?= number_format((int) $row['estimasi_harga'], 0, ',', '.') ?></p>
            <?php if ($row['id_transaksi']): ?><a class="btn btn-outline-primary mt-3" href="<?= site_url('transaksi/detail/' . (int) $row['id_transaksi']) ?>">Buka transaksi #<?= (int) $row['id_transaksi'] ?></a><?php endif ?>
        </div><div class="col-md-5">
            <?php if ($canEdit && $row['status'] === 'menunggu'): ?>
                <form method="post" action="<?= site_url('penjemputan/' . (int) $row['id_permintaan'] . '/proses') ?>"><?= csrf_field() ?><button class="btn btn-primary w-100" type="submit">Proses penjemputan</button></form>
            <?php endif ?>
            <?php if ($canEdit && $row['status'] === 'diproses'): ?>
                <form method="post" action="<?= site_url('penjemputan/' . (int) $row['id_permintaan'] . '/konfirmasi') ?>" class="border rounded-3 p-3">
                    <?= csrf_field() ?><label class="form-label" for="weight-<?= (int) $row['id_permintaan'] ?>">Berat aktual diterima (kg)</label>
                    <input class="form-control mb-2" id="weight-<?= (int) $row['id_permintaan'] ?>" name="berat_aktual" type="number" min="0.1" max="100" step="0.01" required>
                    <p class="small text-body-secondary">Pastikan cucian sudah diterima. Biaya akhir memakai berat ini dan harga layanan saat ini.</p>
                    <button class="btn btn-primary w-100" type="submit">Terima cucian &amp; buat nota</button>
                </form>
            <?php endif ?>
            <?php if ($canEdit && in_array($row['status'], ['menunggu', 'diproses'], true)): ?>
                <details class="mt-3"><summary class="text-danger">Tolak permintaan</summary>
                    <form method="post" action="<?= site_url('penjemputan/' . (int) $row['id_permintaan'] . '/tolak') ?>" class="mt-2">
                        <?= csrf_field() ?><label class="form-label" for="reason-<?= (int) $row['id_permintaan'] ?>">Alasan untuk pelanggan</label>
                        <textarea id="reason-<?= (int) $row['id_permintaan'] ?>" name="alasan_penolakan" class="form-control mb-2" rows="2" minlength="3" maxlength="255" required></textarea>
                        <button type="submit" class="btn btn-outline-danger">Konfirmasi penolakan</button>
                    </form>
                </details>
            <?php endif ?>
        </div></div>
    </div></article>
    <?php endforeach ?>
    <nav aria-label="Halaman antrean" class="d-flex justify-content-between align-items-center my-4">
        <span class="text-body-secondary">Halaman <?= $page ?> dari <?= $lastPage ?></span><div class="d-flex gap-2">
            <?php if ($page > 1): ?><a class="btn btn-outline-secondary" href="<?= site_url('penjemputan') . '?' . http_build_query(['status' => $status, 'page' => $page - 1]) ?>">Sebelumnya</a><?php endif ?>
            <?php if ($page < $lastPage): ?><a class="btn btn-outline-primary" href="<?= site_url('penjemputan') . '?' . http_build_query(['status' => $status, 'page' => $page + 1]) ?>">Berikutnya</a><?php endif ?>
        </div>
    </nav>
</div></div>
