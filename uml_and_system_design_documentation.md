# UML & System Design Documentation: Sparkleen Laundry App

Dokumentasi ini menyajikan rancangan arsitektur, aliran logika, dan struktur data sistem **Sparkleen Laundry App** menggunakan standar Unified Modeling Language (UML) dan representasi visual pendukung lainnya.

---

## 1. Use Case Diagram

Diagram Use Case mendefinisikan batas sistem, fungsionalitas utama, dan hak akses dari masing-masing aktor (Kasir, Admin, Pemilik, dan Pelanggan Publik).

```mermaid
graph TD
    subgraph Aktor
        Pelanggan((Pelanggan Publik))
        Kasir((Kasir))
        Admin((Admin))
        Pemilik((Pemilik))
    end

    subgraph "Sistem Sparkleen Laundry (Use Cases)"
        UC_Login([Melakukan Login])
        UC_Profil([Kelola Profil & Ganti Password])
        UC_Transaksi([Kelola Transaksi & Detail])
        UC_LiveSearch([Pencarian Data Live / Filter])
        UC_CariPelanggan([Cari Data Pelanggan])
        UC_Pelanggan([Kelola Data Pelanggan])
        UC_Bayar([Kelola Pembayaran & Cicilan])
        UC_InventarisPakai([Catat Penggunaan Inventaris])
        UC_WA([Kirim Notifikasi WA])
        UC_UbahStatus([Ubah Status Laundry])
        UC_Karyawan([Kelola Data Karyawan])
        UC_Layanan([Kelola Data Layanan])
        UC_ResepLayanan([Kelola Resep Bahan Baku])
        UC_Inventaris([Kelola Stok & Inventaris])
        UC_Laporan([Lihat & Cetak Laporan Keuangan])
        UC_LogAktivitas([Lihat/Monitor Log Aktivitas])
        UC_LogWhatsapp([Lihat Log WhatsApp])
        UC_Backup([Backup Database])
        UC_LacakOrder([Lacak Status Order])
    end

    Pelanggan --> UC_LacakOrder

    Kasir --> UC_Login
    Kasir --> UC_Profil
    Kasir --> UC_Transaksi
    Kasir --> UC_UbahStatus
    Kasir --> UC_Pelanggan
    Kasir --> UC_Bayar
    Kasir --> UC_LogWhatsapp
    Kasir -.->|view only| UC_Inventaris

    Admin --> UC_Login
    Admin --> UC_Profil
    Admin --> UC_Karyawan
    Admin --> UC_Layanan
    Admin --> UC_ResepLayanan
    Admin --> UC_Inventaris
    Admin --> UC_Transaksi
    Admin --> UC_UbahStatus
    Admin --> UC_Pelanggan
    Admin --> UC_Bayar
    Admin --> UC_Laporan
    Admin --> UC_LogWhatsapp
    Admin --> UC_Backup

    Pemilik --> UC_Login
    Pemilik --> UC_Profil
    Pemilik --> UC_Karyawan
    Pemilik --> UC_Layanan
    Pemilik --> UC_Inventaris
    Pemilik --> UC_Laporan
    Pemilik --> UC_LogAktivitas
    Pemilik --> UC_LogWhatsapp
    Pemilik --> UC_Backup
    Pemilik -.->|view only| UC_Transaksi
    Pemilik -.->|view & toggle| UC_Pelanggan

    %% Hubungan Include & Extend
    UC_Transaksi -.->|include| UC_CariPelanggan
    UC_Pelanggan -.->|extend| UC_CariPelanggan
    UC_Pelanggan -.->|include| UC_LiveSearch
    UC_Karyawan -.->|include| UC_LiveSearch
    UC_Layanan -.->|include| UC_LiveSearch
    UC_Inventaris -.->|include| UC_LiveSearch
    UC_Transaksi -.->|include| UC_LiveSearch
    UC_InventarisPakai -.->|extend| UC_Transaksi
    UC_Transaksi -.->|include| UC_WA
    UC_UbahStatus -.->|include| UC_WA
    UC_Bayar -.->|include| UC_Transaksi
    UC_ResepLayanan -.->|extend| UC_Layanan
```

---

## 2. Activity Diagram

Bagian ini memodelkan aliran aktivitas bisnis utama yang berjalan di dalam sistem.

### A. Proses Transaksi Laundry
```mermaid
stateDiagram-v2
    [*] --> BukaFormTransaksi
    BukaFormTransaksi --> CariPelanggan : Cari via No HP / Nama
    
    state CariPelanggan <<choice>>
    CariPelanggan --> PilihPelanggan : Ditemukan
    CariPelanggan --> RegistrasiPelanggan : Tidak Ditemukan
    
    RegistrasiPelanggan --> PilihPelanggan : Input Data Pelanggan Baru & Simpan
    PilihPelanggan --> InputDataTransaksi : Tentukan Estimasi Selesai, Pilih Layanan & Input Berat
    InputDataTransaksi --> HitungBiaya : Sistem Hitung Total
    HitungBiaya --> PilihInventaris : Opsional - Input Sabun/Pewangi Pakai
    PilihInventaris --> SimpanTransaksi : Simpan Data Transaksi
    SimpanTransaksi --> SimpanDetail : Simpan Item Layanan & Berat
    SimpanDetail --> CekPembayaranAwal
    
    state CekPembayaranAwal <<choice>>
    CekPembayaranAwal --> SimpanPembayaranDP : Bayar DP / Lunas
    CekPembayaranAwal --> BelumBayar : Bayar Belakangan
    
    SimpanPembayaranDP --> CetakStruk
    BelumBayar --> CetakStruk
    CetakStruk --> KirimWA : Kirim WA Otomatis (Order Masuk)
    KirimWA --> [*]
```

### B. Update Status Laundry
```mermaid
stateDiagram-v2
    [*] --> BukaDaftarTransaksi
    BukaDaftarTransaksi --> PilihTransaksi : Pilih Transaksi yang Berjalan
    PilihTransaksi --> UbahStatus : Ganti Status (Proses/Setrika/Selesai/Diambil)
    UbahStatus --> CekStatusBaru
    
    state CekStatusBaru <<choice>>
    CekStatusBaru --> KirimWASelesai : Status Baru = 'Selesai' (Siap Diambil)
    CekStatusBaru --> SimpanStatus : Status Baru != 'Selesai'
    
    KirimWASelesai --> SimpanStatus : Kirim WA Otomatis
    SimpanStatus --> [*]
```

### C. Pembayaran (Pelunasan)
```mermaid
stateDiagram-v2
    [*] --> BukaMenuPembayaran
    BukaMenuPembayaran --> CariNota : Masukkan No Nota / Cari Pelanggan
    CariNota --> PilihMetodeBayar : Pilih Opsi (Cash, QRIS, Transfer Bank)
    
    state PilihMetodeBayar <<choice>>
    PilihMetodeBayar --> TampilQRIS : Pilih QRIS
    PilihMetodeBayar --> TampilTransfer : Pilih Transfer Bank
    PilihMetodeBayar --> InputBayar : Pilih Cash
    
    TampilQRIS --> InputBayar : Tampilkan Kode QR Mockup & Pelanggan Scan
    TampilTransfer --> InputBayar : Tampilkan No Rekening & Salin untuk Transfer
    
    InputBayar --> HitungKembalian : Sistem Hitung (Uang - Tagihan)
    HitungKembalian --> CekLunas
    
    state CekLunas <<choice>>
    CekLunas --> UpdateLunas : Pembayaran Lunas (Sisa = 0)
    CekLunas --> UpdateCicil : Pembayaran DP/Cicilan (Sisa > 0)
    
    UpdateLunas --> SimpanRiwayat
    UpdateCicil --> SimpanRiwayat
    SimpanRiwayat --> CetakNotaPembayaran : Cetak Nota/Struk
    CetakNotaPembayaran --> [*]
```

