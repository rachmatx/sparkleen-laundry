# Sparkleen Laundry Management System

A premium laundry management system built on top of **CodeIgniter 4** framework. This application features order tracking, transaction management, payment tracking, inventory usage logs, and WhatsApp notification integration.

---

## 🚀 Fitur Utama
1. **Manajemen Transaksi & Kasir**: Pembuatan order, pencetakan nota kasir termal (POS), kalkulasi tagihan, dan sisa kembalian secara presisi.
2. **Notifikasi WhatsApp**: Integrasi pengiriman notifikasi otomatis via API Fonnte/Wablas atau tautan manual (`wa.me`) ketika pesanan laundry selesai.
3. **Manajemen Inventaris**: Pengurangan stok otomatis barang habis pakai (seperti sabun/pewangi) saat transaksi diproses.
4. **Log Perubahan Status & Aktivitas**: Riwayat status laundry terpantau transparan beserta catatan kasir.
5. **Keamanan & Kontrol Akses (RBAC)**: Pembatasan ketat untuk kasir, admin, dan pemilik (owner).

---

## ⚙️ Persyaratan Sistem
- **PHP** versi 8.2 atau lebih tinggi.
- Ekstensi PHP wajib diaktifkan: `intl`, `mbstring`, `json`, `mysqlnd`, `libcurl`.
- Database **MySQL** / MariaDB.

---

## 📥 Panduan Instalasi & Setup

1. **Clone Repositori & Salin Konfigurasi**:
   Salin file template `.env.example` menjadi `.env` di direktori utama:
   ```bash
   cp .env.example .env
   ```

2. **Konfigurasi Database & Base URL**:
   Buka file `.env` yang baru dibuat dan sesuaikan konfigurasi database Anda:
   ```env
   CI_ENVIRONMENT = development
   app.baseURL = 'http://localhost:8080/'

   database.default.hostname = localhost
   database.default.database = nama_database_laundry
   database.default.username = root
   database.default.password = 
   ```

3. **Install Dependensi Composer**:
   ```bash
   composer install
   ```

4. **Jalankan Migrasi Database**:
   ```bash
   php spark db:create nama_database_laundry (jika belum ada database)
   php spark migrate
   php spark db:seed (jika ada seeder)
   ```

5. **Jalankan Server Lokal**:
   ```bash
   php spark serve
   ```
   Aplikasi dapat diakses di `http://localhost:8080` (atau sesuaikan port default Anda).

---

## 🔒 Keamanan Repositori (Git & Kredensial)

Untuk menjaga keamanan informasi rahasia proyek:
- File `.env` **wajib diabaikan** dari Git (tidak boleh di-upload ke GitHub). File ini sudah didaftarkan pada `.gitignore`.
- Jangan pernah menulis kredensial database asli, password, atau Token API WhatsApp di file selain `.env` lokal Anda.
- Jika ada penambahan variabel konfigurasi baru, daftarkan variabel baru tersebut ke file `.env.example` dengan nilai kosong/placeholder sebagai panduan bagi developer lain.

---

## 💬 Konfigurasi Notifikasi WhatsApp

Sistem ini mendukung pengiriman notifikasi otomatis saat status pesanan diubah menjadi **Selesai**. Konfigurasikan ini di file `.env` lokal Anda:

```env
# 1 = aktifkan, 0 = nonaktifkan
whatsapp.enabled = true

# Pilihan provider: link | fonnte | wablas
whatsapp.provider = fonnte

# Token API resmi dari Fonnte / Wablas Anda
whatsapp.apiToken = TOKEN_API_ANDA_DISINI

# URL API (kosongkan saja untuk menggunakan default provider)
whatsapp.apiUrl = https://api.fonnte.com/send

# Nama Laundry Anda yang akan muncul di teks pesan
whatsapp.businessName = "Sparkleen Laundry"
```

### Jenis Provider:
* **`fonnte` / `wablas`**: Mengirimkan notifikasi WhatsApp secara **otomatis** di latar belakang menggunakan jalur API Gateway masing-masing.
* **`link` (Manual)**: Tidak mengirim otomatis, melainkan menampilkan tombol **"Kirim WhatsApp"** di halaman Detail Transaksi yang mengarah ke tautan `wa.me` (berisi nomor tujuan & teks template pesan otomatis yang siap dikirim lewat web/aplikasi WhatsApp).
