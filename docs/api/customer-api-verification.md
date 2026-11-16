# Hasil fondasi integrasi Sparkleen

10 November 2026 · verifikasi lokal pada branch `feat/customer-api-foundation` di kedua repo.

> Pembaruan terbaru tahap 6–8: suite penuh lulus, 106 test dan 404 assertions, tanpa failure/error.
> Angka 17 failure pada bagian fondasi adalah hasil historis sebelum perbaikan test.
> Hasil terbaru per tahap dan batas cakupannya ada di bagian akhir dokumen.

## Yang sudah diterapkan

Backend Laundry memperoleh 11 endpoint pelanggan `/api/v1`: daftar, aktivasi,
login, logout, profil, edit profil, layanan, outlet, daftar transaksi, detail, dan
nota. Autentikasi Bearer terpisah dari sesi kasir. PIN di-hash, DB menyimpan hash
token, token dapat dicabut/kedaluwarsa, dan login/aktivasi/registrasi dibatasi.
Normalisasi nomor mendukung pelanggan lama tanpa mengubah seluruh data lama.

Query transaksi dan nota dibatasi ke pelanggan pemilik token. Detail privat dapat
memuat PIN pengambilan; daftar dan nota tidak. Harga/poin tetap berasal dari tabel
kasir. Kontrak JSON, field integer rupiah, pagination, status, serta zona tanggal
dicocokkan dengan model Flutter. CORS memakai origin yang dikonfigurasi eksplisit.

Flutter mereset cache data pelanggan saat sesi berganti. Respons pagination lama
tidak boleh digabungkan ke sesi baru, respons 401 milik token lama tidak boleh
menghapus sesi baru, dan kegagalan 401 saat edit profil tidak boleh mengembalikan
state authenticated. Desain dan aset yang sudah terpasang dipertahankan.

Jalur kasir QRIS/transfer yang tidak dapat membuat tagihan sekarang mengembalikan
kegagalan, bukan meneruskan pencatatan pembayaran lokal. Ini perbaikan terbatas:
webhook, retry callback, otorisasi Smart Pay dan simulasi saldo masih pekerjaan
berikutnya.

## Bukti pengujian

| Pemeriksaan | Hasil |
| --- | --- |
| Baseline backend `524f554` | 86 test, 17 failure lama |
| Backend sesudah perubahan | 104 test, 17 failure; nama test yang gagal sama persis dengan baseline |
| API pelanggan baru | Seluruh 18 test lulus, tanpa error/failure baru |
| Flutter unit/widget | 84 test lulus |
| Integrasi Flutter → HTTP → CodeIgniter → MariaDB | 1 skenario penuh lulus |
| Analisis Flutter | Tanpa masalah |
| Build web release | Berhasil; memakai URL contoh saat compile, bukan deployment |
| Sintaks PHP yang diubah | Lulus `php -l` |

Runtime pengujian: PHP 8.4.24, MariaDB 11.8.6 dan dependency Composer yang dikunci
repo. Baseline memakai snapshot kode sebelum perubahan dengan konfigurasi test
lokal yang sama. Tidak menganggap angka historis dari rencana Oktober sebagai
hasil saat ini. Semua data berasal dari fixture lokal; tidak menghubungi backend
operasional, Payment API operasional, atau pengiriman WhatsApp.

Skenario live memakai **repository Dart dan Dio yang asli**, server HTTP
CodeIgniter, serta database lokal `laundry_db_test`. Yang diganti hanya penyimpanan
token perangkat, karena plugin secure storage native tidak dijalankan dalam test
host. Skenario tersebut:

1. Masuk sebagai pelanggan pertama dengan nomor alias `62...`.
2. Membaca profil, katalog, outlet, daftar, serta detail transaksi.
3. Mengubah profil dan membaca hasil yang tersimpan.
4. Menjalankan service kasir sebenarnya untuk memperbarui status dan mencatat
   pembayaran tunai pada order demo; tidak memanggil gateway atau WhatsApp.
5. Membaca ulang detail/nota dari Flutter dan memeriksa perubahan status/pembayaran,
   nominal yang sama, serta ketiadaan PIN pengambilan dalam payload nota.
6. Logout, lalu memastikan akses tanpa token ditolak.
7. Membuat akun kedua dan memastikan detail/nota akun pertama menghasilkan 404,
   sementara riwayat akun kedua kosong.

