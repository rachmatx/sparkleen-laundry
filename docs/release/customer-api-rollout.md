# Rilis fondasi API pelanggan Sparkleen

10 November 2026. Branch kandidat kedua repo: `feat/customer-api-foundation`.
Dokumen ini adalah panduan penerapan; deployment dan backup operasional belum dilakukan.

## Status yang terbukti

- Suite penuh tahap 6 pada `4db71f5`: 104 test, 392 assertions, tanpa failure/error.
- Rehearsal lokal memakai service backup sebenarnya: snapshot skema sebelum migrasi
  autentikasi mobile → dump SQL → ubah data → restore → jalankan migration runner.
  Isi tabel pelanggan, transaksi, detail, pembayaran, inventaris dan pemakaian tetap sama.
- Route `debug`, `debug-db`, `seed` hanya didaftarkan saat `ENVIRONMENT=development`.
  Inventaris route dengan `CI_ENVIRONMENT=production` sudah diperiksa.
- Boot standar tidak menjalankan migrasi/seeder dan tidak membuat akun default.
  `RUN_DATABASE_MIGRATIONS=1` menjalankan migrasi sebelum Apache; kegagalan menghentikan boot.

## Informasi yang diperlukan dari lingkungan operasional

Catat URL HTTPS, provider, project/service, branch+commit deployment, versi PHP/DB,
status migrasi, mekanisme backup dan akses deployment. Repo memiliki konfigurasi
Railway; itu belum membuktikan Railway adalah layanan yang sedang dipakai.
GitHub dan hosting belum terhubung dalam sesi ini. Jangan menaruh token, password,
dump database, atau berkas penandatanganan Android dalam repo/log.

## Persiapan dan backup

1. Verifikasi commit operasional dan `php spark migrate:status` pada service yang
   benar. Jangan memakai database operasional sebagai database PHPUnit.
2. Jadwalkan jeda penulisan data selama backup/penerapan; jangan jalankan DDL bersamaan
   dengan backup. Simpan backup lengkap di tempat privat dengan akses terbatas.
3. Dengan akun DB yang sudah dikonfigurasi secara privat, contoh backup MySQL:

   ```sh
   mysqldump --login-path=sparkleen-release --single-transaction --routines --triggers --events DB_NAME > backup_private.sql
   ```

   Ganti `DB_NAME` dengan database yang sudah diverifikasi. `--login-path` adalah
   konfigurasi lokal operator; jangan memasukkan password pada argumen command.
   Sesuaikan client/opsi dengan MySQL atau MariaDB yang digunakan.
4. Restore backup ke **database salinan terpisah**, lalu periksa tabel, jumlah baris,
   nominal, dan relasi. Terapkan migrasi pada salinan itu terlebih dahulu.
   Rehearsal fixture lokal bukan pengganti verifikasi salinan data operasional.

## Penerapan backend

1. Deploy kandidat ke staging terlebih dahulu. PHP dan dependency harus sesuai
   `composer.lock`; instal dengan `composer install --no-dev --optimize-autoloader`.
2. Atur `CI_ENVIRONMENT=production`, base URL HTTPS, koneksi DB, informasi outlet
   dan `MOBILE_ALLOWED_ORIGINS` hanya untuk domain web yang diperlukan.
   Flutter Android tidak membutuhkan CORS. Jangan membuka mode development di publik.
3. Sesudah backup terverifikasi, jalankan satu kali `php spark migrate --all`
   melalui sesi/pipeline release yang memiliki koneksi DB service tersebut.
   Pada Railway, CLI `railway run` saja berjalan pada komputer operator dan belum
   tentu dapat menjangkau hostname DB privat; gunakan sesi dalam service atau
   mekanisme release yang memang punya akses jaringan DB.
4. Bila memilih migrasi saat boot, set `RUN_DATABASE_MIGRATIONS=1` secara eksplisit
   untuk penerapan terkontrol, lalu kembalikan ke `0` setelah selesai. Default `0`.
   Atur flag ini pada environment container/hosting; startup shell tidak membaca `.env` CI4.
   Startup tidak lagi melakukan seed master/admin; provisioning baru dilakukan
   manual dengan kredensial owner yang dikelola aman. Jangan memakai akun contoh.
5. Periksa status migrasi dan log tanpa menyalin token/body autentikasi. Jangan
   menganggap healthcheck `/login` membuktikan seluruh API sudah bekerja.

## Smoke test staging/operasional

- `/login` tampil; `/debug`, `/debug-db`, `/seed` tidak dapat dijalankan.
- `GET /api/v1/me` tanpa Bearer memberi 401 JSON, bukan redirect HTML.
- Login akun uji memperoleh token; profil/layanan/outlet sesuai konfigurasi.
- Akun uji A membaca order/nota miliknya; akun B mendapat 404 untuk ID A.
- Logout mencabut token. PIN pengambilan tidak ada pada payload daftar/nota.
- Dengan data uji yang disepakati, perubahan status kasir terlihat setelah refresh app.
- Uji perubahan profil dengan izin pemilik akun. Jangan membuat pembayaran atau
  mengirim WhatsApp operasional hanya untuk smoke test tanpa data/alur uji yang jelas.

## Rilis aplikasi dan urutan rollback

Backend kompatibel harus tersedia sebelum app yang memakai API ini dirilis.
Ikuti `docs/release/android-verification.md` pada repo App. Release harus memakai
URL HTTPS nyata serta signing key rilis; konfigurasi saat ini masih memakai debug
signing, sehingga APK produksi belum dapat dianggap siap didistribusikan.

Jika app bermasalah, hentikan distribusi/kembalikan versi app lebih dahulu. Migrasi
backend bersifat additive, sehingga rollback kode backend biasanya tidak memerlukan
penghapusan kolom autentikasi. Jangan menjalankan rollback seluruh sejarah migrasi.
Rollback migrasi mobile menghapus PIN/hash token dan memerlukan aktivasi ulang;
restore DB juga dapat menimpa transaksi baru setelah waktu backup. Tentukan jeda
penulisan dan strategi rekonsiliasi sebelum melakukan pemulihan operasional.

## GitHub

Push kandidat ke branch `feat/customer-api-foundation`, bukan langsung mengganti
`main`. Cocokkan perilaku auto-deploy provider sebelum mengubah branch produksi.
Koneksi GitHub diperlukan untuk push; sesi ini tidak memiliki autentikasi GitHub.