---

## 3. Sequence Diagram

Bagian ini memodelkan interaksi pertukaran pesan (message-passing) antarkomponen perangkat lunak saat mengeksekusi skenario sistem.

### A. Proses Transaksi Laundry
```mermaid
sequenceDiagram
    autonumber
    actor Kasir
    participant View as Form Transaksi
    participant Auth as Filters/AuthFilter
    participant CSRF as CSRF Security Guard
    participant Controller as TransaksiController
    participant ModelT as MTransaksi
    participant ModelD as MDetailTransaksi
    participant ModelI as MPenggunaanInventaris
    participant DB as MySQL Database
    participant WA as WhatsAppNotifier (Fonnte)

    Kasir->>View: Isi data transaksi & list layanan
    View->>Auth: POST /transaksi/store
    activate Auth
    
    alt Sesi Habis / Tidak Valid
        Auth-->>Kasir: Redirect ke /login (Pesan: Sesi Habis)
    else Sesi Valid
        Auth->>CSRF: Teruskan request & token
        deactivate Auth
        activate CSRF
        
        alt Token CSRF Tidak Valid / Expired
            CSRF-->>Kasir: HTTP 403 / Error Token Kadaluarsa
        else Token CSRF Valid
            CSRF->>Controller: Loloskan ke TransaksiController
            deactivate CSRF
            activate Controller
            
            Controller->>Controller: Validasi Form & IP Throttle
            
            alt Validasi Form Gagal
                Controller-->>View: Kembalikan error via flashdata
            else Validasi Form Sukses
                Controller->>ModelT: save(dataTransaksi)
                activate ModelT
                ModelT->>DB: INSERT INTO tb_transaksi
                DB-->>ModelT: id_transaksi
                ModelT-->>Controller: id_transaksi
                deactivate ModelT

                loop Tiap Layanan Pilihan
                    Controller->>ModelD: save(id_transaksi, id_layanan, berat, harga)
                    activate ModelD
                    ModelD->>DB: INSERT INTO tb_detail_transaksi
                    DB-->>ModelD: Sukses
                    ModelD-->>Controller: Sukses
                    deactivate ModelD
                end

                opt Ada Penggunaan Inventaris
                    Controller->>ModelI: savePenggunaan(id_transaksi, id_inventaris, jumlah)
                    activate ModelI
                    ModelI->>DB: INSERT INTO tb_penggunaan_inventaris
                    DB-->>ModelI: Sukses
                    ModelI-->>Controller: Sukses
                    deactivate ModelI
                end

                Controller->>WA: sendOrderReceived(no_nota)
                activate WA
                WA->>FonnteAPI: POST https://api.fonnte.com/send
                FonnteAPI-->>WA: Response status
                WA-->>Controller: Status pengiriman WA
                deactivate WA

                Controller-->>Kasir: Redirect ke /transaksi (Cetak nota)
            end
            deactivate Controller
        end
    end
```

### B. Proses Pembayaran Cicilan
```mermaid
sequenceDiagram
    autonumber
    actor Kasir
    participant View as Form Bayar / Cicil
    participant Controller as TransaksiController
    participant ModelP as MPembayaran
    participant ModelT as MTransaksi
    participant DB as MySQL Database

    Kasir->>View: Pilih transaksi & input nominal cicilan
    View->>Controller: POST /transaksi/bayar (id_transaksi, nominal)
    activate Controller
    Controller->>Controller: Validasi nominal bayar
    
    alt Nominal tidak valid
        Controller-->>View: Kembalikan error
    else Nominal Valid
        Controller->>ModelP: savePembayaran(id_transaksi, nominal, sisa)
        activate ModelP
        ModelP->>DB: INSERT INTO tb_pembayaran
        DB-->>ModelP: Sukses
        ModelP-->>Controller: Sukses
        deactivate ModelP

        Controller->>ModelT: updateStatusPembayaran(id_transaksi, status_baru)
        activate ModelT
        ModelT->>DB: UPDATE tb_transaksi SET status_pembayaran
        DB-->>ModelT: Sukses
        ModelT-->>Controller: Sukses
        deactivate ModelT

        Controller-->>Kasir: Kembalikan kembalian & Redirect (Cetak Struk Pembayaran)
    end
    deactivate Controller
```

### C. Proses Quick View Modal Transaksi (AJAX)
```mermaid
sequenceDiagram
    autonumber
    actor Kasir
    participant View as Daftar Transaksi (Browser)
    participant Controller as TransaksiController
    participant ModelT as MTransaksi
    participant ModelD as MDetailTransaksi
    participant DB as MySQL Database

    Kasir->>View: Klik tombol 'Quick View'
    View->>Controller: GET /transaksi/ajaxDetail/{id} (AJAX)
    activate Controller
    
    Controller->>ModelT: Fetch data transaksi & pelanggan
    activate ModelT
    ModelT->>DB: SELECT Query
    DB-->>ModelT: Data
    ModelT-->>Controller: Data
    deactivate ModelT
    
    Controller->>ModelD: Fetch detail layanan
    activate ModelD
    ModelD->>DB: SELECT Query
    DB-->>ModelD: Data
    ModelD-->>Controller: Data
    deactivate ModelD
    
    Controller->>DB: Fetch riwayat pembayaran
    DB-->>Controller: Data
    
    Controller-->>View: Kembalikan respon JSON (success)
    deactivate Controller
    
    View->>View: Render HTML & Tampilkan Modal Bootstrap
```

### D. Proses Pencarian Data Live (AJAX Live Search)
```mermaid
sequenceDiagram
    autonumber
    actor User as Pengguna (Kasir/Admin/Pemilik)
    participant Input as Input Search (Browser)
    participant JS as live-search.js (Debounce 400ms)
    participant Controller as Controller (Pelanggan/Karyawan/Layanan/Inventaris/Transaksi)
    participant Model as Model Terkait
    participant DB as MySQL Database

    User->>Input: Mengetik keyword di kolom pencarian
    Input->>JS: Event 'input' terpicu
    JS->>JS: Mulai timer debounce 400ms
    
    alt User masih mengetik sebelum 400ms
        JS->>JS: Reset timer (batalkan request sebelumnya)
    else User berhenti mengetik 400ms
        JS->>JS: Timer habis, jalankan doSearch()
        JS->>Input: Ubah icon menjadi spinner loading
        JS->>Controller: GET /{modul}/ajaxSearch?q={keyword} (AJAX, X-Requested-With)
        activate Controller
        
        Controller->>Controller: Cek isAJAX() - tolak jika bukan AJAX
        Controller->>Model: Query LIKE keyword pada kolom terkait
        activate Model
        Model->>DB: SELECT ... WHERE kolom LIKE '%keyword%'
        DB-->>Model: Array hasil data
        Model-->>Controller: Array hasil data
        deactivate Model
        
        Controller-->>JS: JSON response (data, total, permission flags)
        deactivate Controller
        
        JS->>Input: Kembalikan icon search
        JS->>Input: Tampilkan info jumlah hasil (X hasil)
        JS->>JS: Render baris tabel baru via renderFn(data)
        JS->>JS: Update innerHTML tbody#liveSearchTbody
        JS->>JS: Sembunyikan pagination jika keyword aktif
        
        opt Halaman Transaksi
            JS->>JS: Panggil bindQuickView() untuk re-bind event handler
        end
    end

    alt User menghapus semua keyword
        JS->>JS: doSearch('') - tampilkan semua data
        JS->>JS: Tampilkan kembali pagination
    end
```

