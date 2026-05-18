<!DOCTYPE html>
<html>
<head>
    <title>Daftar Karyawan</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Daftar Karyawan</h1>
    <a href="/karyawan/create">Tambah Karyawan</a>
    
    <?php if(session()->getFlashdata('success')): ?>
        <p class="success"><?= session()->getFlashdata('success') ?></p>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr><th>Nama</th><th>Username</th><th>Level</th><th>No Telp</th><th>Gaji Pokok</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach($karyawan as $k): ?>
            <tr>
                <td><?= $k['nama_karyawan'] ?></td>
                <td><?= $k['username'] ?></td>
                <td><?= ucfirst($k['level']) ?></td>
                <td><?= $k['no_telp'] ?></td>
                <td>Rp <?= number_format($k['gaji_pokok'], 0, ',', '.') ?></td>
                <td>
                    <a href="/karyawan/edit/<?= $k['id_karyawan'] ?>">Edit</a> |
                    <a href="#" onclick="if(confirm('Yakin hapus?')) window.location.href='/karyawan/delete/<?= $k['id_karyawan'] ?>'">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>