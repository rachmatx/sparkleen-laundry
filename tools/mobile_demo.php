<?php
/** Local integration fixture. Never run against an operational database. */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$action = $argv[1] ?? '';
if (!in_array($action, ['reset', 'cashier-update'], true)) {
    fwrite(STDERR, "Usage: php tools/mobile_demo.php reset|cashier-update\n");
    exit(1);
}
require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';
$config = config('Database')->tests;
if (ENVIRONMENT !== 'testing' || $config['database'] !== 'laundry_db_test' || !in_array($config['hostname'], ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('Only the local laundry_db_test database may be used.');
}
$db = db_connect('tests');
if ($action === 'reset') {
    $db->disableForeignKeyChecks();
    foreach ($db->listTables() as $table) {
        $db->query('DROP TABLE ' . $db->protectIdentifiers($table));
    }
    $db->enableForeignKeyChecks();
    $db->resetDataCache();
    service('migrations')->setNamespace(null)->latest('tests');
    \Config\Database::seeder('tests')->call(\Tests\Support\Database\Seeds\TestDataSeeder::class);
    $db->table('tb_pelanggan')->where('id_pelanggan', 1)->update(['no_telp' => '081298765432', 'pin_hash' => password_hash('654321', PASSWORD_DEFAULT), 'pin_set_at' => date('Y-m-d H:i:s')]);
    $db->table('tb_layanan')->where('id_layanan', 1)->update(['nama_layanan' => 'Cuci Kering']);
    $db->table('tb_layanan')->where('id_layanan', 2)->update(['nama_layanan' => 'Cuci & Setrika']);
    echo "Local mobile demo fixtures ready (customer 1).\n";
} else {
    $service = new \App\Services\TransaksiService();
    $order = $db->table('tb_transaksi')->where('id_pelanggan', 1)->where('deleted_at', null)->orderBy('id_transaksi', 'DESC')->get(1)->getRowArray();
    $id = (int) $order['id_transaksi'];
    $result = $service->prosesUbahStatus($id, ['id_status_transaksi' => 4, 'id_karyawan' => 1, 'kirim_wa' => '0'], 'kasir');
    if (!$result['success']) {
        throw new RuntimeException('Cashier status update failed.');
    }
    $remaining = $service->hitungSisaTagihan($id);
    if ($remaining > 0) {
        $result = $service->prosesPembayaran($id, ['nominal_bayar' => $remaining, 'id_metode_pembayaran' => 1]);
        if (!$result['success']) {
            throw new RuntimeException('Cash payment failed.');
        }
    }
    echo "Cashier service updated local demo order.\n";
}
