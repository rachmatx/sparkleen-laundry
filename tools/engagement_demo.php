<?php
// Fictional integration balances, never an operational data migration.
if (PHP_SAPI !== 'cli') exit(1);
require __DIR__.'/../vendor/codeigniter4/framework/system/Test/bootstrap.php';
$config=config('Database')->tests;
if (ENVIRONMENT!=='testing'||$config['database']!=='laundry_db_test'||!in_array($config['hostname'],['127.0.0.1','localhost'],true)) throw new RuntimeException('Disposable localhost test database required.');
$db=db_connect('tests');
$db->table('tb_pelanggan')->where('id_pelanggan',1)->update(['poin'=>100,'total_transaksi'=>35000]);
$db->table('tb_transaksi')->where('id_transaksi',1)->update(['poin_diperoleh'=>3]);
echo "Fictional loyalty balance prepared.\n";
