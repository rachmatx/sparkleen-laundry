# Tahap 4 — kotak masuk dan penukaran poin

Implementasi lokal pada `feat/notifications-loyalty`, 10 November 2026, melanjutkan
commit pembayaran pelanggan c184592. Belum push, deploy, atau migrasi DB operasional.

## Perilaku

Kotak masuk pelanggan menyimpan event pesanan baru/perubahan status, status jemput,
pembayaran kas/online, dan penukaran poin. Event ditulis pada transaksi database yang
sama dengan perubahan bisnis. Event yang diputar ulang tidak menggandakan pesan;
ledger pembayaran tetap menjadi identitas notifikasi pembayaran. Isi pesan tidak
memuat PIN pengambilan, nomor telepon, alamat, atau instrumen pembayaran.

Notifikasi ini adalah **in-app inbox**, bukan push sistem saat aplikasi tertutup.
Tidak ada integrasi/config Firebase/FCM atau pengiriman perangkat pada tahap ini.
WhatsApp existing tetap terpisah; pengiriman terjadi setelah status/inbox commit.
Tidak ada callback jaringan baru yang menahan transaksi database.

## Penukaran

Aturan existing: 1 poin = Rp300, perolehan floor(grand_total / Rp10.000) diberikan
ketika transaksi dibuat. App dapat menukar poin sekali per pesanan yang belum dibayar,
belum diambil, belum menggunakan poin, dan tanpa referensi/attempt online aktif.
Tidak ada perubahan pada invoice yang sudah memiliki pembayaran, walau masih cicilan.

Preview memuat saldo, batas maksimum, total dan poin yang telah diberikan pesanan.
Body commit hanya `points`, `expected_grand_total`, `request_id`; nilai diskon dan
saldo akhir dihitung server. Penurunan grand total dapat menurunkan perolehan poin
pesanan. Contoh: total Rp35.000, earned 3, saldo 100, tukar 20 → diskon Rp6.000,
grand Rp29.000, earned 2, saldo akhir 79 (20 ditukar + 1 penyesuaian earned).
Kedua mutasi terlihat terpisah di riwayat dan preview/konfirmasi app.

Lock order → ledger/intent → pelanggan mencegah benturan dengan pembayaran dan saldo
yang dibelanjakan pada dua pesanan. Transaksi baru kasir/penerimaan jemput juga memakai
saldo pelanggan terkini dengan FOR UPDATE, bukan snapshot lama dari form. Unique
order serta customer/request key mencegah pengulangan penukaran. Jika diskon menutup
seluruh biaya, status lunas karena potongan, tanpa membuat pembayaran uang fiktif.
Belum ada pembatalan penukaran/refund dari aplikasi.

Saldo historis tetap; riwayat hanya merekam mutasi sejak fitur ini diaktifkan, tanpa
mengarang peristiwa lampau. Penukaran oleh kasir dan earned order baru ikut dicatat.

## API pelanggan

Semua route di bawah `/api/v1` memakai bearer dan ownership dari token.

| Method | Path | Body/hasil |
|---|---|---|
| GET | `/notifikasi` | page/per_page; list + meta unread_count/latest_id |
| GET | `/notifikasi/ringkasan` | unread_count/latest_id |
| POST | `/notifikasi/{id}/baca` | idempotent, hanya milik token |
| POST | `/notifikasi/baca-semua` | `{"through_id":123}`; pesan baru di atas cutoff tetap unread |
| GET | `/loyalitas` | saldo, nilai poin, history + meta pagination |
| GET | `/transaksi/{id}/poin` | preview/batas dan alasan bila tidak tersedia |
| POST | `/transaksi/{id}/poin` | `{"points":20,"expected_grand_total":35000,"request_id":"client-random-id-16-to-64"}` |

Error 401/403 sesi; 404 milik akun lain; 422 input; 409 POINTS_UNAVAILABLE,
ORDER_CHANGED atau IDEMPOTENCY_CONFLICT. Tidak menerima ID pelanggan, diskon, saldo,
status lunas atau nominal pembayaran dari client.

## Migrasi dan penerapan

Backup DB dahulu lalu jalankan migrasi `2026-11-10-000004_AddCustomerNotificationsAndLoyalty`.
Tabel baru: tb_customer_notification, tb_point_redemption, tb_point_movement. Deploy
backend sebelum app yang menampilkan fitur. Tidak perlu key tambahan untuk inbox.
Rollback yang menghapus audit poin ditolak jika ada mutasi/penukaran; pakai backup
yang konsisten dengan invoice dan saldo. Pengecualian hanya fixture localhost
laundry_db_test pada environment testing untuk reset test.

Jalankan `php vendor/bin/phpunit`. Test mencakup saldo, snapshot basi, koreksi earned,
isolasi akun, idempotensi, rollback event failure, pembayaran vs redeem, dua order
merebut saldo, dan request identik dengan koneksi MariaDB terpisah. Tool fixture
`tools/engagement_demo.php` hanya menerima database test lokal yang disposable.

## Hasil verifikasi

- Full suite: 160 tests / 786 assertions lulus (17 test engagement baru).
- Paired Flutter: 124 tests lulus (17 baru), analyzer bersih, build web berhasil.
- Integrasi repository Dart → HTTP PHP → MariaDB lulus: diskon/earned/saldo dan nota,
  payment kasir mengikuti grand total baru, event jemput/earned, inbox/read cutoff,
  pengulangan tidak menduplikasi, dan isolasi pelanggan A/B.
- Tidak ada pengiriman WhatsApp nyata, Firebase, gateway uang nyata, atau deployment.