Test backend mencakup migrasi naik/turun tanpa mengubah baris transaksi,
validasi/normalisasi nomor, hash token/PIN, aktivasi dengan nota terakhir,
rate limit, token invalid/expired/revoked, akun nonaktif, pembatasan PATCH,
pagination, data terhapus/kosong, pembulatan rupiah, status tidak dikenal,
preflight CORS, serta gateway tidak dikonfigurasi/transport gagal.

## Failure backend yang sudah ada sebelum perubahan

Suite backend keseluruhan **belum hijau**. Perbandingan memakai nama setiap test,
bukan hanya jumlah failure. Tidak ada failure baru pada hasil akhir.

- `Tests.Feature.AuthFeatureTest.testDeactivatedAccountCannotLogin`
- `Tests.Feature.AuthFeatureTest.testEmptyCredentialsShowsError`
- `Tests.Feature.AuthFeatureTest.testFailedLoginShowsError`
- `Tests.Feature.AuthFeatureTest.testLoginWithEmailWorks`
- `Tests.Feature.AuthFeatureTest.testSuccessfulLoginRedirectsToDashboard`
- `Tests.Feature.RateLimitTest.testBlockedLoginRecordsSecurityLog`
- `Tests.Feature.RateLimitTest.testFailedLoginRecordsActivityLog`
- `Tests.Feature.RateLimitTest.testLoginThrottleBlocksAfterMaxAttempts`
- `Tests.Feature.RateLimitTest.testSuccessfulLoginClearsThrottle`
- `Tests.Feature.TransaksiFeatureTest.testAddToCartAndRemoveFromCart`
- `Tests.Feature.TransaksiFeatureTest.testPointCalculation`
- `Tests.Feature.TransaksiFeatureTest.testSimpanPembayaranCalculatesChange`
- `Tests.Feature.TransaksiFeatureTest.testSimpanPembayaranRecordsPayment`
- `Tests.Feature.TransaksiFeatureTest.testSimpanTransaksiCreatesFullTransaction`
- `Tests.Feature.TransaksiFeatureTest.testSimpanTransaksiRejectsEmptyCart`
- `Tests.Feature.TransaksiFeatureTest.testSimpanTransaksiRejectsNoCustomer`
- `Tests.Unit.Models.TransaksiModelTest.testAllowedFieldsAreCorrect`

Kegagalan lama meliputi ekspektasi field model, redirect/session autentikasi web,
rate limit web, dan feature test transaksi kasir. Perbaikannya dipisahkan dari
fondasi API agar perubahan ini dapat ditinjau dengan jelas. Lulusnya API baru
bukan klaim bahwa seluruh alur web kasir sudah lulus pengujian otomatis.

## Menjalankan ulang

Backend: ikuti `docs/api/customer-api-foundation.md` di repo Laundry. PHPUnit
memakai bootstrap yang menolak reset DB selain database test lokal yang bernama
`laundry_db_test`. Database tersebut harus benar-benar disposable.

Frontend, setelah fixture/backend lokal siap dan environment `LAUNDRY_TEST_ROOT`
serta `TEST_PHP_BIN` diisi:

```sh
CI=true flutter test
CI=true flutter analyze
CI=true flutter test tool/customer_api_integration_test.dart --dart-define=API_BASE_URL=http://127.0.0.1:42525
flutter build web --release --dart-define=API_BASE_URL=https://your-backend.example
```

Gunakan base URL server tanpa menambahkan `/api/v1` lagi. Untuk Android emulator,
konfigurasi sesuai host emulator; perangkat fisik memerlukan host yang dapat
diakses perangkat dan konfigurasi release HTTPS.

## Batas penerapan

- Source backend yang dipakai adalah GitHub `main` pada `524f554`; branch mobile
  lama yang disebut dokumen belum ditemukan pada remote yang tersedia. Versi
  deployment sebenarnya belum dapat dipastikan dari akses repo saja.
- Migrasi hanya diterapkan ke DB lokal pengujian. Backup/restore data operasional
  dan penerapan migrasi pada salinan data operasional belum dilakukan.
- Android fisik, secure storage native, dan kondisi jaringan perangkat belum diuji.
- Jemput, layar pembayaran pelanggan, push notification, serta penukaran poin
  dari aplikasi belum ditambahkan. Request rencana dalam koleksi Postman diberi
  penanda `[PLANNED]`.
- Ekosistem pembayaran masih memiliki simulasi verifikasi/saldo serta pekerjaan
  webhook/retry/otorisasi yang belum diselesaikan. Jalur debug/seed web lama juga
  perlu ditinjau sebelum deployment publik.
- Perubahan ini disiapkan sebagai commit lokal per repo. Belum push atau deploy.

