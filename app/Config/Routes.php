<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

# Pelanggan
$routes->group('pelanggan', function($routes) {
    $routes->get('/pelanggan', 'Pelanggan::index');
    $routes->get('/pelanggan/create', 'Pelanggan::create');
    $routes->post('/pelanggan/store', 'Pelanggan::store');
    $routes->get('/pelanggan/edit/(:num)', 'Pelanggan::edit/$1');
    $routes->post('/pelanggan/update/(:num)', 'Pelanggan::update/$1');
    $routes->get('/pelanggan/delete/(:num)', 'Pelanggan::delete/$1');
});

# Layanan
$routes->group('layanan', function($routes) {
    $routes->get('/layanan', 'Layanan::index');
    $routes->get('/layanan/create', 'Layanan::create');
    $routes->post('/layanan/store', 'Layanan::store');
    $routes->get('/layanan/edit/(:num)', 'Layanan::edit/$1');
    $routes->post('/layanan/update/(:num)', 'Layanan::update/$1');
    $routes->get('/layanan/delete/(:num)', 'Layanan::delete/$1');
});

# Karyawan
$routes->group('karyawan', function($routes) {
    $routes->get('/', 'Karyawan::index');
    $routes->get('create', 'Karyawan::create');
    $routes->post('store', 'Karyawan::store');
    $routes->get('edit/(:num)', 'Karyawan::edit/$1');
    $routes->post('update/(:num)', 'Karyawan::update/$1');
    $routes->get('delete/(:num)', 'Karyawan::delete/$1');
});