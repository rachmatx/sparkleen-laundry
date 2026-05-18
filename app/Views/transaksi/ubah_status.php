<!DOCTYPE html>
<html>
<head>
    <title>Update Status Laundry</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 500px; margin: auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { display: inline-block; padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; border: none; cursor: pointer; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .card { border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin-top: 20px; }
        .info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Update Status Laundry</h1>
        
        <div class="info">
            <p><strong>No Nota:</strong> <?= $transaksi['no_nota'] ?></p>
            <p><strong>Status Saat Ini:</strong> <?= ucfirst($transaksi['status']) ?></p>
        </div>
        
        <div class="card">
            <form action="/transaksi/prosesUbahStatus" method="post">
                <input type="hidden" name="id_transaksi" value="<?= $transaksi['id_transaksi'] ?>">
                
                <div class="form-group">
                    <label>Status Baru</label>
                    <select name="status" required>
                        <?php
                        $statuses = ['menunggu', 'proses', 'setrika', 'selesai', 'diambil'];
                        $current = $transaksi['status'];
                        $found = false;
                        foreach($statuses as $s):
                            if($s == $current):
                                $found = true;
                                continue;
                            endif;
                        ?>
                            <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                        <?php if(!$found): ?>
                            <option value="proses">Proses</option>
                            <option value="setrika">Setrika</option>
                            <option value="selesai">Selesai</option>
                            <option value="diambil">Diambil</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Catatan (Opsional)</label>
                    <textarea name="catatan" rows="3" placeholder="Contoh: Ada kerusakan, perlu perhatian khusus..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                <a href="/transaksi/detail/<?= $transaksi['id_transaksi'] ?>" class="btn btn-danger">Batal</a>
            </form>
        </div>
    </div>
</body>
</html>