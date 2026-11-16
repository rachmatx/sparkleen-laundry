# Alamat pelanggan — Sparkleen App 0.0.6

Branch backend: `feat/customer-addresses-v0.0.6`. Jalankan `php spark migrate` sebelum memasang klien baru. Migration `2026-11-16-000001` mengimpor alamat profil yang sudah ada, membuat tabel alamat, dan menambahkan patokan snapshot pada permintaan jemput. Jangan jalankan seeder demo pada database operasional. Klien lama yang mengirim alamat manual tetap didukung.

Semua endpoint di bawah memakai prefix `/api/v1` dan Bearer pelanggan yang sudah ada. ID milik pelanggan lain dibalas 404. Tidak menerima `id_pelanggan` dari body sebagai pemilik.

| Metode | Endpoint | Hasil |
| --- | --- | --- |
| GET | `/alamat?page=1&per_page=20` | Daftar + meta page/last_page/total; maksimal 20 per halaman |
| GET | `/alamat/{id}` | Satu alamat |
| POST | `/alamat` | Tambah, HTTP 201 |
| PATCH | `/alamat/{id}` | Ubah field yang diberikan |
| POST | `/alamat/{id}/utama` | Jadikan alamat utama |
| DELETE | `/alamat/{id}` | Hapus, HTTP 204 |

Field: `label` string 1–40 karakter, `alamat` string 10–1000 karakter, `patokan` string opsional sampai 255 karakter, `is_default` boolean. Maksimal 50 alamat per pelanggan. DTO memakai `id_alamat`, `label`, `alamat`, `patokan`, dan `is_default`.

Alamat pertama otomatis utama. Penghapusan utama mempromosikan alamat tertua yang tersisa; menghapus terakhir mengosongkan `tb_pelanggan.alamat`. Alamat yang telah dihapus tidak dibuat ulang dari profil. Edit profil lama dan perubahan alamat oleh kasir tetap disinkronkan dengan alamat utama. Untuk pendaftaran setelah migration, alamat profil diimpor ketika fitur alamat pertama kali dipakai.

## Transaksi dan snapshot

Operasi alamat mengunci baris pelanggan (`FOR UPDATE`) dalam transaksi, urutannya sama dengan pembuatan penjemputan. Marker primary nullable dan unique mencegah dua alamat utama. Perubahan marker, alamat, dan mirror profil di-commit bersama; kegagalan mengembalikan semuanya.

Pembuatan penjemputan menerima `id_alamat` opsional. Bila ada, server memverifikasi kepemilikan dan membaca ulang alamat di bawah kunci pelanggan sebelum menyimpan `alamat_jemput` + `patokan_jemput`. Teks alamat dari klien tidak dipakai menggantikan alamat dengan ID. Snapshot tidak memiliki foreign key ke alamat tersimpan, sehingga riwayat bertahan sesudah edit/hapus. Tampilan kasir menampilkan patokan dengan escaping HTML.

## Pengujian dan uji manual

Suite penuh lulus 167 tes / 866 assertions pada MySQL terisolasi. `CustomerAddressesTest` meliputi CRUD, validasi, pagination, isolasi pemilik, primary, legacy sync, snapshot, serta kegagalan database yang membuktikan rollback. Tes HTTP dari repository Dart asli juga lulus.

Uji manual setelah deploy: tambahkan Rumah dan Kos, jadikan Kos utama, ajukan penjemputan dari Rumah, ubah/hapus Rumah, lalu pastikan alamat permintaan lama tetap Rumah di kasir. Masuk akun lain dan pastikan hanya alamat akun tersebut yang terlihat. Hapus alamat terakhir dan pastikan form mengharuskan pemilihan alamat baru.

Layanan tambahan di master kasir: **Setrika Saja**, satuan kg, aktif; harga dan estimasi ditentukan outlet. Tidak ada perubahan Smart Pay atau Payment API pada patch ini.
