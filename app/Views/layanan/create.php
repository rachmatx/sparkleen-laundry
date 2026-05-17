<h2>Tambah Layanan</h2>
<form action="/layanan/store" method="post">
    <label>Nama Layanan:</label><br><input type="text" name="nama_layanan" required><br>
    <label>Harga per Kg:</label><br><input type="number" name="harga_per_kg" required><br>
    <label>Estimasi (Hari):</label><br><input type="number" name="estimasi_hari" required><br><br>
    <button type="submit">Simpan</button>
</form>