<?php

$db = \Config\Database::connect();
$pelanggan = $db->table('tb_pelanggan')->orderBy('id_pelanggan', 'ASC')->get()->getResult();

$counter = 1;
foreach ($pelanggan as $p) {
    $kode = 'PLG' . str_pad($counter, 4, '0', STR_PAD_LEFT);
    $db->table('tb_pelanggan')
       ->where('id_pelanggan', $p->id_pelanggan)
       ->update(['kode_pelanggan' => $kode]);
    $counter++;
}

echo "Resequenced " . count($pelanggan) . " pelanggan.";
