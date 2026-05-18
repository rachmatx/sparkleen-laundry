<!DOCTYPE html>
<html>
<head>
    <title>Pilih Pelanggan - Transaksi Laundry</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f2f2f2; }
        .btn { display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; }
        .btn-success { background: #28a745; }
        .header { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Transaksi Laundry</h1>
        <a href="/dashboard" class="btn">Kembali ke Dashboard</a>
    </div>

    <h2>Step 1: Pilih Pelanggan</h2>
    
    <?php if(session()->getFlashdata('error')): ?>
        <div style="color:red"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr><th>Kode</th><th>Nama</th><th>No Telp</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach($pelanggan as $p): ?>
            <tr>
                <td><?= $p['kode_pelanggan'] ?></td>
                <td><?= $p['nama_pelanggan'] ?></td>
                <td><?= $p['no_telp'] ?></td>
                <td><a href="/transaksi/pilih-layanan/<?= $p['id_pelanggan'] ?>" class="btn btn-success">Pilih</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p style="margin-top:20px">Belum punya pelanggan? <a href="/pelanggan/create">Tambah Pelanggan Baru</a></p>
</body>
</html>