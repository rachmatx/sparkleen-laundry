# Deploy ke Railway (GRATIS)

## Syarat
- Akun GitHub (login dengan GitHub di Railway)
- Repository GitHub dengan Dockerfile + docker-compose.yml
- API token Fonnte (sudah punya)

## Step-by-Step

### 1. Push ke GitHub
```bash
git add .
git commit -m "Add Railway config"
git push origin main
```

### 2. Login ke Railway
- Buka https://railway.app
- Click "Login"
- Pilih "Login with GitHub"
- Authorize Railway untuk access GitHub

### 3. Deploy dari GitHub
- Di Railway dashboard, click "New Project"
- Pilih "Deploy from GitHub repo"
- Cari repository `sparkleen` lu
- Click "Deploy"

Railway akan:
- Membaca `docker-compose.yml`
- Build Docker image
- Jalankan container
- Generate URL publik (misal: `sparkleen-prod.railway.app`)

### 4. Setup Environment Variables

Di Railway dashboard, tab "Variables":

Add ini:
```
MYSQL_PASSWORD=PASSWORD_KUAT_RANDOM
WHATSAPP_TOKEN=TOKEN_FONNTE_LU
DB_PASSWORD=PASSWORD_KUAT_RANDOM
```

(Ganti PASSWORD_KUAT_RANDOM dengan password random)

### 5. Setup Database Service (Optional tapi recommended)

- Di Railway project, click "+ Add Service"
- Pilih "MySQL"
- Railway auto-generate `MYSQL_*` variables

### 6. Deploy Database Migration

Setelah container running, jalankan migration:

Di Railway dashboard → "Logs", atau via CLI:
```bash
railway run php spark migrate
```

### 7. Cek Status

URL aplikasi: `https://RAILWAY_PUBLIC_DOMAIN/`

(Railway generate domain otomatis, biasanya `sparkleen-prod-RANDOM.railway.app`)

---

## Keuntungan Railway untuk Tugas:
✅ Free $5/bulan (cukup buat tugas)
✅ Deploy dari GitHub (auto-update)
✅ MySQL database included
✅ Auto-restart kalau crash
✅ Logs tersedia di dashboard
✅ Tidak perlu credit card buat tier free

## Troubleshooting

**Aplikasi error 500?**
```
- Cek logs di Railway dashboard
- Pastikan DB migration sudah jalan
- Check environment variables set dengan benar
```

**Database connection error?**
```
- Verify MYSQL_PASSWORD di variables match
- Pastikan MySQL service sudah running
```

**Timeout saat deploy?**
```
- Docker image terlalu besar (~500MB)
- Tunggu sampai selesai (~10 menit first time)
```