## Tahap kecil berikutnya: test akun karyawan nonaktif

10 November 2026. `testDeactivatedAccountCannotLogin` direproduksi gagal karena
request test tidak menyertakan token CSRF dan ditolak sebelum mencapai controller.
Request test diperbaiki dengan token CSRF yang valid; konfigurasi keamanan dan
controller aplikasi tidak diubah. Test juga memastikan sesi login/id karyawan
tidak terbentuk dan percobaan akun nonaktif tercatat pada log aktivitas.

Verifikasi terarah: `php vendor/bin/phpunit --filter testDeactivatedAccountCannotLogin`
lulus **1 test, 7 assertions** pada database test lokal. Suite keseluruhan tidak
dijalankan ulang pada tahap kecil ini; angka 17 failure di atas adalah hasil
baseline/verifikasi tahap sebelumnya, bukan klaim hasil suite terbaru.

## Tahap kecil berikutnya: test login kasir berhasil

10 November 2026. `testSuccessfulLoginRedirectsToDashboard` direproduksi gagal
karena request test juga tidak mengirim token CSRF. Test kini mengirim token yang
valid dan memeriksa redirect dashboard, status login, peran kasir, username,
identitas karyawan yang benar, serta log login. Controller dan konfigurasi
keamanan aplikasi tidak diubah.

Verifikasi terarah bersama test akun nonaktif tahap sebelumnya:
`php vendor/bin/phpunit --filter 'testSuccessfulLoginRedirectsToDashboard|testDeactivatedAccountCannotLogin'`
lulus **2 test, 17 assertions** pada database test lokal. Suite keseluruhan tidak
dijalankan ulang pada tahap ini.

## Tahap 2: sisa skenario autentikasi

10 November 2026. Request password salah, form kosong, dan login email kini
menyertakan token CSRF yang valid. Pemeriksaan tambahan memastikan kegagalan tidak
membentuk sesi login, sedangkan login email memakai identitas admin yang benar.
Mock konfigurasi filter yang tidak efektif dihapus; filter CSRF tetap aktif.
`php vendor/bin/phpunit --filter AuthFeatureTest`: **9 test, 45 assertions lulus**.

## Tahap 3: pembatasan percobaan login

Request pada empat feature test rate limit kini menyertakan token CSRF baru untuk
setiap POST. Test memastikan password benar tetap ditolak selama blokir, dan
login berhasil sebelum batas blokir benar-benar memulihkan jumlah percobaan.
Pemeriksaan log kegagalan/blokir tetap berjalan melalui controller sebenarnya.
`php vendor/bin/phpunit --filter 'RateLimitTest|LoginThrottleTest'`:
**14 test, 40 assertions lulus**. Tidak mengubah batas atau implementasi throttle.

## Tahap 4: field model transaksi

Test lama mengharapkan daftar field sebelum penambahan `payment_ref` dan
`payment_detail`, padahal keduanya sudah ada pada migrasi Juni. Test pengganti
memeriksa penyimpanan metadata melalui model, menolak perubahan primary key dan
input tidak dikenal, serta memastikan nomor nota, nominal dan status pembayaran
tidak ikut berubah. Model/skema aplikasi tidak diubah untuk memenuhi test lama.
`php vendor/bin/phpunit --filter TransaksiModelTest`:
**5 test, 16 assertions lulus**.

## Tahap 5: alur kasir

Seluruh POST keranjang, transaksi dan pembayaran kini mengirim token CSRF valid.
Filter web tetap dijalankan. Notifier WhatsApp pada fixture dinonaktifkan secara
lokal dalam test agar alur ini tidak mengirim pesan keluar.

Pemeriksaan meliputi tambah/hapus keranjang, penolakan transaksi tanpa pelanggan
atau item, nota transaksi baru, potongan dan perolehan poin, stok turun tepat
0,25 dari 50 menjadi 49,75, rekaman pemakaian inventaris, pelunasan tunai, serta
kembalian Rp15.000 untuk uang Rp50.000 pada tagihan Rp35.000. Pembayaran yang
tercatat tetap Rp35.000, sisa tagihan nol, dan satu rekaman pembayaran.

Run pertama `php vendor/bin/phpunit --filter TransaksiFeatureTest` lulus
**10 test, 47 assertions**. Setelah penambahan pemeriksaan nominal/stok dan
ketiadaan transaksi pada input tidak valid, hasil gabungan dicatat berikut ini.

## Verifikasi gabungan tahap 2–5

Perintah:

