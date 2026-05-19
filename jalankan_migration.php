<?php
$db = mysqli_connect('localhost', 'root', '', 'laundry_db');
if (!$db) die('Koneksi gagal: ' . mysqli_connect_error());

// Tampilkan semua data karyawan dengan no_telp
$r = mysqli_query($db, "SELECT id_karyawan, nama_karyawan, no_telp FROM tb_karyawan ORDER BY id_karyawan");
echo "=== DATA KARYAWAN ===\n";
while ($row = mysqli_fetch_assoc($r)) {
    echo "ID: {$row['id_karyawan']} | Nama: {$row['nama_karyawan']} | Telp: {$row['no_telp']}\n";
}

// Drop key dulu
mysqli_query($db, "ALTER TABLE tb_karyawan DROP INDEX IF EXISTS uq_karyawan_telp");
mysqli_query($db, "ALTER TABLE tb_pelanggan DROP INDEX IF EXISTS uq_pelanggan_telp");

// Update no_telp karyawan duplikat pakai format lebih pendek
echo "\n=== UPDATE NO_TELP DUPLIKAT ===\n";
$dups = mysqli_query($db, "SELECT no_telp, GROUP_CONCAT(id_karyawan ORDER BY id_karyawan) as ids 
    FROM tb_karyawan GROUP BY LEFT(no_telp, 12) HAVING COUNT(*) > 1");

while ($dup = mysqli_fetch_assoc($dups)) {
    $ids = explode(',', $dup['ids']);
    array_shift($ids); // Simpan yang pertama, update sisanya
    foreach ($ids as $i => $id) {
        $newTelp = '08' . str_pad($id * 11, 9, '0', STR_PAD_LEFT);
        mysqli_query($db, "UPDATE tb_karyawan SET no_telp='$newTelp' WHERE id_karyawan=$id");
        echo "Update karyawan ID=$id => $newTelp\n";
    }
}

// Cek lagi apakah masih ada duplikat
$check = mysqli_query($db, "SELECT no_telp, COUNT(*) as c FROM tb_karyawan GROUP BY no_telp HAVING c > 1");
if (mysqli_num_rows($check) > 0) {
    echo "MASIH ADA DUPLIKAT!\n";
    while ($r2 = mysqli_fetch_assoc($check)) print_r($r2);
} else {
    echo "Tidak ada duplikat, lanjut tambah UNIQUE key...\n";
    
    if (mysqli_query($db, "ALTER TABLE tb_karyawan ADD UNIQUE KEY uq_karyawan_telp (no_telp)")) {
        echo "UNIQUE key karyawan: OK\n";
    } else {
        echo "ERROR: " . mysqli_error($db) . "\n";
    }
    
    if (mysqli_query($db, "ALTER TABLE tb_pelanggan ADD UNIQUE KEY uq_pelanggan_telp (no_telp)")) {
        echo "UNIQUE key pelanggan: OK\n";
    } else {
        echo "ERROR: " . mysqli_error($db) . "\n";
    }
}

mysqli_close($db);
echo "\nSELESAI.\n";