---

## 4. Class Diagram

Class diagram menggambarkan arsitektur berbasis objek (OOP) di sisi Controller dan Model (CodeIgniter 4) beserta hubungannya.

```mermaid
classDiagram
    class BaseController {
        #request: RequestInterface
        #response: ResponseInterface
        #logger: LoggerInterface
        +initController()
    }

    class Auth {
        -loginThrottle: LoginThrottle
        +login()
        +doLogin()
        +logout()
    }

    class Dashboard {
        -transaksiModel: MTransaksi
        -inventarisModel: MInventaris
        +index()
        +getChart7Hari()
        +getCucianJatuhTempo()
    }

    class Transaksi {
        -transaksiModel: MTransaksi
        -pelangganModel: MPelanggan
        -layananModel: MLayanan
        -detailModel: MDetailTransaksi
        -tempModel: MTempPeminjaman
        -inventarisModel: MInventaris
        -penggunaanModel: MPenggunaanInventaris
        -layananInventarisModel: MLayananInventaris
        +index()
        +addToCart()
        +removeFromCart()
        +simpanTransaksi()
        +nota()
        +daftar()
        +detail()
        +ajaxDetail()
        +ajaxSearch()
        +ubahStatus()
        +prosesUbahStatus()
        +pembayaran()
        +simpanPembayaran()
        +kirimWaApi()
    }

    class Pelanggan {
        -pelangganModel: MPelanggan
        +index()
        +create()
        +store()
        +edit()
        +update()
        +toggleStatus()
        +ajaxSearch()
        +ajaxSearchTable()
    }

    class Karyawan {
        -karyawanModel: MKaryawan
        +index()
        +create()
        +store()
        +edit()
        +update()
        +delete()
        +toggleStatus()
        +ajaxSearch()
    }

    class Layanan {
        -layananModel: MLayanan
        -inventarisModel: MInventaris
        -layananInventarisModel: MLayananInventaris
        +index()
        +create()
        +store()
        +edit()
        +update()
        +toggleStatus()
        +resep()
        +resepStore()
        +ajaxSearch()
    }

    class Inventaris {
        -inventarisModel: MInventaris
        +index()
        +create()
        +store()
        +edit()
        +update()
        +toggleStatus()
        +warning()
        +ajaxSearch()
    }

    class Laporan {
        -transaksiModel: MTransaksi
        +index()
        +harian()
        +bulanan()
        +export_harian_pdf()
        +export_bulanan_pdf()
        +export_harian_excel()
        +export_bulanan_excel()
    }

    class Profile {
        -karyawanModel: MKaryawan
        +index()
        +update()
    }

    class LogAktivitas {
        -logModel: MLogAktivitas
        +index()
    }

    class LogWhatsapp {
        -logModel: MLogWhatsapp
        +index()
    }

    class Lacak {
        -transaksiModel: MTransaksi
        -detailModel: MDetailTransaksi
        +index()
    }

    class Backup {
        -level: string
        +index()
        +download()
    }

    class AuthFilter {
        +before()
        +after()
    }

    class LoginThrottle {
        -session: Session
        +checkThrottle()
        +incrementFailure()
        +resetThrottle()
    }

    class activity_log_helper {
        +log_aktivitas()
        +detail_perubahan()
        +badge_level_karyawan()
    }

    class WhatsAppNotifier {
        +apiToken: string
        +provider: string
        +notifyOrderBaru(array transaksi, array pelanggan)
        +notifyOrderSelesai(array transaksi, array pelanggan)
        +notifyOrderDiambil(array transaksi, array pelanggan)
        +normalizePhone()
        +buildWaMeLink()
        -sendRequest()
    }

    class MTransaksi {
        +table: string = tb_transaksi
        +primaryKey: string = id_transaksi
        +allowedFields: array
        +getTransaksiWithDetail()
        +getLaporanBulanan()
    }

    class MPelanggan {
        +table: string = tb_pelanggan
        +primaryKey: string = id_pelanggan
        +allowedFields: array
        +generateKode()
    }

    class MKaryawan {
        +table: string = tb_karyawan
        +primaryKey: string = id_karyawan
        +allowedFields: array
        +verifyLogin()
    }

    class MLayanan {
        +table: string = tb_layanan
        +primaryKey: string = id_layanan
        +allowedFields: array
    }

    class MInventaris {
        +table: string = tb_inventaris
        +primaryKey: string = id_inventaris
        +allowedFields: array
        +getStokKritis()
    }

    class MLayananInventaris {
        +table: string = tb_layanan_inventaris
        +primaryKey: string = id_layanan_inventaris
        +allowedFields: array
    }

    class MLogAktivitas {
        +table: string = tb_log_aktivitas
        +primaryKey: string = id_log
        +logAksi()
    }

    class MLogWhatsapp {
        +table: string = tb_log_whatsapp
        +primaryKey: string = id_log
        +allowedFields: array
        +getDaftarLog()
    }

    BaseController <|-- Auth
    BaseController <|-- Dashboard
    BaseController <|-- Transaksi
    BaseController <|-- Pelanggan
    BaseController <|-- Karyawan
    BaseController <|-- Layanan
    BaseController <|-- Inventaris
    BaseController <|-- Laporan
    BaseController <|-- Profile
    BaseController <|-- LogAktivitas
    BaseController <|-- LogWhatsapp
    BaseController <|-- Lacak
    BaseController <|-- Backup

    Auth ..> LoginThrottle : Menggunakan
    AuthFilter ..> BaseController : Mengamankan Akses Rute
    Dashboard ..> MTransaksi : Memakai
    Dashboard ..> MInventaris : Memakai
    Transaksi ..> MTransaksi : Memakai
    Transaksi ..> MPelanggan : Memakai
    Transaksi ..> MLayanan : Memakai
    Transaksi ..> MInventaris : Memakai
    Transaksi ..> MLayananInventaris : Memakai
    Transaksi ..> WhatsAppNotifier : Menggunakan
    Pelanggan ..> MPelanggan : Memakai
    Karyawan ..> MKaryawan : Memakai
    Karyawan ..> activity_log_helper : Mencatat Aksi
    Layanan ..> MLayanan : Memakai
    Layanan ..> MInventaris : Memakai
    Layanan ..> MLayananInventaris : Memakai
    Inventaris ..> MInventaris : Memakai
    Inventaris ..> activity_log_helper : Mencatat Aksi
    Laporan ..> MTransaksi : Memakai
    Profile ..> MKaryawan : Memakai
    LogAktivitas ..> MLogAktivitas : Memakai
    LogWhatsapp ..> MLogWhatsapp : Memakai
    Lacak ..> MTransaksi : Memakai
    Backup ..> MLogAktivitas : Mencatat Log Keamanan
    activity_log_helper ..> MLogAktivitas : Menyimpan ke
```

---

## 5. Entity Relationship Diagram (ERD)

ERD menggambarkan struktur logis basis data relasional Sparkleen Laundry secara detail beserta relasi antar-entitas.

