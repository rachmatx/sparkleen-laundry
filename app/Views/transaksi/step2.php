<!DOCTYPE html>
<html>
<head>
    <title>Input Transaksi Laundry</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f2f2f2; }
        .btn { display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        .form-group { margin-bottom: 15px; }
        input, select { padding: 5px; width: 200px; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Transaksi Laundry</h1>
    <p><strong>Pelanggan:</strong> <?= $pelanggan['nama_pelanggan'] ?> (<?= $pelanggan['kode_pelanggan'] ?>)</p>
    
    <?php if(session()->getFlashdata('success')): ?>
        <p class="success"><?= session()->getFlashdata('success') ?></p>
    <?php endif; ?>
    
    <?php if(session()->getFlashdata('error')): ?>
        <p class="error"><?= session()->getFlashdata('error') ?></p>
    <?php endif; ?>

    <hr>

    <h2>Tambah Layanan</h2>
    <form action="/transaksi/addToCart" method="post">
        <div class="form-group">
            <label>Jenis Layanan</label>
            <select name="id_layanan" required>
                <option value="">-- Pilih --</option>
                <?php foreach($layanan as $l): ?>
                <option value="<?= $l['id_layanan'] ?>"><?= $l['nama_layanan'] ?> - Rp <?= number_format($l['harga_per_kg'],0,',','.') ?>/kg</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Berat (kg)</label>
            <input type="number" step="0.1" name="berat" required>
        </div>
        <button type="submit" class="btn">+ Tambah ke Keranjang</button>
    </form>

    <hr>

    <h2>Keranjang Belanja</h2>
    <table>
        <thead>
            <tr><th>Layanan</th><th>Berat (kg)</th><th>Subtotal</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php 
            $totalBerat = 0;
            $totalBiaya = 0;
            if(!empty($temp)):
                foreach($temp as $t):
                    // Cari data layanan dari array $layanan yang sudah dikirim dari controller
                    $namaLayanan = '';
                    $harga = 0;
                    foreach($layanan as $l):
                        if($l['id_layanan'] == $t['id_layanan']):
                            $namaLayanan = $l['nama_layanan'];
                            $harga = $l['harga_per_kg'];
                            break;
                        endif;
                    endforeach;
                    $subtotal = $t['berat'] * $harga;
                    $totalBerat += $t['berat'];
                    $totalBiaya += $subtotal;
            ?>
            <tr>
                <td><?= $namaLayanan ?></td>
                <td><?= $t['berat'] ?> kg</td>
                <td>Rp <?= number_format($subtotal,0,',','.') ?></td>
                <td><a href="/transaksi/removeFromCart/<?= $t['id_temp'] ?>" class="btn btn-danger" onclick="return confirm('Hapus item ini?')">Hapus</a></td>
            </tr>
            <?php 
                endforeach;
            else:
            ?>
            <tr><td colspan="4" style="text-align:center">Keranjang masih kosong</td></tr>
            <?php endif; ?>
        </tbody>
        <?php if(!empty($temp)): ?>
        <tfoot>
            <tr><th colspan="2">Total</th><th>Rp <?= number_format($totalBiaya,0,',','.') ?></th><th></th></tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <?php if(!empty($temp)): ?>
    <form action="/transaksi/simpanTransaksi" method="post" style="margin-top:20px">
        <button type="submit" class="btn btn-success">Simpan Transaksi</button>
    </form>
    <?php endif; ?>

    <p style="margin-top:20px"><a href="/transaksi" class="btn">Pilih Pelanggan Lain</a></p>
    <p><a href="/dashboard" class="btn">Kembali ke Dashboard</a></p>
</body>
</html>