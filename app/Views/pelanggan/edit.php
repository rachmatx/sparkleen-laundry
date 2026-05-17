<h2>Edit Pelanggan</h2>
<form action="/pelanggan/update/<?= $pelanggan['id_pelanggan'] ?>" method="post">
    <label>Nama:</label><br><input type="text" name="nama_pelanggan" value="<?= esc($pelanggan['nama_pelanggan']) ?>" required><br>
    <label>No Telp:</label><br><input type="text" name="no_telp" value="<?= esc($pelanggan['no_telp']) ?>" required><br>
    <label>Alamat:</label><br><textarea name="alamat" required><?= esc($pelanggan['alamat']) ?></textarea><br><br>
    <button type="submit">Update</button>
</form>