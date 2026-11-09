# Validasi dan deduplikasi webhook pembayaran

10 November 2026. Kandidat Laundry dan Payment API:
`feat/payment-webhook-integrity`. Tahap ini menangani penerimaan callback dan
pencatatan pembayaran, bukan verifikasi uang nyata oleh provider.

## Perubahan perilaku

Webhook lama hanya mencocokkan `payment_ref`, mengubah transaksi menjadi lunas,
lalu mengisi seluruh sisa tagihan. Sekarang nominal callback harus sama dengan
intent yang disimpan saat Laundry membuat tagihan pada gateway. Pembayaran parsial
hanya mencatat nominal intent dan memakai status cicil bila masih ada sisa.

Migration `2026-11-10-000001_CreatePaymentIntents` menambah `tb_payment_intent`:
referensi gateway unik, merchant, nota, transaksi Laundry, nominal, metode, status
PENDING/APPLIED, dan hubungan unik ke pembayaran hasil penerapan. Tidak ada backfill
otomatis yang menebak nominal tagihan lama dari sisa tagihan terkini.

Pembuatan tagihan memeriksa `merchant_id`, `customer_ref`, amount integer yang cocok,
method serta status PENDING pada respons Payment API. Intent dan referensi yang
ditampilkan kasir disimpan pada transaksi DB yang sama. Payment API perlu versi
baru yang mengembalikan merchant/customer_ref; respons versi lama ditolak.

## Kontrak callback

`POST /webhook/payment`, `Content-Type: application/json`, header `X-Signature`
berisi HMAC SHA-256 heksadesimal atas **byte body asli**, menggunakan `PAYMENT_WEBHOOK_SECRET` server Laundry yang cocok dengan
`webhook_secret` merchant di Payment API. Secret ini harus berbeda dari API key
merchant dan tidak boleh dimasukkan ke Smart Pay/Flutter. Key kosong/sama dengan
API key merchant ditolak; tidak ada fallback ke `PAYMENT_API_KEY`. Signature, expected
HMAC, payload lengkap, dan rahasia tidak ditulis ke log.

```json
{
  "event": "payment.success",
  "transaction_id": "gateway-transaction-id",
  "merchant_id": "merchant-id",
  "customer_ref": "LAU/20261110/0001",
  "status": "SUCCESS",
  "amount": 35000,
  "method": "QRIS",
  "paid_at": "2026-11-10T00:00:00.000Z"
}
```

`payment_detail` opsional, tidak digunakan sebagai bukti nominal atau metode.
Method hanya QRIS/BANK_TRANSFER. Amount harus JSON integer positif, tidak menerima
string/bool/pecahan. Laundry membatasi integer rupiah sampai 99.999.999 sesuai
kolom nominal DECIMAL(10,2) yang sudah ada. `paid_at` wajib ISO-8601 valid dengan zona.

| Kondisi | Respons | Mutasi pembayaran |
| --- | --- | --- |
| Signature hilang/palsu/body berubah | 401 INVALID_SIGNATURE | Tidak ada |
| Key server belum diatur | 503 WEBHOOK_NOT_CONFIGURED | Tidak ada |
| JSON rusak / payload tidak sesuai | 400 / 422 | Tidak ada |
| Referensi tanpa intent / transaksi terhapus | 404 INTENT_NOT_FOUND | Tidak ada |
| Merchant/nota/nominal/metode tidak cocok | 409 INTENT_MISMATCH | Tidak ada |
| Intent dibatalkan/diganti atau balance berubah | 409 STALE_INTENT / BALANCE_CONFLICT | Tidak ada; perlu rekonsiliasi |
| Event lain yang signature-nya valid | 200 ignored | Tidak ada |
| Callback valid pertama | 200 success, duplicate=false | Satu pembayaran sesuai nominal |
| Callback valid yang sudah diterapkan | 200 success, duplicate=true | Tidak menambah pembayaran |
| Kegagalan DB saat penerapan | 500 SERVER_ERROR | Seluruh mutasi di-rollback |

## Konkurensi dan batas transaksi

Kasir (tunai maupun membuat tagihan) dan webhook mengambil lock baris transaksi
Laundry lebih dahulu. Webhook kemudian mengunci intent dan membaca ledger dengan
locking read. Pembayaran, status cicil/lunas dan penanda APPLIED di-commit bersama.
Unique gateway_ref/id_pembayaran menjadi batas tambahan pada database. Tabel
transaksi, pembayaran dan intent harus memakai engine transaksional InnoDB;
verifikasi engine ini pada salinan database operasional sebelum penerapan.

