<!DOCTYPE html>
<html>
<head>
    <title>Daftar Layanan</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Daftar Layanan Laundry</h1>
    <a href="/layanan/create">Tambah Layanan</a>
    
    <?php if(session()->getFlashdata('success')): ?>
        <p class="success"><?= session()->getFlashdata('success') ?></p>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr><th>Nama Layanan</th><th>Harga per KG</th><th>Estimasi Hari</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach($layanan as $l): ?>
            <tr>
                <td><?= $l['nama_layanan'] ?></td>
                <td>Rp <?= number_format($l['harga_per_kg'], 0, ',', '.') ?></td>
                <td><?= $l['estimasi_hari'] ?> hari</td>
                <td>
                    <a href="/layanan/edit/<?= $l['id_layanan'] ?>">Edit</a> |
                    <a href="#" onclick="if(confirm('Yakin hapus?')) window.location.href='/layanan/delete/<?= $l['id_layanan'] ?>'">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>