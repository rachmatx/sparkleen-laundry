<h2>Data Layanan</h2>
<a href="/layanan/create">Tambah Layanan</a><br><br>
<table border="1" cellpadding="10" cellspacing="0">
    <tr><th>No</th><th>Nama Layanan</th><th>Harga/Kg</th><th>Estimasi (Hari)</th><th>Aksi</th></tr>
    <?php $i=1; foreach($layanan as $l): ?>
    <tr>
        <td><?= $i++ ?></td>
        <td><?= esc($l['nama_layanan']) ?></td>
        <td><?= esc($l['harga_per_kg']) ?></td>
        <td><?= esc($l['estimasi_hari']) ?></td>
        <td>
            <a href="/layanan/edit/<?= $l['id_layanan'] ?>">Edit</a> | 
            <a href="/layanan/delete/<?= $l['id_layanan'] ?>">Hapus</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>