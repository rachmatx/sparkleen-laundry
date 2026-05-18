<!DOCTYPE html>
<html>
<head>
    <title>Tambah Karyawan</title>
</head>
<body>
    <h1>Tambah Karyawan</h1>
    <form action="/karyawan/store" method="post">
        <label>Nama Karyawan</label><br>
        <input type="text" name="nama_karyawan" required><br>
        
        <label>Username</label><br>
        <input type="text" name="username" required><br>
        
        <label>Password</label><br>
        <input type="password" name="password" required><br>
        
        <label>Level</label><br>
        <select name="level" required>
            <option value="kasir">Kasir</option>
            <option value="admin">Admin</option>
            <option value="pemilik">Pemilik</option>
        </select><br>
        
        <label>No Telp</label><br>
        <input type="text" name="no_telp" required><br>
        
        <label>Gaji Pokok</label><br>
        <input type="number" name="gaji_pokok" value="0"><br><br>
        
        <button type="submit">Simpan</button>
        <a href="/karyawan">Batal</a>
    </form>
</body>
</html>