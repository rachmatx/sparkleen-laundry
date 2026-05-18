<!DOCTYPE html>
<html>
<head>
    <title>Tambah Pelanggan</title>
</head>
<body>
    <h1>Tambah Pelanggan</h1>
    <form action="/pelanggan/store" method="post">
        <label>Kode Pelanggan</label><br>
        <input type="text" name="kode_pelanggan" value="<?= $kode ?>" readonly><br>
        <label>Nama Pelanggan</label><br>
        <input type="text" name="nama_pelanggan" required><br>
        <label>No Telp</label><br>
        <input type="text" name="no_telp" required><br>
        <label>Alamat</label><br>
        <textarea name="alamat" required></textarea><br><br>
        <button type="submit">Simpan</button>
        <a href="/pelanggan">Batal</a>
    </form>
</body>
</html>