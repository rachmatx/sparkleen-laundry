<!DOCTYPE html>
<html>
<head>
    <title>Tambah Layanan</title>
</head>
<body>
    <h1>Tambah Layanan Laundry</h1>
    <form action="/layanan/store" method="post">
        <label>Nama Layanan</label><br>
        <input type="text" name="nama_layanan" required placeholder="Contoh: Cuci Reguler"><br>
        <label>Harga per KG</label><br>
        <input type="number" name="harga_per_kg" required placeholder="8000"><br>
        <label>Estimasi Hari (selesai)</label><br>
        <input type="number" name="estimasi_hari" required placeholder="2"><br><br>
        <button type="submit">Simpan</button>
        <a href="/layanan">Batal</a>
    </form>
</body>
</html>