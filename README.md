# Sparkleen Laundry Management System

A premium laundry management system built on top of **CodeIgniter 4** framework. This application features order tracking, transaction management, payment tracking, inventory usage logs, and WhatsApp notification integration.

---

## Fitur Utama
1. **Manajemen Transaksi & Kasir**: Pembuatan order, pencetakan nota kasir termal (POS), kalkulasi tagihan, loyalitas poin, dan sisa kembalian secara presisi.
2. **Resep Otomatis & Inventaris**: Pengurangan stok otomatis bahan baku (seperti sabun/pewangi) berdasarkan berat cucian menggunakan formula takaran (resep) default per layanan yang dapat diatur oleh Admin.
3. **Program Poin Loyalitas**: Akumulasi poin belanja otomatis untuk pelanggan (tiap kelipatan Rp 10.000) dan penukaran poin menjadi diskon pemotong tagihan (1 poin = Rp 300 diskon).
4. **Notifikasi WhatsApp Multi-Tahap**: Integrasi pengiriman otomatis via API Fonnte/Wablas atau tautan manual (`wa.me`) ketika pesanan laundry Baru Diterima, Selesai Dicuci, atau Diambil Pelanggan.
5. **Log Keamanan & Backup Database**: Pencatatan log upaya keamanan login secara dinamis di database, serta penyediaan fitur ekspor (backup) database terenkripsi ke file SQL secara aman dan instan.
6. **Integrasi Ekosistem Smart Pay**: Tersambung secara langsung dengan sistem Node.js Payment API dan aplikasi mobile Flutter Smart Pay untuk memberikan fitur pembayaran QRIS dan Transfer otomatis tanpa verifikasi manual kasir.

---

## Persyaratan Sistem
- **PHP** versi 8.2 atau lebih tinggi.
- Ekstensi PHP wajib diaktifkan: `intl`, `mbstring`, `json`, `mysqlnd`, `libcurl`.
- Database **MySQL** / MariaDB.

---

## Panduan Instalasi & Setup

Proyek ini mendukung dua metode instalasi: **Lokal (Manual)** dan **Docker Container**.

### Metode 1: Instalasi dengan Docker (Direkomendasikan)
Aplikasi ini sudah dipersiapkan penuh untuk dijalankan di dalam container Docker beserta database bawaan.
1. **Clone Repositori**: `git clone <url-repo-anda> && cd laundry_app`
2. **Siapkan Konfigurasi**: `cp .env.example .env`
3. **Jalankan Docker Compose**:
   ```bash
   docker compose up -d --build
   ```
4. **Migrasi Database**:
   ```bash
   docker compose exec app php spark migrate
   ```
   *(Aplikasi otomatis dapat diakses di `http://localhost:8080`)*

### Metode 2: Instalasi Lokal (Manual)
1. **Clone Repositori & Salin Konfigurasi**:
   ```bash
   cp .env.example .env
   ```
2. **Konfigurasi Database & Base URL**: Sesuaikan `.env` Anda (hostname, database, username, password).
3. **Install Dependensi Composer**:
   ```bash
   composer install
   ```
4. **Jalankan Migrasi Database**:
   ```bash
   php spark db:create nama_database_laundry
   php spark migrate
   ```
5. **Jalankan Server Lokal**:
   ```bash
   php spark serve
   ```

---

## Konfigurasi Payment API & Smart Pay (Penting)

Aplikasi Laundry ini merupakan ujung tombak dari ekosistem pembayaran Smart Pay. Saat Anda mempublikasikan aplikasi ini ke produksi, Anda **wajib** menyambungkannya dengan server Payment API (Node.js).

Buka file `.env` dan atur bagian berikut:

```env
# URL server Payment API (contoh URL dari Railway)
PAYMENT_API_URL="https://payment-api-production-ba66.up.railway.app/api/transactions"

# API Key rahasia bawaan server Node.js (harus sama dengan Node.js dan Flutter)
PAYMENT_API_KEY="my-super-secret-key-123"
```

Tanpa pengaturan di atas, fitur pembayaran "Transfer" dan "QRIS" yang muncul di layar kasir Anda tidak akan dapat diteruskan ke aplikasi mobile Smart Pay pelanggan.

---

## Panduan Deployment (Vercel, Railway, VPS)

Aplikasi ini sudah dioptimalkan untuk deployment di berbagai platform Cloud dan VPS menggunakan arsitektur Docker. Untuk instruksi lebih lengkap, silakan merujuk ke dokumen berikut:
* **Railway (CI/CD)**: Baca panduan di file `RAILWAY_DEPLOY.md`.
* **VPS (Docker & Nginx SSL)**: Baca panduan lengkap di file `DEPLOYMENT.md`.

---

## Keamanan Repositori (Git & Kredensial)

Untuk menjaga keamanan informasi rahasia proyek:
- File `.env` **wajib diabaikan** dari Git (tidak boleh di-upload ke GitHub).
- Jangan pernah menulis kredensial database asli, password, atau Token API WhatsApp di file selain `.env` lokal Anda.
- Jika ada penambahan variabel konfigurasi baru, daftarkan variabel baru tersebut ke file `.env.example` dengan nilai kosong/placeholder sebagai panduan bagi developer lain.

---

## Konfigurasi Notifikasi WhatsApp

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

---

## Changelog & Pembaruan Sistem Terkini

Berbagai peningkatan **UI/UX dan Performa** telah diimplementasikan:

1. **Integrasi Smart Pay**: Sistem kasir kini mendukung pembuatan sesi pembayaran (Payment Session) ke perantara API Node.js secara dinamis.
2. **Metadata Transaksi**: Penggunaan kolom `payment_detail` bertipe TEXT pada MySQL untuk melacak referensi pelanggan tanpa merombak skema yang kaku.
3. **Penyempurnaan UI & Notifikasi (SweetAlert2)**: Menghilangkan notifikasi bawaan browser (`alert()` & `confirm()`) pada fitur keranjang belanja dan pembatalan API.
4. **Live Search (AJAX)**: Pencarian pelanggan, transaksi, layanan, dan inventaris secara real-time tanpa perlu reload halaman.
5. **Soft Deletes Transaksi**: Memastikan seluruh data riwayat transaksi tidak terhapus permanen dari basis data (Soft Delete).
6. **Auto-Tracking WhatsApp**: Memperbaiki tautan pelacakan status pesanan publik pada notifikasi otomatis WhatsApp agar halaman Lacak Order langsung menampilkan detail pesanan seketika setelah di-klik.
