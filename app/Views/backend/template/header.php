<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Sparkleen' ?> - Sparkleen Laundry</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- FontSource Source Sans 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" />
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
    
    <!-- OverlayScrollbars -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" />
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="/adminlte4/dist/css/adminlte.min.css">
    
    <!-- jQuery (Required for plugins like Select2) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    
    <!-- Navbar -->
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
            </ul>
            
            <!-- Right navbar links -->
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item d-none d-sm-flex align-items-center me-3">
                    <span class="text-secondary" style="font-weight: 500; font-size: 0.95rem; cursor: default;">
                        <i class="far fa-clock me-1 opacity-75"></i> 
                        <span id="topBarClock">--:--:--</span>
                    </span>
                </li>                
                <li class="nav-item me-3" id="notif-stok-container" style="display: none;">
                    <a class="nav-link position-relative text-danger" href="#" id="btn-stok-notif">
                        <i class="fas fa-bell fs-5"></i>
                        <span id="notif-stok-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger mt-1" style="font-size: 0.65rem;">
                            0
                        </span>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link" data-bs-toggle="dropdown" href="#">
                        <i class="fas fa-user-circle fs-5 me-1"></i> <span class="fw-medium"><?= session()->get('nama_karyawan') ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="/profile" class="dropdown-item">
                            <i class="fas fa-user-cog me-2"></i> Profil Saya
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="/logout" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>