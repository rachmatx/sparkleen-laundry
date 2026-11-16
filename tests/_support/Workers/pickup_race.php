<?php
require __DIR__ . '/../../../vendor/codeigniter4/framework/system/Test/bootstrap.php';
$config = config('Database')->tests;
if (ENVIRONMENT !== 'testing' || $config['database'] !== 'laundry_db_test' || !in_array($config['hostname'], ['localhost', '127.0.0.1'], true)) exit(2);
$input = json_decode(fgets(STDIN), true, 32, JSON_THROW_ON_ERROR);
echo "READY\n"; flush();
if (trim(fgets(STDIN)) !== 'GO') exit(3);
echo "STARTING\n"; flush();
try {
    $service = new \App\Services\PickupService();
    $result = $input['kind'] === 'create' ? $service->create(1, $input['body']) : $service->act($input['id'], 1, 'konfirmasi', ['berat_aktual' => 5]);
    echo json_encode(['success' => true, 'data' => $result], JSON_THROW_ON_ERROR) . "\n";
} catch (\App\Libraries\Mobile\ApiProblem $error) {
    echo json_encode(['success' => false, 'code' => $error->apiCode], JSON_THROW_ON_ERROR) . "\n";
}
