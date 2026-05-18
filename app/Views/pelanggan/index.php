<!DOCTYPE html>
<html>
<head>
    <title>Daftar Pelanggan</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Daftar Pelanggan</h1>
    <a href="/pelanggan/create">Tambah Pelanggan</a>
    
    <?php if(session()->getFlashdata('success')): ?>
        <p class="success"><?= session()->getFlashdata('success') ?></p>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr><th>Kode</th><th>Nama</th><th>No Telp</th><th>Alamat</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach($pelanggan as $p): ?>
            <tr>
                <td><?= $p['kode_pelanggan'] ?></td>
                <td><?= $p['nama_pelanggan'] ?></td>
                <td><?= $p['no_telp'] ?></td>
                <td><?= $p['alamat'] ?></td>
                <td>
                    <a href="/pelanggan/edit/<?= $p['id_pelanggan'] ?>">Edit</a> |
                    <a href="#" onclick="if(confirm('Yakin hapus?')) window.location.href='/pelanggan/delete/<?= $p['id_pelanggan'] ?>'">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>