```sh
php vendor/bin/phpunit --filter 'AuthFeatureTest|RateLimitTest|LoginThrottleTest|TransaksiModelTest|TransaksiFeatureTest|CustomerApiTest'
```

Hasil akhir: **56 test, 290 assertions, 0 failure, 0 error** (39,217 detik waktu
PHPUnit; di luar inisialisasi database disposable). Rincian:

| Kelompok | Test | Assertions |
| --- | ---: | ---: |
| Autentikasi web | 9 | 45 |
| Rate limit web | 4 | 24 |
| Unit LoginThrottle | 10 | 16 |
| Model transaksi | 5 | 16 |
| Alur kasir | 10 | 59 |
| API pelanggan | 18 | 130 |

Sebelum tahap 2–5, empat kelas sasaran dijalankan dan menghasilkan 28 test,
15 failure. Kelompok itu kini lulus bersama pemeriksaan unit throttle dan API.
Perubahan tahap 2–5 hanya pada test serta laporan; tidak melonggarkan CSRF,
allowlist model, atau perilaku bisnis aplikasi untuk menyesuaikan ekspektasi lama.

Suite backend keseluruhan (tahap 6), Android fisik, push, dan deployment belum
dikerjakan pada tahap ini. Hasil terarah ini tidak dianggap hasil suite penuh.

## Tahap 6: suite backend penuh

10 November 2026. Pada kode `4db71f5`, seluruh suite dijalankan satu kali secara
berurutan dengan `php vendor/bin/phpunit --log-junit <laporan-lokal.xml>` pada
MariaDB disposable lokal. Hasil: **104 test, 392 assertions, 0 failure, 0 error**,
waktu PHPUnit **55,242 detik**. Semua 17 kegagalan yang dilaporkan pada tahap
fondasi kini terselesaikan. Hasil XML diperiksa setelah proses selesai.

Ini verifikasi lokal PHP 8.4.24/MariaDB 11.8.6; belum membuktikan perilaku Android
fisik atau deployment operasional yang belum dapat diakses.

## Tahap 7–8: pemeriksaan Android dan persiapan rilis

10 November 2026. Tahap Android fisik **belum dapat dijalankan**: Android SDK,
`adb`, emulator dan perangkat fisik belum tersedia dalam lingkungan ini. Java 21
tersedia. Percobaan inventaris Flutter dihentikan/timed out tanpa hasil perangkat;
ini bukan hasil lulus uji Android. Repo App kini memuat panduan uji perangkat pada
`docs/release/android-verification.md`. Signing release masih menggunakan debug
config, dan keystore pemilik belum tersedia; APK produksi belum dibuat.

Persiapan backend yang diterapkan sebelum push/deploy:

- Route diagnostik/seed hanya terdaftar dalam development; inventaris route
  production diperiksa melalui `CI_ENVIRONMENT=production php spark routes`.
- Startup standar tidak menjalankan migrasi/seeder/default owner account.
  Flag environment container `RUN_DATABASE_MIGRATIONS=1` adalah pilihan eksplisit
  setelah backup; kegagalan migrasi menghentikan startup sebelum Apache.
- Tiga skenario shell startup lulus: boot default, migrasi eksplisit, dan fail-stop.
- Test restore memakai output `BackupService` sebenarnya, termasuk alamat dengan
  kutip, backslash, baris baru dan Unicode; migration runner kemudian menerapkan
  skema mobile. Enam kelompok tabel bisnis cocok dengan snapshot sebelum perubahan.
- Panduan backup operasional, staging, konfigurasi, smoke test dan rollback ada di
  `docs/release/customer-api-rollout.md`. Backup/restore di atas memakai fixture
  lokal; backup database operasional belum dilakukan.

Sesudah perubahan rilis, suite penuh dijalankan lagi secara berurutan:
**106 test, 404 assertions, 0 failure, 0 error**, waktu PHPUnit **56,184 detik**.
Dua test kesiapan rilis menambah 12 assertions. `bash -n` dan pemeriksaan sintaks
PHP terkait juga lulus.

GitHub tidak terautentikasi (`gh auth status`), dan dry-run push ditolak karena
kredensial tidak tersedia. Pemeriksaan remote hanya membuktikan Laundry `main`
masih menunjuk `524f554`; tidak membuktikan versi service operasional. Belum ada
push/deploy. Token GitHub lama tidak digunakan atau disimpan. Penyelesaian tahap
7–8 memerlukan perangkat/SDK Android, konfigurasi signing yang benar, koneksi
GitHub serta URL dan akses project/service hosting yang aktif.
