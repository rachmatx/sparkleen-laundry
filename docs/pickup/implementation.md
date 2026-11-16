# Permintaan jemput dan antrean kasir

10 November 2026. Kandidat kedua repo: `feat/pickup-requests`.
Backend berangkat dari `c248216`; App dari `9d4ad51`.

## Alur yang diterapkan

| Status | Pelanggan | Kasir/admin |
| --- | --- | --- |
| menunggu | Melihat jadwal, membatalkan | Memproses jemput atau menolak dengan alasan |
| diproses | Melihat bahwa jemput sedang ditangani | Menolak, atau menerima cucian dengan berat aktual |
| dikonfirmasi | Membuka transaksi/nota yang terhubung | Membuka transaksi yang sudah dibuat |
| ditolak | Melihat alasan; dapat mengajukan lagi | Riwayat |
| dibatalkan | Dapat mengajukan lagi | Riwayat |

Pemilik dapat membaca antrean, tetapi aksi mutasi hanya kasir/admin aktif. Endpoint
web tetap dilindungi sesi, role dan CSRF. Bearer pelanggan tidak memberi akses kasir.
Tidak ada transaksi, perolehan/penukaran poin, pemakaian bahan atau pembayaran saat
pelanggan mengajukan jemput. Antrean diurutkan berdasarkan jadwal terdekat.

Saat kasir mengonfirmasi cucian diterima, service transaksi existing dipakai untuk
membuat satu transaksi/nota, memakai berat aktual dan harga layanan **saat penerimaan**.
Harga permintaan adalah snapshot perkiraan. Poin diperoleh menurut aturan POS yang
sudah ada; poin tidak ditebus otomatis. Stok tidak dikurangi saat penjemputan atau
penerimaan: alur ini tidak mencatat bahan yang belum digunakan. Pencatatan pemakaian
bahan tetap memerlukan alur inventaris/POS operasional yang sesuai.

## API pelanggan

Semua relatif ke `/api/v1`, memerlukan Bearer:

| Method | Path | Fungsi |
| --- | --- | --- |
| POST | /permintaan | Buat permintaan; hanya field draft yang diizinkan |
| GET | /permintaan | List milik sendiri, pagination dan filter |
| GET | /permintaan/{id} | Detail milik sendiri; ID lain menghasilkan 404 |
| POST | /permintaan/{id}/batal | Batalkan saat menunggu; replay pembatalan mengembalikan hasil yang sama |

Layanan harus aktif. Berat JSON number 0,1–100 kg dibulatkan ke dua desimal; alamat
10–500 karakter; catatan maksimal 255 karakter. Tanggal sampai 30 hari ke depan dan
slot harus belum lewat dalam WIB: pagi 08.00, siang 12.00, sore 15.00. Slot adalah
perkiraan yang perlu dipastikan outlet, bukan janji SLA otomatis.

`aktif` = menunggu/diproses; `selesai` = dikonfirmasi/ditolak/dibatalkan. Data publik
mempertahankan status diproses supaya pelanggan melihat tindakan kasir. Daftar
dokumen lama yang menormalkannya ke menunggu sudah diperbarui. Filter/pagination
invalid ditolak 422, bukan diam-diam diabaikan.

Harga, customer ID, status, employee ID dan transaction ID dari body pelanggan
bukan sumber otoritas. Harga dihitung server; identitas dari token. Pasangan harga/
layanan tersimpan sebagai estimasi, tidak dipakai sebagai pembayaran otomatis.

## Antrean web

Menu **Antrean Jemput** → `/penjemputan`. Filter aktif, per status atau semua,
20 baris per halaman. Alamat, kontak dan catatan tampil untuk kebutuhan jemput;
teks pelanggan di-escape pada HTML. Mutasi memakai POST + CSRF:

- `/penjemputan/{id}/proses`
- `/penjemputan/{id}/tolak` dengan `alasan_penolakan`
- `/penjemputan/{id}/konfirmasi` dengan `berat_aktual`

Konfirmasi hanya setelah diproses, layanan/pelanggan masih aktif dan berat valid.
Pengiriman ulang konfirmasi mengembalikan transaksi yang sudah dibuat, tanpa
menambah transaksi atau poin. Pelanggan tidak bisa membatalkan sesudah diproses.

## Migrasi dan konsistensi

`2026-11-10-000002_CreatePickupRequests` menambah tabel InnoDB `tb_permintaan_jemput`.
Tidak mengubah tabel transaksi existing. Unique nullable `active_customer_id` dan
CHECK status menjaga satu permintaan belum selesai per pelanggan; unique nullable
`id_transaksi` menjaga hubungan ke satu transaksi. CHECK memerlukan MySQL 8.0.16+
atau MariaDB yang mendukung constraint tersebut; verifikasi versi operasional.

Create, cancel dan tindakan kasir mengunci customer kemudian request. Konversi ke
transaksi dan log tindakan berada dalam satu transaksi DB, termasuk service POS
bersarang. Kegagalan setelah transaksi/poin dibuat membatalkan semuanya.

Sebelum deploy: backup/restore salinan database, jalankan `php spark migrate --all`,
lalu deploy backend dan app yang cocok. Table transaksi/payment/intent sebelumnya
serta fitur webhook tetap diperlukan sesuai migrasi yang sudah ada. Jangan menjalankan
seed demo pada database operasional. Rollback migrasi jemput menghapus riwayat jemput;
transaksi Laundry yang telah dibuat tidak dihapus oleh down migration.

## Verifikasi

- Suite penuh backend: **130 test, 592 assertions, tanpa failure/error**.
- 11 test jemput mencakup autentikasi/isolasi A-B, estimasi server, spoofed fields,
  validasi tanggal/slot/berat, duplikat, pembatalan, role+CSRF, escape HTML, lifecycle,
  berat/harga aktual, konfirmasi berulang, rollback sale/poin, serta dua proses
  create/confirm bersamaan pada koneksi MariaDB terpisah.
- Flutter: 94 test lulus, termasuk 10 test baru untuk form, konflik server,
  cancel dengan dialog, status diproses/ditolak, sesi berubah saat pagination,
  serta layout 320 px pada dua tema dan ukuran teks 1,0/1,8.
- Analyze Flutter tanpa masalah. Uji HTTP repository Dart → CodeIgniter → MariaDB
  lulus, termasuk service kasir nyata, detail/nota, cancel dan isolasi akun kedua.
- Build web dan batas penerapan dicatat pada dokumen App `docs/pickup-requests.md`.

Jalankan ulang backend dengan database disposable lokal `laundry_db_test`:

```sh
php vendor/bin/phpunit --filter PickupRequestsTest
php vendor/bin/phpunit
```

Uji HTTP opt-in pada repo App: siapkan `tools/mobile_demo.php reset`, jalankan server
PHP testing loopback, set `LAUNDRY_TEST_ROOT`/`TEST_PHP_BIN`, kemudian jalankan
`flutter test tool/pickup_integration_test.dart --dart-define=API_BASE_URL=http://127.0.0.1:42525`.
Helper `tools/pickup_demo.php` hanya memajukan fixture customer 1 pada DB test lokal;
tidak memanggil WhatsApp atau gateway pembayaran.

## Batas tahap ini

Tidak ada push/deploy, migrasi operasional, pengujian Android fisik, tracking kurir,
penetapan zona/biaya jemput otomatis, push notification atau fitur bayar pelanggan.
Outlet mengatur ketersediaan/penjemputan secara manual melalui antrean. Dibutuhkan
validasi alur kerja outlet terhadap salinan data dan perangkat sebelum rilis.
