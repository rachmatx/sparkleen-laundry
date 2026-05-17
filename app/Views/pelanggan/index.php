<h2>Data Pelanggan</h2>
<a href="/pelanggan/create">Tambah Pelanggan</a><br><br>
<table border="1" cellpadding="10" cellspacing="0">
    <tr><th>No</th><th>Kode</th><th>Nama</th><th>Telepon</th><th>Alamat</th><th>Poin</th><th>Aksi</th></tr>
    <?php $i=1; foreach($pelanggan as $p): ?>
    <tr>
        <td><?= $i++ ?></td>
        <td><?= esc($p['kode_pelanggan']) ?></td>
        <td><?= esc($p['nama_pelanggan']) ?></td>
        <td><?= esc($p['no_telp']) ?></td>
        <td><?= esc($p['alamat']) ?></td>
        <td><?= esc($p['poin']) ?></td>
        <td>
            <a href="/pelanggan/edit/<?= $p['id_pelanggan'] ?>">Edit</a> | 
            <a href="/pelanggan/delete/<?= $p['id_pelanggan'] ?>">Hapus</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>