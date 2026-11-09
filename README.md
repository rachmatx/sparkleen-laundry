# Sparkleen Laundry Management System

<div align="center">

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.7-EF4223?style=for-the-badge&logo=codeigniter&logoColor=white)](https://codeigniter.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)](docker-compose.yml)

Sistem manajemen laundry premium berbasis **CodeIgniter 4** yang dilengkapi dengan pelacakan pesanan, manajemen transaksi, inventaris otomatis, program loyalitas poin, notifikasi WhatsApp, dan integrasi pembayaran Smart Pay.

</div>

---

## Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Panduan Instalasi](#panduan-instalasi--setup)
- [Akun Default](#akun-default-setelah-seeder)
- [Konfigurasi Payment API](#konfigurasi-payment-api--smart-pay)
- [Konfigurasi WhatsApp](#konfigurasi-notifikasi-whatsapp)
- [Keamanan Repositori](#keamanan-repositori)
- [Panduan Deployment](#panduan-deployment)
- [Changelog](#changelog--pembaruan)

---

## Fitur Utama

1. **Manajemen Transaksi & Kasir** — Pembuatan order, pencetakan nota kasir termal (POS), kalkulasi tagihan, loyalitas poin, dan sisa kembalian secara presisi.
2. **Resep Otomatis & Inventaris** — Pengurangan stok otomatis bahan baku (seperti sabun/pewangi) berdasarkan berat cucian menggunakan formula takaran (resep) default per layanan yang dapat diatur oleh Admin.
3. **Program Poin Loyalitas** — Akumulasi poin belanja otomatis untuk pelanggan (tiap kelipatan Rp 10.000) dan penukaran poin menjadi diskon pemotong tagihan (1 poin = Rp 300 diskon).
4. **Notifikasi WhatsApp Multi-Tahap** — Integrasi pengiriman otomatis via API Fonnte/Wablas atau tautan manual (`wa.me`) ketika pesanan laundry Baru Diterima, Selesai Dicuci, atau Diambil Pelanggan.
5. **Log Keamanan & Backup Database** — Pencatatan log upaya keamanan login secara dinamis di database, serta penyediaan fitur ekspor (backup) database terenkripsi ke file SQL secara aman dan instan.
6. **Integrasi Ekosistem Smart Pay** — Tersambung secara langsung dengan sistem Node.js Payment API dan aplikasi mobile Flutter Smart Pay untuk memberikan fitur pembayaran QRIS dan Transfer otomatis tanpa verifikasi manual kasir.
7. **Live Search (AJAX)** — Pencarian pelanggan, transaksi, layanan, dan inventaris secara real-time tanpa perlu reload halaman.
8. **Soft Delete Transaksi** — Seluruh data riwayat transaksi tidak terhapus permanen dari basis data.

---

## Persyaratan Sistem

| Komponen | Versi Minimum |
|---|---|
| PHP | 8.2 atau lebih tinggi |
| CodeIgniter | 4.7 |
| MySQL / MariaDB | 8.0 / 10.4 |
| Composer | 2.x |
| Docker (opsional) | 20.x |

**Ekstensi PHP wajib diaktifkan:** `intl`, `mbstring`, `json`, `mysqlnd`, `libcurl`

---

## Panduan Instalasi & Setup

Proyek ini mendukung dua metode instalasi: **Docker Container** (direkomendasikan) dan **Lokal Manual**.

### Metode 1: Instalasi dengan Docker (Direkomendasikan)

Aplikasi ini sudah dipersiapkan penuh untuk dijalankan di dalam container Docker beserta database bawaan.

```bash
# 1. Clone repositori
git clone https://github.com/rachmatx/sparkleen-laundry.git
cd sparkleen-laundry

# 2. Salin file konfigurasi
cp .env.example .env

# 3. Jalankan Docker Compose
docker compose up -d --build

# 4. Jalankan migrasi & seeder database
docker compose exec app php spark migrate
docker compose exec app php spark db:seed KaryawanSeeder
```

> Aplikasi otomatis dapat diakses di `http://localhost:8080`

---

### Metode 2: Instalasi Lokal (Manual)

```bash
# 1. Clone repositori
git clone https://github.com/rachmatx/sparkleen-laundry.git
cd sparkleen-laundry

# 2. Salin file konfigurasi
cp .env.example .env
```

3. **Konfigurasi Database & Base URL** — Sesuaikan file `.env` Anda:
   ```env
   app.baseURL = 'http://localhost/sparkleen-laundry/public/'

   database.default.hostname = localhost
   database.default.database = nama_database_laundry
   database.default.username = root
   database.default.password = password_anda
   ```

```bash
# 4. Install dependensi Composer
composer install

# 5. Buat database & jalankan migrasi
php spark db:create nama_database_laundry
php spark migrate

# 6. Jalankan seeder untuk data awal & akun default
php spark db:seed KaryawanSeeder

# 7. Jalankan server lokal
php spark serve
```

> Aplikasi dapat diakses di `http://localhost:8080`

---

## Akun Default (Setelah Seeder)

Setelah menjalankan `KaryawanSeeder`, akun berikut akan tersedia secara otomatis:

| Role | Username | Password | Akses |
|---|---|---|---|
| **Pemilik / Owner** | `pemilik` | `password123` | Dashboard owner, laporan, gaji |
| **Admin** | `admin` | `admin123` | Manajemen penuh sistem |
| **Kasir** | `kasir1` | `kasir123` | Kasir, transaksi, lacak order |

> [!CAUTION]
> **Segera ganti password default ini** setelah pertama kali login, terutama pada lingkungan produksi.

---

## Konfigurasi Payment API & Smart Pay

Aplikasi Laundry ini merupakan ujung tombak dari ekosistem pembayaran Smart Pay. Saat Anda mempublikasikan aplikasi ini ke produksi, Anda **wajib** menyambungkannya dengan server Payment API (Node.js).

Buka file `.env` dan atur bagian berikut:

```env
# URL server Payment API (contoh URL dari Railway)
PAYMENT_API_URL="https://your-payment-api.railway.app/api/transactions"

# API Key rahasia bawaan server Node.js (harus sama dengan Node.js dan Flutter)
PAYMENT_API_KEY="YOUR_SECRET_API_KEY_HERE"
```

> [!WARNING]
> Tanpa pengaturan di atas, fitur pembayaran **Transfer** dan **QRIS** yang muncul di layar kasir tidak akan dapat diteruskan ke aplikasi mobile Smart Pay pelanggan.

---

## Konfigurasi Notifikasi WhatsApp

Sistem ini mendukung pengiriman notifikasi otomatis saat status pesanan diubah menjadi **Selesai**. Konfigurasikan ini di file `.env` lokal Anda:

```env
# 1 = aktifkan, 0 = nonaktifkan
whatsapp.enabled = true

# Pilihan provider: link | fonnte | wablas
whatsapp.provider = fonnte

# Token API resmi dari Fonnte / Wablas Anda
whatsapp.apiToken = YOUR_WHATSAPP_API_TOKEN_HERE

# URL API (kosongkan untuk menggunakan default provider)
whatsapp.apiUrl = https://api.fonnte.com/send

# Nama Laundry Anda yang akan muncul di teks pesan
whatsapp.businessName = "Sparkleen Laundry"
```

**Provider yang didukung:**

| Provider | Mode | Keterangan |
|---|---|---|
| `link` | Manual | Menggunakan tautan `wa.me` — gratis, tanpa API |
| `fonnte` | Otomatis | Integrasi [Fonnte API](https://fonnte.com) — berbayar |
| `wablas` | Otomatis | Integrasi [Wablas API](https://wablas.com) — berbayar |

---

## Keamanan Repositori

Untuk menjaga keamanan informasi rahasia proyek:

- File `.env` **wajib diabaikan** dari Git (sudah dikonfigurasi di `.gitignore`).
- **Jangan pernah** menulis kredensial database asli, password, atau Token API WhatsApp di file selain `.env` lokal Anda.
- Jika ada penambahan variabel konfigurasi baru, daftarkan variabel baru tersebut ke file `.env.example` dengan nilai kosong/placeholder sebagai panduan bagi developer lain.
- Fitur **Login Throttle** sudah aktif secara bawaan untuk mencegah serangan brute-force.

---

## Panduan Deployment

Aplikasi ini sudah dioptimalkan untuk deployment di berbagai platform Cloud dan VPS menggunakan arsitektur Docker:

| Platform | Panduan |
|---|---|
| **Railway (CI/CD otomatis)** | Lihat [`RAILWAY_DEPLOY.md`](RAILWAY_DEPLOY.md) |
| **VPS (Docker + Nginx + SSL)** | Lihat [`DEPLOYMENT.md`](DEPLOYMENT.md) |

---

## Changelog & Pembaruan

### v1.3.0 — Smart Pay & UI Overhaul *(Juli 2026)*

1. **Integrasi Smart Pay** — Sistem kasir kini mendukung pembuatan sesi pembayaran (Payment Session) ke perantara API Node.js secara dinamis.
2. **Metadata Transaksi** — Penggunaan kolom `payment_detail` bertipe TEXT pada MySQL untuk melacak referensi pelanggan tanpa merombak skema yang kaku.
3. **Penyempurnaan UI & Notifikasi (SweetAlert2)** — Menghilangkan notifikasi bawaan browser (`alert()` & `confirm()`) pada fitur keranjang belanja dan pembatalan API.
4. **Live Search (AJAX)** — Pencarian pelanggan, transaksi, layanan, dan inventaris secara real-time tanpa perlu reload halaman.
5. **Soft Delete Transaksi** — Memastikan seluruh data riwayat transaksi tidak terhapus permanen dari basis data.
6. **Auto-Tracking WhatsApp** — Memperbaiki tautan pelacakan status pesanan publik pada notifikasi otomatis WhatsApp agar halaman Lacak Order langsung menampilkan detail pesanan seketika setelah diklik.

---

<div align="center">

Dibuat menggunakan [CodeIgniter 4](https://codeigniter.com) &nbsp;·&nbsp; [MIT License](LICENSE)

</div>


## API pelanggan Sparkleen App

Branch fondasi API menambahkan 11 endpoint `/api/v1` untuk autentikasi pelanggan,
profil, katalog, outlet, transaksi milik sendiri, dan nota digital. Kontrak,
migrasi additive, konfigurasi CORS/outlet, serta cara uji terisolasi tersedia di
[Customer API foundation](docs/api/customer-api-foundation.md).

Jemput dan pembayaran pelanggan belum termasuk endpoint yang diimplementasikan.
Gateway online yang gagal tidak lagi otomatis dicatat sebagai pembayaran lokal.
