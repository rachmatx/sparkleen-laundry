<?php
// Child worker for real-connection locking tests. Does not reset or seed a DB.
require __DIR__ . '/../../../vendor/codeigniter4/framework/system/Test/bootstrap.php';
$config = config('Database')->tests;
if (ENVIRONMENT !== 'testing' || $config['database'] !== 'laundry_db_test'
    || !in_array($config['hostname'], ['127.0.0.1', 'localhost'], true)) {
    exit(2);
}
$input = json_decode(fgets(STDIN), true, 32, JSON_THROW_ON_ERROR);
echo "READY\n";
flush();
if (trim(fgets(STDIN)) !== 'GO') exit(3);
echo "STARTING\n";
flush();
try {
    if ($input['kind'] === 'cash') {
        $result = (new \App\Services\TransaksiService())->prosesPembayaran(1, ['nominal_bayar' => 35000, 'id_metode_pembayaran' => 1]);
    } else {
        $result = ['success' => true, 'duplicate' => (new \App\Services\PaymentSettlementService())->apply($input['payload'])];
    }
} catch (\App\Libraries\Payments\PaymentProblem $error) {
    $result = ['success' => false, 'code' => $error->problemCode];
}
echo json_encode($result, JSON_THROW_ON_ERROR) . "\n";