```mermaid
erDiagram
    KARYAWAN ||--o{ TRANSAKSI : "dilayani oleh"
    PELANGGAN ||--o{ TRANSAKSI : "memiliki"
    TRANSAKSI ||--|{ DETAIL_TRANSAKSI : "berisi"
    LAYANAN ||--o{ DETAIL_TRANSAKSI : "terdiri dari"
    TRANSAKSI ||--o{ PEMBAYARAN : "memiliki riwayat"
    TRANSAKSI ||--o{ STATUS_LOG : "dicatat perubahan"
    KARYAWAN ||--o{ STATUS_LOG : "diubah oleh"
    TRANSAKSI ||--o{ PENGGUNAAN_INVENTARIS : "memakai"
    INVENTARIS ||--o{ PENGGUNAAN_INVENTARIS : "digunakan di"
    KARYAWAN ||--o{ LOG_AKTIVITAS : "melakukan aksi"
    LAYANAN ||--o{ LAYANAN_INVENTARIS : "memiliki resep"
    INVENTARIS ||--o{ LAYANAN_INVENTARIS : "dipakai dalam resep"
    TRANSAKSI ||--o{ LOG_WHATSAPP : "tercatat di (via no_nota)"
    MASTER_LEVEL_KARYAWAN ||--o{ KARYAWAN : "menentukan"
    MASTER_STATUS_TRANSAKSI ||--o{ TRANSAKSI : "menentukan"
    MASTER_STATUS_PEMBAYARAN ||--o{ TRANSAKSI : "menentukan"
    MASTER_METODE_PEMBAYARAN ||--o{ TRANSAKSI : "menggunakan"
    MASTER_METODE_PEMBAYARAN ||--o{ PEMBAYARAN : "menggunakan"
    MASTER_STATUS_WHATSAPP ||--o{ LOG_WHATSAPP : "berstatus"

    KARYAWAN {
        int id_karyawan PK
        varchar nama_karyawan
        varchar username
        varchar password
        int id_level_karyawan FK
        varchar no_telp
        varchar email
        decimal gaji_pokok
        tinyint is_aktif
        datetime created_at
        datetime updated_at
    }

    PELANGGAN {
        int id_pelanggan PK
        varchar kode_pelanggan
        varchar nama_pelanggan
        varchar no_telp
        text alamat
        int poin
        int total_transaksi
        tinyint is_aktif
        datetime created_at
        datetime updated_at
    }

    LAYANAN {
        int id_layanan PK
        varchar nama_layanan
        decimal harga_per_kg
        int estimasi_hari
        tinyint is_aktif
        datetime created_at
        datetime updated_at
    }

    TRANSAKSI {
        int id_transaksi PK
        varchar no_nota
        int id_pelanggan FK
        int id_kasir FK
        datetime tgl_masuk
        datetime tgl_selesai_estimasi
        datetime tgl_selesai_aktual
        decimal total_berat
        decimal total_biaya
        decimal diskon
        decimal grand_total
        int poin_ditebus
        int poin_diperoleh
        int id_status_transaksi FK
        int id_status_pembayaran FK
        int id_metode_pembayaran FK
        varchar pin_pengambilan
        datetime created_at
        datetime updated_at
    }

    DETAIL_TRANSAKSI {
        int id_detail PK
        int id_transaksi FK
        int id_layanan FK
        decimal berat
        decimal harga_satuan
        decimal subtotal
    }

    PEMBAYARAN {
        int id_pembayaran PK
        int id_transaksi FK
        datetime tgl_bayar
        decimal nominal_bayar
        decimal sisa_tagihan
        int id_metode_pembayaran FK
        varchar bukti_foto
    }

    STATUS_LOG {
        int id_log PK
        int id_transaksi FK
        varchar status_lama
        varchar status_baru
        datetime tgl_ubah
        int id_karyawan FK
        text catatan
    }

    INVENTARIS {
        int id_inventaris PK
        varchar nama_barang
        decimal stok
        varchar satuan
        decimal harga_beli
        decimal stok_minimal
        tinyint is_aktif
        datetime created_at
        datetime updated_at
    }

    PENGGUNAAN_INVENTARIS {
        int id_penggunaan PK
        int id_transaksi FK
        int id_inventaris FK
        decimal jumlah_pakai
        text keterangan
    }

    LAYANAN_INVENTARIS {
        int id_layanan_inventaris PK
        int id_layanan FK
        int id_inventaris FK
        decimal jumlah_default
        datetime created_at
        datetime updated_at
    }

    LOG_AKTIVITAS {
        int id_log PK
        int id_karyawan FK
        varchar nama_karyawan
        varchar level_karyawan
        varchar modul
        varchar aksi
        int id_referensi
        varchar nama_referensi
        text detail
        varchar ip_address
        datetime created_at
    }

    LOG_WHATSAPP {
        int id_log PK
        varchar no_nota FK
        varchar no_tujuan
        text isi_pesan
        varchar tipe_notifikasi
        varchar provider
        varchar status
        text respon_api
        datetime created_at
    }
```

---

## 6. Logical Record Structure (LRS)

LRS menyajikan hubungan antar tabel database yang dinormalisasi dengan memperlihatkan Cardinality, Primary Key (PK), dan Foreign Key (FK).

Berikut adalah representasi visual LRS menggunakan diagram relasi fisik:

```mermaid
erDiagram
    tb_karyawan {
        int id_karyawan PK
        varchar nama_karyawan
        varchar username
        varchar password
        enum level
        varchar no_telp
        varchar email
        decimal gaji_pokok
        tinyint is_aktif
        datetime created_at
        datetime updated_at
    }

    tb_pelanggan {
        int id_pelanggan PK
        varchar kode_pelanggan
        varchar nama_pelanggan
        varchar no_telp
        text alamat
        int poin
        int total_transaksi
        tinyint is_aktif
        datetime created_at
        datetime updated_at
    }

    tb_layanan {
        int id_layanan PK
        varchar nama_layanan
        decimal harga_per_kg
        int estimasi_hari
        tinyint is_aktif
        datetime created_at
        datetime updated_at
    }

    tb_inventaris {
        int id_inventaris PK
        varchar nama_barang
        decimal stok
        varchar satuan
        decimal harga_beli
        decimal stok_minimal
        tinyint is_aktif
        datetime created_at
        datetime updated_at
    }

    tb_transaksi {
        int id_transaksi PK
        varchar no_nota
        int id_pelanggan FK
        int id_kasir FK
        datetime tgl_masuk
        datetime tgl_selesai_estimasi
        datetime tgl_selesai_aktual
        decimal total_berat
        decimal total_biaya
        decimal diskon
        decimal grand_total
        int poin_ditebus
        int poin_diperoleh
        enum status
        enum status_pembayaran
        enum metode_pembayaran
        datetime created_at
        datetime updated_at
    }

    tb_detail_transaksi {
        int id_detail PK
        int id_transaksi FK
        int id_layanan FK
        decimal berat
        decimal harga_satuan
        decimal subtotal
    }

    tb_pembayaran {
        int id_pembayaran PK
        int id_transaksi FK
        datetime tgl_bayar
        decimal nominal_bayar
        decimal sisa_tagihan
        enum metode_bayar
        varchar bukti_foto
    }

    tb_status_log {
        int id_log PK
        int id_transaksi FK
        varchar status_lama
        varchar status_baru
        datetime tgl_ubah
        int id_karyawan FK
        text catatan
    }

    tb_penggunaan_inventaris {
        int id_penggunaan PK
        int id_transaksi FK
        int id_inventaris FK
        decimal jumlah_pakai
        text keterangan
    }

    tb_layanan_inventaris {
        int id_layanan_inventaris PK
        int id_layanan FK
        int id_inventaris FK
        decimal jumlah_default
        datetime created_at
        datetime updated_at
    }

    tb_log_aktivitas {
        int id_log PK
        int id_karyawan FK
        varchar nama_karyawan
        varchar level_karyawan
        varchar modul
        varchar aksi
        int id_referensi
        varchar nama_referensi
        text detail
        varchar ip_address
        datetime created_at
    }

    tb_log_whatsapp {
        int id_log PK
        varchar no_nota FK
        varchar no_tujuan
        text isi_pesan
        varchar tipe_notifikasi
        varchar provider
        varchar status
        text respon_api
        datetime created_at
    }

    tb_karyawan ||--o{ tb_transaksi : "dilayani oleh (id_kasir)"
    tb_karyawan ||--o{ tb_log_aktivitas : "melakukan aksi (id_karyawan)"
    tb_karyawan ||--o{ tb_status_log : "diubah oleh (id_karyawan)"
    tb_pelanggan ||--o{ tb_transaksi : "memiliki (id_pelanggan)"
    tb_transaksi ||--|{ tb_detail_transaksi : "berisi (id_transaksi)"
    tb_layanan ||--o{ tb_detail_transaksi : "terdiri dari (id_layanan)"
    tb_transaksi ||--o{ tb_pembayaran : "memiliki riwayat (id_transaksi)"
    tb_transaksi ||--o{ tb_status_log : "dicatat riwayat (id_transaksi)"
    tb_transaksi ||--o{ tb_penggunaan_inventaris : "memakai (id_transaksi)"
    tb_inventaris ||--o{ tb_penggunaan_inventaris : "digunakan di (id_inventaris)"
    tb_layanan ||--o{ tb_layanan_inventaris : "memiliki resep (id_layanan)"
    tb_inventaris ||--o{ tb_layanan_inventaris : "dipakai dalam resep (id_inventaris)"
    tb_transaksi ||--o{ tb_log_whatsapp : "berelasi via no_nota"
```

