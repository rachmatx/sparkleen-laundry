<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>Rp <?= number_format($pendapatanHariIni, 0, ',', '.') ?></h3>
                <p>Pendapatan Hari Ini</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= $transaksiHariIni ?></h3>
                <p>Transaksi Hari Ini</p>
            </div>
            <div class="icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= $transaksiProses ?></h3>
                <p>Transaksi Dalam Proses</p>
            </div>
            <div class="icon">
                <i class="fas fa-spinner"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3><?= $jumlahPelanggan ?></h3>
                <p>Total Pelanggan</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Selamat Datang di Sistem Laundry</h3>
            </div>
            <div class="card-body">
                <p>Halo, <strong><?= session()->get('nama_karyawan') ?></strong>! Selamat datang.</p>
                <hr>
                <a href="/transaksi" class="btn btn-primary">➕ Transaksi Baru</a>
                <a href="/transaksi/daftar" class="btn btn-info">📋 Daftar Transaksi</a>
                <a href="/laporan" class="btn btn-success">📊 Laporan</a>
            </div>
        </div>
    </div>
</div>