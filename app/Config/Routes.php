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

// Diagnostic and bootstrap routes are available only for local development.
if (ENVIRONMENT === 'development') {
    $routes->get('/debug-db', function() {
        $db = \Config\Database::connect();
        $karyawan = $db->table('tb_karyawan')->get()->getResultArray();
        return "<pre>" . print_r($karyawan, true) . "</pre>";
    });

    $routes->get('/seed', function() {
        $seeder = \Config\Database::seeder();
        try {
            $seeder->call('MasterSeeder');
            $seeder->call('AdminSeeder');
            return "Database berhasil di-seed! Silakan coba login.";
        } catch (\Exception $e) {
            return "Gagal seed database: " . $e->getMessage();
        }
    });

    $routes->get('/debug', function() {
        return "Base URL is: " . base_url() . "<br>HTTP_HOST is: " . ($_SERVER['HTTP_HOST'] ?? 'null') . "<br>X-Forwarded-Host is: " . ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? 'null');
    });
}

// Profil / Ganti Password
$routes->get('/profile', 'Profile::index');
$routes->post('/profile/update', 'Profile::update');

// Rute khusus untuk prototype Pelanggan App (Mobile E-Wallet)
$routes->group('pelanggan-bayar', function ($routes) {
    $routes->get('/', 'PelangganBayar::index');
    $routes->post('doLogin', 'PelangganBayar::doLogin');
    $routes->get('logout', 'PelangganBayar::logout');
    $routes->get('dashboard', 'PelangganBayar::dashboard');
    $routes->get('scan', 'PelangganBayar::scan');
    $routes->post('cekQr', 'PelangganBayar::cekQr');
    $routes->get('transfer', 'PelangganBayar::transfer');
    $routes->post('cekTransfer', 'PelangganBayar::cekTransfer');
    $routes->get('konfirmasi', 'PelangganBayar::konfirmasi');
    $routes->post('prosesBayar', 'PelangganBayar::prosesBayar');
    $routes->get('topup', 'PelangganBayar::topup');
    $routes->post('prosesTopup', 'PelangganBayar::prosesTopup');
    $routes->get('riwayat', 'PelangganBayar::riwayat');
    $routes->get('pengaturan', 'PelangganBayar::pengaturan');
    $routes->post('simpanPengaturan', 'PelangganBayar::simpanPengaturan');
});

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
    $routes->get('ajaxCekPembayaran/(:num)', 'Transaksi::ajaxCekPembayaran/$1');
    $routes->post('batalAPI/(:num)', 'Transaksi::batalAPI/$1');
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
    $routes->get('bulanan', 'Laporan::bulanan');
    $routes->get('export_bulanan_pdf', 'Laporan::export_bulanan_pdf');
    $routes->get('export_bulanan_excel', 'Laporan::export_bulanan_excel');
    $routes->get('export_harian_pdf', 'Laporan::export_harian_pdf');
    $routes->get('export_harian_excel', 'Laporan::export_harian_excel');
    $routes->get('rentang', 'Laporan::rentang');
    $routes->get('export_rentang_pdf', 'Laporan::export_rentang_pdf');
    $routes->get('export_rentang_excel', 'Laporan::export_rentang_excel');
});

// Webhook Payment
$routes->post('webhook/payment', 'Webhook::payment');

// Customer API: independent of cashier sessions; all private routes require Bearer auth.
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1', 'filter' => 'cors'], static function ($routes) {
    $routes->post('auth/register', 'AuthController::register');
    $routes->post('auth/activate', 'AuthController::activate');
    $routes->post('auth/login', 'AuthController::login');
    $routes->options('(:any)', static fn () => service('response')->setStatusCode(204));
    $routes->group('', ['filter' => 'apiauth'], static function ($routes) {
        $routes->post('auth/logout', 'AuthController::logout');
        $routes->get('me', 'CustomerController::me');
        $routes->patch('me', 'CustomerController::update');
        $routes->get('layanan', 'CustomerController::layanan');
        $routes->get('outlet', 'CustomerController::outlet');
        $routes->get('transaksi', 'TransaksiController::index');
        $routes->get('transaksi/(:num)', 'TransaksiController::show/$1');
        $routes->get('transaksi/(:num)/nota', 'TransaksiController::nota/$1');
    });
    $routes->match(['GET', 'POST', 'PATCH', 'PUT', 'DELETE'], '(:any)', static fn () => \App\Libraries\Mobile\ApiJson::error(new \App\Libraries\Mobile\ApiProblem('NOT_FOUND', 'Endpoint tidak ditemukan.', 404)));
});
