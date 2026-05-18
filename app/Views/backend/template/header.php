<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Laundry App' ?> - Sistem Laundry</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="/adminlte4/dist/css/adminlte.min.css">
    
    <style>
        .content-wrapper { background: #f4f6f9; }
        .small-box { border-radius: 10px; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>
        
        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-user"></i> <?= session()->get('nama_karyawan') ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="/logout" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>