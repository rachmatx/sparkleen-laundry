<h2>Edit Layanan</h2>
<form action="/layanan/update/<?= $layanan['id_layanan'] ?>" method="post">
    <label>Nama Layanan:</label><br><input type="text" name="nama_layanan" value="<?= esc($layanan['nama_layanan']) ?>" required><br>
    <label>Harga per Kg:</label><br><input type="number" name="harga_per_kg" value="<?= esc($layanan['harga_per_kg']) ?>" required><br>
    <label>Estimasi (Hari):</label><br><input type="number" name="estimasi_hari" value="<?= esc($layanan['estimasi_hari']) ?>" required><br><br>
    <button type="submit">Update</button>
</form>