Berikut adalah representasi LRS secara tekstual terstruktur:

```text
tb_karyawan (
   id_karyawan: INT (PK)
   nama_karyawan: VARCHAR(100)
   username: VARCHAR(50)
   password: VARCHAR(255)
   level: ENUM('admin', 'kasir', 'pemilik')
   no_telp: VARCHAR(15)
   email: VARCHAR(100)
   gaji_pokok: DECIMAL(10,2)
   is_aktif: TINYINT(1)
   created_at: DATETIME
   updated_at: DATETIME
)
   |
   |-- dilayani (1:N) --------------------------------+
   |-- mencatat aksi (1:N) -----+                     |
   |                            |                     |
tb_log_aktivitas (              |                     |
   id_log: INT (PK)             |                     |
   id_karyawan: INT (FK) -------+                     |
   nama_karyawan: VARCHAR(100)                        |
   level_karyawan: VARCHAR(20)                        |
   modul: VARCHAR(50)                                 |
   aksi: VARCHAR(50)                                  |
   id_referensi: INT                                  |
   nama_referensi: VARCHAR(150)                       |
   detail: TEXT                                       |
   ip_address: VARCHAR(45)                            |
   created_at: DATETIME                               |
)                                                     |
                                                      |
tb_pelanggan (                                        |
   id_pelanggan: INT (PK)                             |
   kode_pelanggan: VARCHAR(20)                        |
   nama_pelanggan: VARCHAR(100)                       |
   no_telp: VARCHAR(15)                               |
   alamat: TEXT                                       |
   poin: INT                                          |
   total_transaksi: INT                               |
   is_aktif: TINYINT(1)                               |
   created_at: DATETIME                               |
   updated_at: DATETIME                               |
)                                                     |
   |                                                  |
   |-- memiliki (1:N) --------+                       |
                              |                       |
                           tb_transaksi (             |
                              id_transaksi: INT (PK)  |
                              no_nota: VARCHAR(20)    |
                              id_pelanggan: INT (FK) -+
                              id_kasir: INT (FK) <----+
                              tgl_masuk: DATETIME
                              tgl_selesai_estimasi: DATETIME
                              tgl_selesai_aktual: DATETIME
                              total_berat: DECIMAL(10,2)
                              total_biaya: DECIMAL(10,2)
                              diskon: DECIMAL(10,2)
                              grand_total: DECIMAL(10,2)
                              poin_ditebus: INT
                              poin_diperoleh: INT
                              status: ENUM('menunggu', 'proses', 'setrika', 'selesai', 'diambil')
                              status_pembayaran: ENUM('lunas', 'cicil', 'belum')
                              metode_pembayaran: ENUM('cash', 'qris', 'transfer')
                              created_at: DATETIME
                              updated_at: DATETIME
                           )
                              |
                              |-- memiliki riwayat (1:N) ---> tb_pembayaran (
                              |                                  id_pembayaran: INT (PK)
                              |                                  id_transaksi: INT (FK)
                              |                                  tgl_bayar: DATETIME
                              |                                  nominal_bayar: DECIMAL(10,2)
                              |                                  sisa_tagihan: DECIMAL(10,2)
                              |                                  metode_bayar: ENUM('cash', 'qris', 'transfer')
                              |                               )
                              |
                              |-- dicatat riwayat (1:N) ----> tb_status_log (
                              |                                  id_log: INT (PK)
                              |                                  id_transaksi: INT (FK)
                              |                                  status_lama: VARCHAR(20)
                              |                                  status_baru: VARCHAR(20)
                              |                                  tgl_ubah: DATETIME
                              |                                  id_karyawan: INT (FK)
                              |                                  catatan: TEXT
                              |                               )
                              |
                              |-- berisi item (1:N) --------> tb_detail_transaksi (
                              |                                  id_detail: INT (PK)
                              |                                  id_transaksi: INT (FK)
                              |                                  id_layanan: INT (FK) ---+
                              |                                  berat: DECIMAL(10,2)    |
                              |                                  harga_satuan: DECIMAL(10,2)
                              |                                  subtotal: DECIMAL(10,2) |
                              |                               )                        |
                              |
                              |-- tercatat WA (1:N) --------> tb_log_whatsapp (
                                                                 id_log: INT (PK)
                                                                 no_nota: VARCHAR(20) (FK)
                                                                 no_tujuan: VARCHAR(20)
                                                                 isi_pesan: TEXT
                                                                 tipe_notifikasi: VARCHAR(50)
                                                                 provider: VARCHAR(50)
                                                                 status: VARCHAR(20)
                                                                 respon_api: TEXT
                                                                 created_at: DATETIME
                                                              )
                              |                                                        |
                              |                               tb_layanan (             |
                              |                                  id_layanan: INT (PK) <+
                              |                                  nama_layanan: VARCHAR(50)
                              |                                  harga_per_kg: DECIMAL(10,2)
                              |                                  estimasi_hari: INT(3)
                              |                                  is_aktif: TINYINT(1)
                              |                               )
                              |                                  |
                              |                                  |-- memiliki resep (1:N) --> tb_layanan_inventaris (
                              |                                                                  id_layanan_inventaris: INT (PK)
                              |                                                                  id_layanan: INT (FK)
                              |                                                                  id_inventaris: INT (FK) --+
                              |                                                                  jumlah_default: DECIMAL(10,4)
                              |                                                                  created_at: DATETIME      |
                              |                                                                  updated_at: DATETIME      |
                              |                                                               )                           |
                              |                                                                                            |
                              |-- digunakan (1:N) ----------> tb_penggunaan_inventaris (                                   |
                                                                 id_penggunaan: INT (PK)                                   |
                                                                 id_transaksi: INT (FK)                                    |
                                                                 id_inventaris: INT (FK) --+                               |
                                                                 jumlah_pakai: DECIMAL(10,2)                               |
                                                                 keterangan: TEXT          |                               |
                                                              )                            |                               |
                                                                                           |                               |
                                                              tb_inventaris (              |                               |
                                                                 id_inventaris: INT (PK) <-+<-----------------------------+
                                                                 nama_barang: VARCHAR(100)
                                                                 stok: DECIMAL(10,2)
                                                                 satuan: VARCHAR(20)
                                                                 harga_beli: DECIMAL(10,2)
                                                                 stok_minimal: DECIMAL(10,2)
                                                                 is_aktif: TINYINT(1)
                                                              )
```

