<?php
define('FCPATH', __DIR__ . '/public/');
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';
require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();
$app = \Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();
$penggunaan = $db->query("SELECT * FROM tb_penggunaan_inventaris")->getResultArray();
echo "PENGGUNAAN:\n";
print_r($penggunaan);

$inventaris = $db->query("SELECT * FROM tb_inventaris")->getResultArray();
echo "INVENTARIS:\n";
print_r($inventaris);
