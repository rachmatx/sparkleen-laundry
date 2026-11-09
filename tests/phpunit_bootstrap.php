<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

// Destructive reset is restricted to the named disposable LOCAL test database.
$config = config('Database')->tests;
if (ENVIRONMENT !== 'testing' || $config['database'] !== 'laundry_db_test'
    || !in_array($config['hostname'], ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('Refusing to reset a non-local or non-test database.');
}
$db = db_connect('tests');
$db->disableForeignKeyChecks();
foreach ($db->listTables() as $table) {
    $db->query('DROP TABLE ' . $db->protectIdentifiers($table));
}
$db->enableForeignKeyChecks();
service('routes')->loadRoutes();
