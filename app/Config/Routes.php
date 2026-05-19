<?php

namespace Config;

$routes = Services::routes();

// Auth
$routes->get('/', 'Auth::login');
$routes->get('/login', 'Auth::login');
$routes->post('/doLogin', 'Auth::doLogin');
$routes->get('/logout', 'Auth::logout');

// Dashboard
$routes->get('/dashboard', 'Dashboard::index');

// CRUD Pelanggan
$routes->get('/pelanggan', 'Pelanggan::index');
$routes->get('/pelanggan/create', 'Pelanggan::create');
$routes->post('/pelanggan/store', 'Pelanggan::store');
$routes->get('/pelanggan/edit/(:num)', 'Pelanggan::edit/$1');
$routes->post('/pelanggan/update/(:num)', 'Pelanggan::update/$1');
$routes->get('/pelanggan/delete/(:num)', 'Pelanggan::delete/$1');

// CRUD Layanan
$routes->get('/layanan', 'Layanan::index');
$routes->get('/layanan/create', 'Layanan::create');
$routes->post('/layanan/store', 'Layanan::store');
$routes->get('/layanan/edit/(:num)', 'Layanan::edit/$1');
$routes->post('/layanan/update/(:num)', 'Layanan::update/$1');
$routes->get('/layanan/delete/(:num)', 'Layanan::delete/$1');

// CRUD Karyawan
$routes->get('/karyawan', 'Karyawan::index');
$routes->get('/karyawan/create', 'Karyawan::create');
$routes->post('/karyawan/store', 'Karyawan::store');
$routes->get('/karyawan/edit/(:num)', 'Karyawan::edit/$1');
$routes->post('/karyawan/update/(:num)', 'Karyawan::update/$1');
$routes->get('/karyawan/delete/(:num)', 'Karyawan::delete/$1');

// CRUD Inventaris
$routes->get('/inventaris', 'Inventaris::index');
$routes->get('/inventaris/create', 'Inventaris::create');
$routes->post('/inventaris/store', 'Inventaris::store');
$routes->get('/inventaris/edit/(:num)', 'Inventaris::edit/$1');
$routes->post('/inventaris/update/(:num)', 'Inventaris::update/$1');
$routes->get('/inventaris/delete/(:num)', 'Inventaris::delete/$1');
$routes->get('/inventaris/warning', 'Inventaris::warning');

// Transaksi
$routes->get('/transaksi', 'Transaksi::index');
$routes->get('/transaksi/pilih-layanan/(:num)', 'Transaksi::pilihLayanan/$1');
$routes->post('/transaksi/addToCart', 'Transaksi::addToCart');
$routes->get('/transaksi/removeFromCart/(:num)', 'Transaksi::removeFromCart/$1');
$routes->post('/transaksi/simpanTransaksi', 'Transaksi::simpanTransaksi');
$routes->get('/transaksi/nota/(:num)', 'Transaksi::nota/$1');

// Daftar & Detail Transaksi
$routes->get('/transaksi/daftar', 'Transaksi::daftar');
$routes->get('/transaksi/detail/(:num)', 'Transaksi::detail/$1');

// Update Status
$routes->get('/transaksi/ubah-status/(:num)', 'Transaksi::ubahStatus/$1');
$routes->post('/transaksi/prosesUbahStatus', 'Transaksi::prosesUbahStatus');

// Laporan
$routes->get('/laporan', 'Laporan::index');
$routes->get('/laporan/harian', 'Laporan::harian');
$routes->get('/laporan/bulanan', 'Laporan::bulanan');

// Pembayaran
$routes->get('/transaksi/pembayaran/(:num)', 'Transaksi::pembayaran/$1');
$routes->post('/transaksi/simpanPembayaran', 'Transaksi::simpanPembayaran');

// Export Laporan
$routes->get('/laporan/export_harian_pdf', 'Laporan::export_harian_pdf');
$routes->get('/laporan/export_bulanan_pdf', 'Laporan::export_bulanan_pdf');
$routes->get('/laporan/export_harian_excel', 'Laporan::export_harian_excel');
$routes->get('/laporan/export_bulanan_excel', 'Laporan::export_bulanan_excel');

// Test & Debug (hapus saat production)
$routes->get('/test', 'Test::index');
$routes->get('/test/fixdb', 'Test::fixDb');
