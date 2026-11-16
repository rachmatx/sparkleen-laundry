<?php
/** Disposable local fixture for the opt-in Node → Laundry HTTP test. */
if (PHP_SAPI !== 'cli' || !in_array($argv[1] ?? '', ['reset', 'inspect'], true)) exit(1);
$action = $argv[1];
if ($action === 'reset') {
    // Reuse its guarded reset of local laundry_db_test and fictional seeder.
    require __DIR__ . '/mobile_demo.php';
} else {
    require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';
}
$config = config('Database')->tests;
if (ENVIRONMENT !== 'testing' || $config['database'] !== 'laundry_db_test'
    || !in_array($config['hostname'], ['127.0.0.1', 'localhost'], true)) exit(2);
$db = db_connect('tests');
if ($action === 'reset') {
    $order = $db->table('tb_transaksi')->where('id_transaksi', 1)->get()->getRowArray();
    $db->transBegin();
    (new \App\Services\PaymentSettlementService())->recordIntent($order, [
        'transaction_id' => 'webhook-http-demo', 'merchant_id' => 'M-http-demo',
        'customer_ref' => $order['no_nota'], 'amount' => 35000, 'method' => 'QRIS', 'status' => 'PENDING',
    ], 35000, 'QRIS');
    $db->table('tb_transaksi')->where('id_transaksi', 1)->update(['payment_ref' => 'webhook-http-demo', 'id_metode_pembayaran' => 2]);
    if (!$db->transStatus() || !$db->transCommit()) exit(3);
    echo "Local webhook fixture ready.\n";
} else {
    $order = $db->table('tb_transaksi')->where('id_transaksi', 1)->get()->getRowArray();
    $paid = $db->table('tb_pembayaran')->selectSum('nominal_bayar', 'total')->where('id_transaksi', 1)->get()->getRowArray();
    echo json_encode([
        'count' => $db->table('tb_pembayaran')->where('id_transaksi', 1)->countAllResults(),
        'amount' => (int) ($paid['total'] ?? 0), 'status' => (int) $order['id_status_pembayaran'],
        'nota' => $order['no_nota'],
    ], JSON_THROW_ON_ERROR) . "\n";
}
