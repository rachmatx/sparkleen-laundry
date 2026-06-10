<!-- app/Views/backend/template/sidebar.php -->

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

                <!-- ===================================================== -->
                <!-- MAIN NAVIGATION                                       -->
                <!-- ===================================================== -->
                <li class="nav-header pt-3">MAIN NAVIGATION</li>
                <li class="nav-item">
                    <a href="/dashboard" class="nav-link <?= ($activeMenu ?? '') == 'dashboard' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <?php if (in_array(session()->get('level'), ['admin', 'kasir'])): ?>
                <li class="nav-item">
                    <a href="/transaksi" class="nav-link <?= ($activeMenu ?? '') == 'transaksi' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-plus-circle text-primary"></i>
                        <p>Transaksi Baru</p>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="/transaksi/daftar" class="nav-link <?= ($activeMenu ?? '') == 'daftar_transaksi' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-list-alt"></i>
                        <p>Daftar Transaksi</p>
                    </a>
                </li>

                <!-- ===================================================== -->
                <!-- MASTER DATA                                           -->
                <!-- ===================================================== -->
                <li class="nav-header pt-3">MASTER DATA</li>
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
                <?php if (in_array(session()->get('level'), ['admin', 'pemilik', 'kasir'])): ?>
                <li class="nav-item">
                    <a href="/inventaris" class="nav-link <?= ($activeMenu ?? '') == 'inventaris' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-boxes"></i>
                        <p>Inventaris</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                <li class="nav-item">
                    <a href="/karyawan" class="nav-link <?= ($activeMenu ?? '') == 'karyawan' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-id-badge"></i>
                        <p>Karyawan</p>
                    </a>
                </li>
                <?php endif; ?>

                <!-- ===================================================== -->
                <!-- REPORTING & LOGS                                      -->
                <!-- ===================================================== -->
                <li class="nav-header pt-3">REPORTING & LOGS</li>
                <?php if (in_array(session()->get('level'), ['admin', 'pemilik'])): ?>
                <li class="nav-item">
                    <a href="/laporan" class="nav-link <?= in_array($activeMenu ?? '', ['laporan', 'laporan_bulanan', 'laporan_harian']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-chart-line text-success"></i>
                        <p>Laporan Keuangan</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (session()->get('level') === 'pemilik'): ?>
                <li class="nav-item">
                    <a href="/log-aktivitas" class="nav-link <?= ($activeMenu ?? '') == 'log_aktivitas' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-history text-warning"></i>
                        <p>Log Aktivitas</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array(session()->get('level'), ['admin', 'pemilik', 'kasir'])): ?>
                <li class="nav-item">
                    <a href="/log-whatsapp" class="nav-link <?= ($activeMenu ?? '') == 'log_whatsapp' ? 'active' : '' ?>">
                        <i class="nav-icon fab fa-whatsapp text-success"></i>
                        <p>Log WhatsApp</p>
                    </a>
                </li>
                <?php endif; ?>

                <!-- ===================================================== -->
                <!-- SETTINGS / SYSTEM                                     -->
                <!-- ===================================================== -->
                <?php if (session()->get('level') === 'pemilik'): ?>
                <li class="nav-header pt-3">SISTEM</li>
                <li class="nav-item">
                    <a href="/backup" class="nav-link <?= ($activeMenu ?? '') == 'backup' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-database text-info"></i>
                        <p>Backup Database</p>
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