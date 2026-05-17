<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Karyawan - Sparkleen</title>
</head>
<body>
    <h2>Data Karyawan</h2>
    <a href="/karyawan/create">Tambah Karyawan Baru</a>
    <br><br>

    <?php if (session()->getFlashdata('success')) : ?>
        <p style="color: green;"><?= session()->getFlashdata('success') ?></p>
    <?php endif; ?>

    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Username</th>
                <th>Level</th>
                <th>No. Telp</th>
                <th>Gaji Pokok</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($karyawan as $k) : ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= esc($k['nama_karyawan']) ?></td>
                <td><?= esc($k['username']) ?></td>
                <td><?= esc($k['level']) ?></td>
                <td><?= esc($k['no_telp']) ?></td>
                <td>Rp <?= number_format($k['gaji_pokok'], 0, ',', '.') ?></td>
                <td>
                    <a href="/karyawan/edit/<?= $k['id_karyawan'] ?>">Edit</a> | 
                    <a href="/karyawan/delete/<?= $k['id_karyawan'] ?>" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>