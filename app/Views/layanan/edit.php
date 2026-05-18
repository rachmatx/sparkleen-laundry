<!DOCTYPE html>
<html>
<head>
    <title>Edit Layanan</title>
</head>
<body>
    <h1>Edit Layanan Laundry</h1>
    <form action="/layanan/update/<?= $layanan['id_layanan'] ?>" method="post">
        <label>Nama Layanan</label><br>
        <input type="text" name="nama_layanan" value="<?= $layanan['nama_layanan'] ?>" required><br>
        <label>Harga per KG</label><br>
        <input type="number" name="harga_per_kg" value="<?= $layanan['harga_per_kg'] ?>" required><br>
        <label>Estimasi Hari</label><br>
        <input type="number" name="estimasi_hari" value="<?= $layanan['estimasi_hari'] ?>" required><br><br>
        <button type="submit">Update</button>
        <a href="/layanan">Batal</a>
    </form>
</body>
</html>