<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Karyawan - Sparkleen</title>
</head>
<body>
    <h2>Tambah Data Karyawan</h2>
    
    <form action="/karyawan/store" method="post">
        <?= csrf_field() ?>
        
        <label>Nama Karyawan:</label><br>
        <input type="text" name="nama_karyawan" required><br><br>

        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Level Akses:</label><br>
        <select name="level" required>
            <option value="admin">Admin</option>
            <option value="kasir">Kasir</option>
            <option value="pemilik">Pemilik</option>
        </select><br><br>

        <label>No. Telepon:</label><br>
        <input type="text" name="no_telp" required><br><br>

        <label>Gaji Pokok:</label><br>
        <input type="number" name="gaji_pokok" required><br><br>

        <button type="submit">Simpan</button>
        <a href="/karyawan">Batal</a>
    </form>
</body>
</html>