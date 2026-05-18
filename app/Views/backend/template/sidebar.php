<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="/dashboard" class="brand-link">
        <span class="brand-text font-weight-light">🧺 Laundry App</span>
    </a>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="info">
                <a href="#" class="d-block"><?= session()->get('nama_karyawan') ?></a>
                <small><?= ucfirst(session()->get('level')) ?></small>
            </div>
        </div>
        
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                <li class="nav-item">
                    <a href="/dashboard" class="nav-link <?= ($activeMenu ?? '') == 'dashboard' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/transaksi" class="nav-link <?= ($activeMenu ?? '') == 'transaksi' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-shopping-cart"></i>
                        <p>Transaksi Baru</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/transaksi/daftar" class="nav-link <?= ($activeMenu ?? '') == 'daftar_transaksi' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-list"></i>
                        <p>Daftar Transaksi</p>
                    </a>
                </li>
                <li class="nav-header">MASTER DATA</li>
                <li class="nav-item">
                    <a href="/pelanggan" class="nav-link <?= ($activeMenu ?? '') == 'pelanggan' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Pelanggan</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/layanan" class="nav-link <?= ($activeMenu ?? '') == 'layanan' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-box"></i>
                        <p>Layanan</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/karyawan" class="nav-link <?= ($activeMenu ?? '') == 'karyawan' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-id-card"></i>
                        <p>Karyawan</p>
                    </a>
                </li>
                <?php if(session()->get('level') == 'admin' || session()->get('level') == 'pemilik'): ?>
                <li class="nav-header">LAPORAN</li>
                <li class="nav-item">
                    <a href="/laporan" class="nav-link <?= ($activeMenu ?? '') == 'laporan' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Laporan</p>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</aside>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?= $title ?? 'Dashboard' ?></h1>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">