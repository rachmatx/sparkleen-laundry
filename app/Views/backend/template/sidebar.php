<!-- Main Sidebar Container -->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!-- Brand Logo -->
    <div class="sidebar-brand">
        <a href="/dashboard" class="brand-link">
            <span class="brand-text font-weight-light">Sparkleen</span>
        </a>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar-wrapper">
        <!-- Sidebar user panel -->
        <div class="mt-3 pb-3 mb-3 d-flex px-3 border-bottom border-secondary">
            <div class="info">
                <a href="#" class="d-block text-decoration-none text-light"><?= session()->get('nama_karyawan') ?></a>
                <small class="text-secondary"><?= ucfirst(session()->get('level')) ?></small>
            </div>
        </div>
        
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">

                <!-- Dashboard: semua level -->
                <li class="nav-item">
                    <a href="/dashboard" class="nav-link <?= ($activeMenu ?? '') == 'dashboard' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Transaksi: semua level -->
                <li class="nav-item">
                    <a href="/transaksi" class="nav-link <?= ($activeMenu ?? '') == 'transaksi' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-plus-circle"></i>
                        <p>Transaksi Baru</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/transaksi/daftar" class="nav-link <?= ($activeMenu ?? '') == 'daftar_transaksi' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-list-alt"></i>
                        <p>Daftar Transaksi</p>
                    </a>
                </li>

                <!-- Master Data: semua level bisa lihat pelanggan & layanan -->
                <li class="nav-header">MASTER DATA</li>
                <li class="nav-item">
                    <a href="/pelanggan" class="nav-link <?= ($activeMenu ?? '') == 'pelanggan' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Pelanggan</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/layanan" class="nav-link <?= ($activeMenu ?? '') == 'layanan' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-soap"></i>
                        <p>Layanan</p>
                    </a>
                </li>

                <!-- Karyawan & Laporan: hanya admin & pemilik -->
                <?php if(in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                <li class="nav-item">
                    <a href="/karyawan" class="nav-link <?= ($activeMenu ?? '') == 'karyawan' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-id-badge"></i>
                        <p>Karyawan</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/inventaris" class="nav-link <?= ($activeMenu ?? '') == 'inventaris' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-boxes"></i>
                        <p>Inventaris</p>
                    </a>
                </li>
                <li class="nav-header">LAPORAN</li>
                <li class="nav-item">
                    <a href="/laporan" class="nav-link <?= ($activeMenu ?? '') == 'laporan' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>Laporan</p>
                    </a>
                </li>
                <?php endif; ?>

            </ul>
        </nav>
    </div>
</aside>

<!-- Content Wrapper -->
<main class="app-main">
    <!-- Content Header (Page header) -->
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3 class="mb-0"><?= $title ?? 'Dashboard' ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="app-content">
        <div class="container-fluid">