Dua proses callback bersamaan diuji terhadap koneksi MariaDB terpisah: satu
menerapkan, satu mendapat duplicate. Balapan callback dengan pembayaran tunai juga
diuji: hanya satu pembayaran penuh yang tercatat. Kegagalan yang dipaksakan setelah
insert ledger membatalkan ledger, status order dan status intent; retry dapat berhasil.

Pembuatan tagihan memegang lock order selama panggilan HTTP gateway (timeout 5 detik).
Ini mencegah saldo kasir berubah di tengah pembuatan snapshot, dengan konsekuensi
request bersamaan pada order yang sama menunggu. Alur outbox/asinkron belum ditambahkan.

## Hasil verifikasi lokal

| Pemeriksaan | Hasil |
| --- | --- |
| Test webhook Laundry | 13 test, 92 assertions lulus |
| Suite penuh Laundry | 119 test, 496 assertions, tanpa failure/error |
| Test Payment API | 8 test lulus, termasuk migrasi SQLite in-memory |
| Node → HTTP PHP → MariaDB | Lulus; body palsu/nominal salah ditolak, replay tetap satu pembayaran |
| Sintaks PHP/JavaScript dan whitespace | Lulus |

Runtime: PHP 8.4.24, MariaDB 11.8.6, Node 24.22.1. Suite penuh terakhir berjalan
71,144 detik (waktu PHPUnit). Kunci fixture bukan rahasia operasional. Database
SQLite existing Payment API, server operasional dan WhatsApp tidak digunakan.
Docker production dan adapter sqlite3/Express penuh belum dijalankan pada tahap ini.

## Pengujian

```sh
php vendor/bin/phpunit --filter PaymentWebhookTest
php vendor/bin/phpunit
```

PHPUnit hanya boleh memakai database disposable lokal `laundry_db_test`. Test worker
konkurensi memakai PHP CLI dengan konfigurasi PHP yang sama; tidak memakai proses
server pembayaran operasional. Seeder fixture kini membersihkan intent sebelum tabel
pembayaran/transaksi agar antar-test tidak mewarisi referensi lama.

Uji HTTP lintas bahasa terdapat di repo Payment API:
`tests/laundry-webhook.integration.js`. Test ini memakai helper pengirim callback Node,
HTTP CodeIgniter dan MariaDB sebenarnya; transport test memakai fetch bawaan Node.
Wrapper Axios production belum diuji terhadap server provider. Set `LAUNDRY_TEST_ROOT`,
`TEST_PHP_BIN`, dan URL test loopback; server PHP testing harus memakai
`PAYMENT_WEBHOOK_SECRET=webhook-http-test-key`. Fixture CLI `tools/payment_webhook_demo.php`
hanya menerima database test lokal dan data fiktif. Uji ini tidak membuka SQLite
Payment API, membuat tagihan operasional, atau mengirim WhatsApp.

## Penerapan dan pekerjaan berikutnya

1. Jeda pembuatan/pelunasan tagihan online saat pembaruan kedua backend. Identifikasi
   tagihan gateway lama yang masih pending dan rekonsiliasi berdasarkan data gateway
   yang otoritatif; jangan membuat intent lama dari dugaan saldo sekarang.
2. Verifikasi backup/restore pada salinan data operasional, jalankan migrasi baru,
   dan deploy versi Laundry serta Payment API yang cocok. Atur secret webhook yang
   sama secara privat di kedua server, berbeda dari key merchant. Untuk merchant
   default M1, env `PAYMENT_WEBHOOK_SECRET` pada Node mengisi kolom `webhook_secret`;
   merchant lain diatur melalui provisioning server yang berwenang. Kolom SQLite
   baru ditambah nullable dan tidak menebak secret lama. Create/pay/confirm dengan
   callback aktif tetapi secret belum siap ditolak 503. Ikuti runbook rilis existing.
3. Uji satu tagihan baru di staging, callback pertama, callback berulang dan partial.
   Baru buka kembali alur setelah hasil Ledger/gateway cocok. Penerapan belum dilakukan.
4. Pertahankan catatan intent yang sudah APPLIED; menghapusnya menghilangkan riwayat
   deduplikasi. Jangan rollback/drop tabel ini saat pembayaran baru terus masuk.

Masih terpisah: penyimpanan dan retry callback, penanganan callback yang tiba sebelum
intent tersimpan, rekonsiliasi saat tagihan diganti/dibatalkan atau dibayar di jalur
lain, pembatasan akses merchant/pelanggan, serta penggantian
verifikasi/saldo simulasi Smart Pay. Tagihan yang sukses di simulator belum menjadi
bukti perpindahan uang nyata. Tidak ada perubahan UI pembayaran pelanggan atau push/deploy.
