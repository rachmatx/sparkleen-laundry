<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Karyawan - Sparkleen</title>
</head>
<body>
    <h2>Edit Data Karyawan</h2>
    
    <form action="/karyawan/update/<?= $karyawan['id_karyawan'] ?>" method="post">
        <?= csrf_field() ?>
        
        <label>Nama Karyawan:</label><br>
        <input type="text" name="nama_karyawan" value="<?= esc($karyawan['nama_karyawan']) ?>" required><br><br>

        <label>Username:</label><br>
        <input type="text" name="username" value="<?= esc($karyawan['username']) ?>" required><br><br>

        <label>Password (Kosongkan jika tidak ingin mengubah):</label><br>
        <input type="password" name="password"><br><br>

        <label>Level Akses:</label><br>
        <select name="level" required>
            <option value="admin" <?= $karyawan['level'] == 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="kasir" <?= $karyawan['level'] == 'kasir' ? 'selected' : '' ?>>Kasir</option>
            <option value="pemilik" <?= $karyawan['level'] == 'pemilik' ? 'selected' : '' ?>>Pemilik</option>
        </select><br><br>

        <label>No. Telepon:</label><br>
        <input type="text" name="no_telp" value="<?= esc($karyawan['no_telp']) ?>" required><br><br>

        <label>Gaji Pokok:</label><br>
        <input type="number" name="gaji_pokok" value="<?= esc($karyawan['gaji_pokok']) ?>" required><br><br>

        <button type="submit">Update</button>
        <a href="/karyawan">Batal</a>
    </form>
</body>
</html>