<?php

namespace Config;

$routes = Services::routes();

// Auth
$routes->get('/', 'Auth::login');
$routes->get('/login', 'Auth::login');
$routes->post('/doLogin', 'Auth::doLogin');
$routes->get('/logout', 'Auth::logout');

// Lacak Order Publik
$routes->get('/lacak', 'Lacak::index');

// Dashboard
$routes->get('/dashboard', 'Dashboard::index');
$routes->get('/dashboard/api/status-kasir', 'Dashboard::apiStatusKasir', ['filter' => 'auth']);


// CRUD Pelanggan
$routes->group('pelanggan', ['filter' => 'role:admin,kasir,pemilik'], static function ($routes) {
    $routes->get('/', 'Pelanggan::index');
    $routes->get('ajaxSearch', 'Pelanggan::ajaxSearch');
    $routes->get('ajaxSearchTable', 'Pelanggan::ajaxSearchTable');
});
$routes->group('pelanggan', ['filter' => 'role:admin,kasir'], static function ($routes) {
    $routes->get('create', 'Pelanggan::create');
    $routes->post('store', 'Pelanggan::store');
    $routes->get('edit/(:num)', 'Pelanggan::edit/$1');
    $routes->post('update/(:num)', 'Pelanggan::update/$1');
});
$routes->group('pelanggan', ['filter' => 'role:admin,pemilik'], static function ($routes) {
    $routes->post('toggle/(:num)', 'Pelanggan::toggleStatus/$1');
});

// Profil / Ganti Password
$routes->get('/profile', 'Profile::index');
$routes->post('/profile/update', 'Profile::update');

// CRUD Layanan
$routes->group('layanan', ['filter' => 'role:admin,kasir,pemilik'], static function ($routes) {
    $routes->get('/', 'Layanan::index');
    $routes->get('ajaxSearch', 'Layanan::ajaxSearch');
});
$routes->group('layanan', ['filter' => 'role:admin,pemilik'], static function ($routes) {
    $routes->get('create', 'Layanan::create');
    $routes->post('store', 'Layanan::store');
    $routes->get('edit/(:num)', 'Layanan::edit/$1');
    $routes->post('update/(:num)', 'Layanan::update/$1');
    $routes->post('toggle/(:num)', 'Layanan::toggleStatus/$1');
});
$routes->group('layanan', ['filter' => 'role:admin'], static function ($routes) {
    $routes->get('resep/(:num)', 'Layanan::resep/$1');
    $routes->post('resep/store/(:num)', 'Layanan::resepStore/$1');
});

// CRUD Karyawan
$routes->group('karyawan', ['filter' => 'role:admin,pemilik'], static function ($routes) {
    $routes->get('/', 'Karyawan::index');
    $routes->get('ajaxSearch', 'Karyawan::ajaxSearch');
    $routes->get('create', 'Karyawan::create');
    $routes->post('store', 'Karyawan::store');
    $routes->get('edit/(:num)', 'Karyawan::edit/$1');
    $routes->post('update/(:num)', 'Karyawan::update/$1');
    $routes->post('toggle/(:num)', 'Karyawan::toggleStatus/$1');
});

// CRUD Inventaris
$routes->group('inventaris', ['filter' => 'role:admin,kasir,pemilik'], static function ($routes) {
    $routes->get('/', 'Inventaris::index');
    $routes->get('ajaxSearch', 'Inventaris::ajaxSearch');
    $routes->get('warning', 'Inventaris::warning');
    $routes->get('ajaxCekStokKritis', 'Inventaris::ajaxCekStokKritis');
});
$routes->group('inventaris', ['filter' => 'role:admin,pemilik'], static function ($routes) {
    $routes->get('create', 'Inventaris::create');
    $routes->post('store', 'Inventaris::store');
    $routes->get('edit/(:num)', 'Inventaris::edit/$1');
    $routes->post('update/(:num)', 'Inventaris::update/$1');
    $routes->post('toggle/(:num)', 'Inventaris::toggleStatus/$1');
});

// Log aktivitas (hanya pemilik)
$routes->group('log-aktivitas', ['filter' => 'role:pemilik'], static function ($routes) {
    $routes->get('/', 'LogAktivitas::index');
});

// Log WhatsApp (Kasir, Admin, Pemilik)
$routes->group('log-whatsapp', ['filter' => 'role:admin,kasir,pemilik'], static function ($routes) {
    $routes->get('/', 'LogWhatsapp::index');
});

// Backup database (pemilik)
$routes->group('backup', ['filter' => 'role:pemilik'], static function ($routes) {
    $routes->get('/', 'Backup::index');
    $routes->post('download', 'Backup::download');
});

// Transaksi POS & Update (Hanya Admin dan Kasir)
$routes->group('transaksi', ['filter' => 'role:admin,kasir'], static function ($routes) {
    $routes->get('/', 'Transaksi::index');
    $routes->post('addToCart', 'Transaksi::addToCart');
    $routes->post('removeFromCart/(:num)', 'Transaksi::removeFromCart/$1');
    $routes->post('simpanTransaksi', 'Transaksi::simpanTransaksi');
    $routes->get('ubah-status/(:num)', 'Transaksi::ubahStatus/$1');
    $routes->post('prosesUbahStatus', 'Transaksi::prosesUbahStatus');
    $routes->get('pembayaran/(:num)', 'Transaksi::pembayaran/$1');
    $routes->post('simpanPembayaran', 'Transaksi::simpanPembayaran');
});

// Transaksi Umum (Daftar, Detail, Nota - Terbuka sesuai akses sebelumnya)
$routes->group('transaksi', ['filter' => 'role:admin,kasir,pemilik'], static function ($routes) {
    $routes->get('nota/(:num)', 'Transaksi::nota/$1');
    $routes->get('daftar', 'Transaksi::daftar');
    $routes->get('ajaxSearch', 'Transaksi::ajaxSearch');
    $routes->get('detail/(:num)', 'Transaksi::detail/$1');
    $routes->get('ajaxDetail/(:num)', 'Transaksi::ajaxDetail/$1');
    $routes->post('kirim-wa-api/(:num)/(:any)', 'Transaksi::kirimWaApi/$1/$2');
});

// Laporan
$routes->group('laporan', ['filter' => 'role:admin,pemilik'], static function ($routes) {
    $routes->get('/', 'Laporan::index');
    $routes->get('harian', 'Laporan::harian');
    $routes->get('bulanan', 'Laporan::bulanan');
    $routes->get('export_harian_pdf', 'Laporan::export_harian_pdf');
    $routes->get('export_bulanan_pdf', 'Laporan::export_bulanan_pdf');
    $routes->get('export_harian_excel', 'Laporan::export_harian_excel');
    $routes->get('export_bulanan_excel', 'Laporan::export_bulanan_excel');
});