---

## 7. Deployment Diagram

Deployment diagram menggambarkan bagaimana komponen perangkat lunak didistribusikan ke atas infrastruktur fisik jaringan.

```mermaid
flowchart TB
    subgraph ClientDevice ["Client Device (PC/Laptop/Smartphone)"]
        Browser["Web Browser (Chrome/Edge/Safari)"]
        subgraph StaticAssets ["Static Assets (Diunduh dari Server)"]
            AdminLTE["AdminLTE 4 + Bootstrap 5.3"]
            LiveSearchJS["live-search.js (AJAX Live Search)"]
            PasswordToggleJS["password-toggle.js"]
        end
    end

    subgraph LocalNetwork ["Local Network (LAN / WiFi)"]
        LAN["Connection Link"]
    end

    subgraph ServerMachine ["Local Server / VPS Machine"]
        subgraph WebServer ["Web Server (Apache v2.4 / Nginx)"]
            PHP["PHP Runtime 8.2+"]
            CI4["CodeIgniter 4 App (Sparkleen)"]
            subgraph Controllers ["Controllers"]
                CtrlAuth["Auth + AuthFilter"]
                CtrlTransaksi["Transaksi (ajaxDetail, ajaxSearch)"]
                CtrlPelanggan["Pelanggan (ajaxSearch, ajaxSearchTable)"]
                CtrlKaryawan["Karyawan (ajaxSearch)"]
                CtrlLayanan["Layanan (ajaxSearch)"]
                CtrlInventaris["Inventaris (ajaxSearch)"]
                CtrlLacak["Lacak (Publik, bypass Auth)"]
            end
        end
        subgraph DBServer ["Database Server"]
            MySQL[(MySQL/MariaDB Database)]
        end
    end

    subgraph CloudNetwork ["Internet / Cloud Gateway"]
        WAN["WAN Access"]
    end

    subgraph FonnteGateway ["WhatsApp Fonnte API Gateway (Cloud)"]
        WAEngine["Fonnte Message Router"]
    end

    Browser <-->|HTTP / HTTPS Port 80/443| LAN
    LAN <--> ServerMachine
    CI4 <-->|PHP Database Driver Port 3306| MySQL
    ServerMachine <-->|WAN Connection| CloudNetwork
    CloudNetwork <-->|HTTPS POST REST API JSON| FonnteGateway
    Browser -.->|AJAX XHR Request| CtrlTransaksi
    Browser -.->|AJAX XHR Request| CtrlPelanggan
    Browser -.->|AJAX XHR Request| CtrlKaryawan
    Browser -.->|AJAX XHR Request| CtrlLayanan
    Browser -.->|AJAX XHR Request| CtrlInventaris
```

---

## 8. Flowchart Sistem

Berikut adalah bagan alir logika untuk modul internal yang membutuhkan kontrol alur ketat.

### A. Proses Login
```mermaid
flowchart TD
    Start([Mulai]) --> AksesLogin[Akses Halaman /login]
    AksesLogin --> InputKredensial[Input Username/Email & Password]
    InputKredensial --> CekTerkunci{IP Diblokir?}
    
    CekTerkunci -- Ya --> TampilkanBlokir[Tampilkan Pesan 'Percobaan Gagal Terlalu Banyak. Tunggu X Menit']
    TampilkanBlokir --> EndBlocked([Selesai - Login Ditolak])
    
    CekTerkunci -- Tidak --> SubmitForm[Kirim POST /doLogin]
    SubmitForm --> VerifikasiDatabase{Kredensial Cocok & Karyawan Aktif?}
    
    VerifikasiDatabase -- Ya --> ResetThrottle[Reset Counter Gagal IP]
    ResetThrottle --> SetSession[Set Sesi & Hak Akses]
    SetSession --> CatatLogLogin[Catat Log Aktivitas Login]
    CatatLogLogin --> RedirectDashboard[Redirect ke Dashboard]
    RedirectDashboard --> EndSuccess([Selesai - Login Berhasil])
    
    VerifikasiDatabase -- Tidak --> TambahCounterGagal[Tambah Counter Gagal IP]
    TambahCounterGagal --> CekBatas{Gagal >= 5 Kali?}
    
    CekBatas -- Ya --> BlokirIP[Kunci IP selama 5 Menit]
    BlokirIP --> TampilkanSandiSalah[Tampilkan Pesan 'Kredensial Salah & IP Dikunci']
    TampilkanSandiSalah --> HalamanLogin[Kembali ke Halaman Login]
    
    CekBatas -- Tidak --> TampilkanPeringatan[Tampilkan Pesan 'Username/Password Salah']
    TampilkanPeringatan --> HalamanLogin
    HalamanLogin --> InputKredensial
```

### B. Proses CRUD Pelanggan
```mermaid
flowchart TD
    Start([Mulai]) --> AksesPelanggan[Buka Menu Data Pelanggan]
    AksesPelanggan --> TabelMuncul[Tabel Pelanggan Ditampilkan]
    TabelMuncul --> PilihAksi{Pilih Aksi}

    PilihAksi -- Cari Data --> InputKeyword[/"Ketik Keyword di Kolom Search"/]
    InputKeyword --> Debounce[JS Debounce 400ms]
    Debounce --> AjaxRequest[AJAX GET /pelanggan/ajaxSearchTable?q=keyword]
    AjaxRequest --> QueryDB[Query LIKE nama/HP/kode di Database]
    QueryDB --> RenderTabel[Render Ulang Baris Tabel Tanpa Reload]
    RenderTabel --> TampilHasil[Tampilkan Hasil & Jumlah Data]
    TampilHasil --> PilihAksi

    PilihAksi -- Tambah --> FormTambah[Klik Tambah Pelanggan]
    FormTambah --> InputData[Input Nama, No HP, Alamat]
    InputData --> ValidasiTambah{Validasi Sukses & No HP Unik?}
    ValidasiTambah -- Tidak --> TampilkanErrorTambah[Tampilkan Pesan Error]
    TampilkanErrorTambah --> InputData
    ValidasiTambah -- Ya --> SimpanTambah[INSERT INTO tb_pelanggan]
    SimpanTambah --> CatatLogTambah[Catat Log Aktivitas]
    CatatLogTambah --> SelesaiAksi

    PilihAksi -- Edit --> PilihPelanggan[Pilih Data & Klik Edit]
    PilihPelanggan --> EditForm[Form Edit Data Pelanggan]
    EditForm --> UpdateInput[Ubah Nama, No HP, Alamat]
    UpdateInput --> ValidasiEdit{Validasi Sukses & No HP Unik?}
    ValidasiEdit -- Tidak --> TampilkanErrorEdit[Tampilkan Pesan Error]
    TampilkanErrorEdit --> UpdateInput
    ValidasiEdit -- Ya --> SimpanEdit[UPDATE tb_pelanggan]
    SimpanEdit --> CatatLogEdit[Catat Log Aktivitas]
    CatatLogEdit --> SelesaiAksi

    PilihAksi -- Toggle Status --> CekRole{Apakah Pengguna Admin/Pemilik?}
    CekRole -- Tidak --> BlokAksi[Aksi Ditolak - Kasir Tidak Punya Hak Akses]
    BlokAksi --> SelesaiAksi
    CekRole -- Ya --> KlikToggle[Klik Nonaktifkan / Aktifkan]
    KlikToggle --> SimpanToggle[UPDATE tb_pelanggan SET is_aktif = status_baru]
    SimpanToggle --> CatatLogToggle[Catat Log Aktivitas]
    CatatLogToggle --> SelesaiAksi

    SelesaiAksi --> ReloadTabel[Reload Halaman & Tampilkan Toast Sukses]
    ReloadTabel --> End([Selesai])
```

