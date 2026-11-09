<?php
// CLI-only helper for the real Dart/HTTP integration test. No operational DB.
if (PHP_SAPI !== 'cli' || !in_array($argv[1] ?? '', ['proses', 'konfirmasi'], true)) exit(1);
require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';
$config = config('Database')->tests;
if (ENVIRONMENT !== 'testing' || $config['database'] !== 'laundry_db_test' || !in_array($config['hostname'], ['localhost', '127.0.0.1'], true)) exit(2);
$id = filter_var($argv[2] ?? null, FILTER_VALIDATE_INT);
$row = $id ? db_connect()->table('tb_permintaan_jemput')->where('id_permintaan', $id)->where('id_pelanggan', 1)->get()->getRowArray() : null;
if (!$row) exit(3);
$result = (new \App\Services\PickupService())->act($id, 1, $argv[1], ['berat_aktual' => 5]);
echo json_encode(['status' => $result['status'], 'id_transaksi' => $result['id_transaksi']], JSON_THROW_ON_ERROR) . "\n";
