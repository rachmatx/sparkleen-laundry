<?php
$db = mysqli_connect('localhost', 'root', '', 'laundry_db');
if (!$db) {
    die('Koneksi gagal: ' . mysqli_connect_error());
}

echo "=== DUPLIKAT NO_TELP DI tb_karyawan ===\n";
$r = mysqli_query($db, "SELECT no_telp, COUNT(*) as c, GROUP_CONCAT(id_karyawan) as ids FROM tb_karyawan GROUP BY no_telp HAVING c > 1");
$found = false;
while ($row = mysqli_fetch_assoc($r)) {
    echo "Telp: {$row['no_telp']} | Jumlah: {$row['c']} | IDs: {$row['ids']}\n";
    $found = true;
}
if (!$found) echo "Tidak ada duplikat.\n";

echo "\n=== DUPLIKAT NO_TELP DI tb_pelanggan ===\n";
$r2 = mysqli_query($db, "SELECT no_telp, COUNT(*) as c, GROUP_CONCAT(id_pelanggan) as ids FROM tb_pelanggan GROUP BY no_telp HAVING c > 1");
$found2 = false;
while ($row = mysqli_fetch_assoc($r2)) {
    echo "Telp: {$row['no_telp']} | Jumlah: {$row['c']} | IDs: {$row['ids']}\n";
    $found2 = true;
}
if (!$found2) echo "Tidak ada duplikat.\n";

mysqli_close($db);
echo "\nSelesai.\n";