### C. Alur Utama Sistem (Overview)
```mermaid
flowchart TD
    Start([Mulai]) --> InputLogin[/"Input Login Sistem (Username & Password)"/]
    InputLogin --> ValidasiLogin["Validasi Kredensial oleh Sistem"]
    ValidasiLogin --> CekRole{"Role Pengguna?"}

    %% Cabang Pemilik / Admin
    CekRole -- "Pemilik / Admin" --> DashboardAdmin["Halaman Dashboard Admin / Pemilik"]
    DashboardAdmin --> KelolaMaster["Kelola Master Data: Karyawan, Layanan, & Harga"]
    KelolaMaster --> InputBarang[/"Input Barang Masuk & Kelola Inventaris"/]
    InputBarang --> UpdateStokGudang["Sistem Update Stok Gudang Realtime"]
    UpdateStokGudang --> TampilLaporan[/"Tampil Laporan Keuangan & Transaksi"/]
    TampilLaporan --> EndAdmin([Selesai])

    %% Cabang Kasir
    CekRole -- Kasir --> DashboardKasir["Halaman Kasir: Antrean & Transaksi"]
    DashboardKasir --> InputPesanan[/"Input Transaksi Pelanggan (Pilih Layanan, Timbang Berat)"/]
    InputPesanan --> RincianPesanan["Hitung Rincian Pesanan & Estimasi Selesai"]
    RincianPesanan --> CekStok["Cek Ketersediaan Stok Bahan (Deterjen/Pewangi)"]
    CekStok --> KondisiStok{"Stok Cukup?"}
    
    KondisiStok -- Tidak --> TampilPeringatan[/"Tampil Notifikasi Stok Kritis/Habis"/]
    TampilPeringatan --> InputPesanan

    KondisiStok -- Ya --> KonfirmasiPesanan["Konfirmasi Transaksi & Total Biaya"]
    KonfirmasiPesanan --> PilihMetode[/"Pilih Metode Pembayaran (Tunai/QRIS/Transfer)"/]
    PilihMetode --> ProsesPembayaran["Sistem Verifikasi Pembayaran"]
    ProsesPembayaran --> KurangiStokOtomatis["Sistem Kurangi Stok Bahan Baku Otomatis"]
    KurangiStokOtomatis --> UpdateStatus["Update Status Cucian (Antrean/Cuci/Setrika)"]
    UpdateStatus --> CetakStruk[/"Cetak Struk/Nota untuk Pelanggan"/]
    CetakStruk --> PesananBaru{"Ada Transaksi/Aktivitas Baru?"}
    
    PesananBaru -- Ya --> DashboardKasir
    PesananBaru -- Tidak --> EndKasir([Selesai])
```

### D. Alur Transaksi Baru & Validasi Stok
```mermaid
flowchart TD
    Start([Mulai]) --> AksesFormTransaksi[Kasir Buka Form Transaksi Baru]
    AksesFormTransaksi --> InputPelanggan[Cari Pelanggan via Nama / No HP]
    InputPelanggan --> CekPelanggan{Apakah Pelanggan Terdaftar?}
    
    CekPelanggan -- Tidak --> RegistrasiPelanggan[Input Nama, No HP, Alamat Pelanggan Baru]
    RegistrasiPelanggan --> SimpanPelanggan[Simpan ke tb_pelanggan]
    SimpanPelanggan --> PilihPelanggan[Pilih Pelanggan Tersebut]
    CekPelanggan -- Ya --> PilihPelanggan
    
    PilihPelanggan --> PilihLayanan[Pilih Layanan & Input Berat]
    PilihLayanan --> HitungSubtotal[Sistem Hitung Subtotal Harga]
    HitungSubtotal --> PoinLoyalitas{Ingin Tebus Poin?}
    
    PoinLoyalitas -- Ya --> CekPoin{Poin Cukup & Poin Ditebus <= Tagihan?}
    PoinLoyalitas -- Tidak --> HitungGrandTotal[Sistem Hitung Grand Total & Poin Baru Diperoleh]
    
    CekPoin -- Tidak --> TampilkanPesanPoin[Tampilkan Peringatan Poin Tidak Valid]
    TampilkanPesanPoin --> PoinLoyalitas
    CekPoin -- Ya --> PotongDiskon[Hitung Potongan Diskon Poin & Terapkan]
    PotongDiskon --> HitungGrandTotal
    
    HitungGrandTotal --> LoadResepBahan[Sistem Load Resep Bahan Baku untuk Layanan]
    LoadResepBahan --> TampilkanPrefillInventaris[Tampilkan Prefill Penggunaan Inventaris]
    TampilkanPrefillInventaris --> ValidasiStok{Apakah Kebutuhan Stok <= Stok Tersedia?}
    
    ValidasiStok -- Tidak --> BlokirPenyimpanan[Tampilkan Pesan 'Stok Tidak Cukup' & Blokir Tombol Simpan]
    BlokirPenyimpanan --> PilihLayanan
    
    ValidasiStok -- Ya --> SimpanTransaksi[INSERT INTO tb_transaksi & tb_detail_transaksi]
    SimpanTransaksi --> PotongStokDatabase[UPDATE tb_inventaris: Potong Jumlah Stok]
    PotongStokDatabase --> CatatPenggunaanInventaris[INSERT INTO tb_penggunaan_inventaris]
    CatatPenggunaanInventaris --> CekPembayaran{Bayar DP / Lunas?}
    
    CekPembayaran -- Ya --> CatatPembayaran[INSERT INTO tb_pembayaran]
    CekPembayaran -- Tidak --> CetakStruk[Cetak Struk Transaksi Termal]
    
    CatatPembayaran --> CetakStruk
    CetakStruk --> KirimWANotif[Kirim WA Otomatis: Order Baru Diterima]
    KirimWANotif --> CatatLogAktivitas[Catat Log Aktivitas Transaksi]
    CatatLogAktivitas --> Selesai([Selesai])
```

