<!DOCTYPE html>
<html>
<head>
    <title>Edit Karyawan</title>
</head>
<body>
    <h1>Edit Karyawan</h1>
    <form action="/karyawan/update/<?= $karyawan['id_karyawan'] ?>" method="post">
        <label>Nama Karyawan</label><br>
        <input type="text" name="nama_karyawan" value="<?= $karyawan['nama_karyawan'] ?>" required><br>
        
        <label>Username</label><br>
        <input type="text" name="username" value="<?= $karyawan['username'] ?>" required><br>
        
        <label>Password (kosongkan jika tidak diubah)</label><br>
        <input type="password" name="password"><br>
        
        <label>Level</label><br>
        <select name="level" required>
            <option value="kasir" <?= $karyawan['level'] == 'kasir' ? 'selected' : '' ?>>Kasir</option>
            <option value="admin" <?= $karyawan['level'] == 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="pemilik" <?= $karyawan['level'] == 'pemilik' ? 'selected' : '' ?>>Pemilik</option>
        </select><br>
        
        <label>No Telp</label><br>
        <input type="text" name="no_telp" value="<?= $karyawan['no_telp'] ?>" required><br>
        
        <label>Gaji Pokok</label><br>
        <input type="number" name="gaji_pokok" value="<?= $karyawan['gaji_pokok'] ?>"><br><br>
        
        <button type="submit">Update</button>
        <a href="/karyawan">Batal</a>
    </form>
</body>
</html>