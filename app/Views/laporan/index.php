<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Laporan Transaksi</h3>
            </div>
            <div class="card-body text-center py-4">
                <i class="fas fa-calendar-day fa-3x text-success mb-3"></i>
                <h5>Laporan Harian & Rentang Waktu</h5>
                <p class="text-muted mb-4">Lihat ringkasan transaksi dan pendapatan per hari atau rentang tanggal tertentu.</p>
                <a href="<?= base_url('/laporan/rentang') ?>" class="btn btn-success btn-lg">
                    <i class="fas fa-arrow-right me-1"></i> Lihat Laporan
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Laporan Bulanan</h3>
            </div>
            <div class="card-body text-center py-4">
                <i class="fas fa-calendar-alt fa-3x text-info mb-3"></i>
                <h5>Laporan Bulanan</h5>
                <p class="text-muted mb-4">Lihat ringkasan transaksi dan pendapatan per bulan.</p>
                <a href="<?= base_url('/laporan/bulanan') ?>" class="btn btn-info text-white btn-lg">
                    <i class="fas fa-arrow-right me-1"></i> Lihat Laporan
                </a>
            </div>
        </div>
    </div>
</div>