### E. Alur Pelunasan & Pembayaran (DP/Cicil/Lunas)
```mermaid
flowchart TD
    Start([Mulai]) --> BukaMenuPembayaran[Akses Menu Transaksi / Pembayaran]
    BukaMenuPembayaran --> CariNota[Input No Nota / Cari Nama Pelanggan]
    CariNota --> AmbilSisaTagihan[Sistem Ambil Sisa Tagihan Transaksi]
    AmbilSisaTagihan --> FormPembayaran[Buka Form Bayar / Cicil]
    
    FormPembayaran --> PilihMetodeBayar{Pilih Metode Pembayaran}
    
    PilihMetodeBayar -- Cash --> InputNominalCash[Input Nominal Uang yang Diterima]
    PilihMetodeBayar -- QRIS --> TampilkanQRISMockup[Tampilkan Kode QR Mockup SVG]
    PilihMetodeBayar -- Transfer Bank --> TampilkanNoRekening[Tampilkan Rekening Bank Mandiri & Tombol Salin]
    
    TampilkanQRISMockup --> PelangganScan[Pelanggan Scan & Bayar Sukses]
    TampilkanNoRekening --> PelangganTransfer[Pelanggan Transfer & Kasir Konfirmasi]
    
    PelangganScan --> InputNominalNonCash[Set Nominal Bayar = Sisa Tagihan]
    PelangganTransfer --> InputNominalNonCash
    
    InputNominalCash --> ValidasiNominal{Nominal >= Sisa Tagihan atau Sisa > 0?}
    InputNominalNonCash --> ValidasiNominal
    
    ValidasiNominal -- Tidak --> TampilkanErrorNominal[Tampilkan Pesan 'Nominal Tidak Valid']
    TampilkanErrorNominal --> FormPembayaran
    
    ValidasiNominal -- Ya --> HitungKembalian[Hitung Sisa Tagihan Baru & Uang Kembalian]
    HitungKembalian --> SimpanPembayaran[INSERT INTO tb_pembayaran]
    
    SimpanPembayaran --> UpdateStatusBayar{Sisa Tagihan Baru = 0?}
    
    UpdateStatusBayar -- Ya --> SetLunas[UPDATE tb_transaksi: status_pembayaran = 'lunas']
    UpdateStatusBayar -- Tidak --> SetCicil[UPDATE tb_transaksi: status_pembayaran = 'cicil']
    
    SetLunas --> TampilkanToastSukses[Tampilkan Toast Sukses & Notifikasi Uang Kembalian]
    SetCicil --> TampilkanToastSukses
    
    TampilkanToastSukses --> CetakStruk[Cetak Nota Struk Pembayaran Termal]
    CetakStruk --> CatatLogBayar[Catat Log Aktivitas Pembayaran]
    CatatLogBayar --> Selesai([Selesai])
```

### F. Alur Pembuatan Laporan Keuangan
```mermaid
flowchart TD
    Start([Mulai]) --> LoginOtoritas{Apakah Level Karyawan Admin / Pemilik?}
    
    LoginOtoritas -- Tidak --> BlokirAkses[Akses Ditolak - Menu Laporan Terkunci]
    BlokirAkses --> Selesai([Selesai])
    
    LoginOtoritas -- Ya --> MasukMenuLaporan[Buka Menu Laporan Keuangan]
    MasukMenuLaporan --> FilterLaporan{Pilih Jenis Filter Laporan}
    
    FilterLaporan -- Harian --> InputTanggalHarian[Pilih Tanggal Tertentu]
    FilterLaporan -- Bulanan --> InputBulanTahun[Pilih Bulan & Tahun]
    
    InputTanggalHarian --> KlikTampilkan[Klik Tombol 'Tampilkan Laporan']
    InputBulanTahun --> KlikTampilkan
    
    KlikTampilkan --> QueryDatabase[Sistem Query Data Transaksi yang Selesai/Diambil & Status Lunas/Cicil]
    QueryDatabase --> HitungTotalPendapatan[Sistem Menghitung Total Pendapatan & Jumlah Transaksi]
    HitungTotalPendapatan --> TampilkanTabel[Tampilkan Laporan Ringkasan pada Layar]
    
    TampilkanTabel --> PilihAksiExport{Pilih Opsi Ekspor Laporan?}
    
    PilihAksiExport -- Tidak --> Selesai
    
    PilihAksiExport -- Export PDF --> GeneratePDF[Sistem Generate Dokumen PDF via Dompdf]
    PilihAksiExport -- Export Excel --> GenerateExcel[Sistem Generate Spreadsheet Excel via PhpSpreadsheet]
    
    GeneratePDF --> DownloadFile[Download File Laporan Keuangan]
    GenerateExcel --> DownloadFile
    
    DownloadFile --> Selesai
```

---

## 9. Business Process Model and Notation (BPMN)
Menggambarkan alur kerja utama bisnis dari pelanggan datang, pelayanan kasir, otomatisasi sistem, hingga pengambilan cucian menggunakan format Flowchart ber-Swimlanes yang mensimulasikan standar BPMN.

```mermaid
flowchart LR
    %% Pengaturan orientasi kiri ke kanan (Horizontal Swimlanes)

    subgraph Pelanggan [LANE: PELANGGAN]
        direction LR
        P_Start((Start)) --> P_B1([Datang Bawa Pakaian])
        P_B1 --> P_B2([Pilih Layanan & Wangi])
        P_B5([Melakukan Pembayaran]) --> P_B6([Menerima Struk & Pakaian])
        P_B6 --> P_End((End))
    end

    subgraph Kasir [LANE: KASIR]
        direction LR
        K_K1([Input Pelanggan & Timbang])
        K_K2{X}
        K_K3([Proses Pembayaran])
        K_K4([Simpan Transaksi])
        K_K5([Serahkan ke Bagian Cuci])
        K_K8([Pakaian Siap di Rak])
        K_K9([Serahkan Pakaian & Selesai])
    end

    subgraph Sistem [LANE: SISTEM & CUCI]
        direction LR
        S_K6([Proses Cuci, Kering, Setrika])
        S_K7([Ubah Status: Selesai])
        S_S1([Simpan Database & Inventaris])
        S_S2([Kirim WA: Order Baru])
        S_S3([Simpan Detail Pembayaran])
        S_S4([Kirim WA: Pakaian Selesai])
        S_S5([Log Aktivitas & WA Diambil])
    end

    %% Hubungan alur utama
    P_B2 --> K_K1
    K_K1 --> K_K2
    K_K2 -->|DP/Lunas| K_K3
    K_K2 -->|Belum Bayar| K_K4
    K_K3 --> K_K4
    K_K4 --> K_K5
    K_K5 --> S_K6
    S_K6 --> S_K7
    S_K7 --> K_K8
    K_K8 --> K_K9
    
    %% Hubungan ke sistem
    K_K4 -.-> S_S1
    S_S1 -.-> S_S2
    K_K3 -.-> S_S3
    S_K7 -.-> S_S4
    K_K9 -.-> S_S5

    %% Hubungan balik ke pelanggan
    S_S2 -.-> P_B5
    S_S4 -.-> P_B5
    S_S5 -.-> P_End

    %% Styling Mirip BPMN (Horizontal Flow & Colors)
    classDef default fill:#fff,stroke:#333,stroke-width:2px,color:#333;
    classDef startEnd fill:#fff,stroke:#000,stroke-width:3px,color:#000;
    classDef gateway fill:#fff,stroke:#333,stroke-width:2px,color:#333;
    classDef pelangganTask fill:#bfdbfe,stroke:#2563eb,stroke-width:2px,color:#000;
    classDef kasirTask fill:#bbf7d0,stroke:#16a34a,stroke-width:2px,color:#000;
    classDef sistemTask fill:#fde047,stroke:#ca8a04,stroke-width:2px,color:#000;
    classDef notifTask fill:#fff,stroke:#64748b,stroke-width:2px,stroke-dasharray: 5 5,color:#000;
    classDef laneStyle fill:#f8fafc,stroke:#cbd5e1,stroke-width:2px,color:#0f172a;

    class P_Start,P_End startEnd;
    class K_K2 gateway;
    class P_B1,P_B2,P_B5,P_B6 pelangganTask;
    class K_K1,K_K3,K_K4,K_K5,K_K8,K_K9 kasirTask;
    class S_K6,S_K7,S_S1,S_S3 sistemTask;
    class S_S2,S_S4,S_S5 notifTask;
    class Pelanggan,Kasir,Sistem laneStyle;
```

---
*Dokumentasi UML dan Desain Sistem ini disusun berdasarkan spesifikasi aktual basis data, relasi model-controller, dan fitur pengamanan terbaru dari proyek Sparkleen Laundry.*
