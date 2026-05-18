<!DOCTYPE html>
<html>
<head>
    <title>Laporan Laundry</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .menu { display: flex; gap: 20px; margin-top: 20px; }
        .card { 
            border: 1px solid #ddd; 
            padding: 30px; 
            border-radius: 8px; 
            text-align: center;
            width: 250px;
            background: #f8f9fa;
        }
        .card h2 { margin: 0 0 10px 0; }
        .card p { color: #666; }
        .btn { 
            display: inline-block; 
            padding: 10px 20px; 
            background: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px;
            margin-top: 15px;
        }
        .btn-success { background: #28a745; }
        .header { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Laundry</h1>
        <a href="/dashboard" class="btn">Kembali ke Dashboard</a>
    </div>
    
    <div class="menu">
        <div class="card">
            <h2>📅 Laporan Harian</h2>
            <p>Lihat pendapatan per hari</p>
            <a href="/laporan/harian" class="btn btn-success">Lihat</a>
        </div>
        <div class="card">
            <h2>📆 Laporan Bulanan</h2>
            <p>Lihat pendapatan per bulan</p>
            <a href="/laporan/bulanan" class="btn btn-success">Lihat</a>
        </div>
    </div>
</body>
</html>