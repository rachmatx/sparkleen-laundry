# UML & System Design Documentation: Sparkleen Laundry App

Dokumentasi ini menyajikan rancangan arsitektur, aliran logika, dan struktur data sistem **Sparkleen Laundry App** menggunakan standar Unified Modeling Language (UML) dan representasi visual pendukung lainnya.

---

## 1. Use Case Diagram

Diagram Use Case mendefinisikan batas sistem, fungsionalitas utama, dan hak akses dari masing-masing aktor (Kasir, Admin, dan Pemilik).

```mermaid
graph TD
    subgraph Aktor
        Kasir((Kasir))
        Admin((Admin))
        Pemilik((Pemilik))
    end

    subgraph "Sistem Sparkleen Laundry (Use Cases)"
        UC_Login([Melakukan Login])
        UC_Transaksi([Kelola Transaksi & Detail])
        UC_Pelanggan([Kelola Data Pelanggan])
        UC_Bayar([Kelola Pembayaran & Cicilan])
        UC_InventarisPakai([Catat Penggunaan Inventaris])
        UC_WA([Kirim Notifikasi WA])
        UC_Karyawan([Kelola Data Karyawan])
        UC_Layanan([Kelola Data Layanan])
        UC_Inventaris([Kelola Stok & Inventaris])
        UC_Laporan([Lihat & Cetak Laporan Keuangan])
        UC_LogAktivitas([Lihat/Monitor Log Aktivitas])
    end

    Kasir --> UC_Login
    Kasir --> UC_Transaksi
    Kasir --> UC_Pelanggan
    Kasir --> UC_Bayar
    Kasir --> UC_InventarisPakai
    Kasir --> UC_WA
    Kasir -.->|View Only| UC_Inventaris

    Admin --> UC_Login
    Admin --> UC_Karyawan
    Admin --> UC_Layanan
    Admin --> UC_Inventaris
    Admin --> UC_Transaksi
    Admin --> UC_Pelanggan
    Admin --> UC_Bayar
    Admin --> UC_InventarisPakai
    Admin --> UC_Laporan

    Pemilik --> UC_Login
    Pemilik --> UC_Karyawan
    Pemilik --> UC_Layanan
    Pemilik --> UC_Inventaris
    Pemilik --> UC_Laporan
    Pemilik --> UC_LogAktivitas
    Pemilik -.->|View Only| UC_Transaksi
    Pemilik -.->|View & Toggle| UC_Pelanggan

    UC_Transaksi -.->|include| UC_WA
    UC_Bayar -.->|include| UC_Transaksi
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

    class Transaksi {
        -transaksiModel: MTransaksi
        -pelangganModel: MPelanggan
        -layananModel: MLayanan
        +index()
        +create()
        +store()
        +updateStatus()
        +bayar()
        +printNota()
    }

    class Karyawan {
        -karyawanModel: MKaryawan
        +index()
        +create()
        +store()
        +edit()
        +update()
        +delete()
    }

    class Laporan {
        -transaksiModel: MTransaksi
        +index()
        +harian()
        +bulanan()
        +exportPdf()
        +exportExcel()
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
    }

    class WhatsAppNotifier {
        +apiToken: string
        +provider: string
        +notifyOrderBaru(array transaksi, array pelanggan)
        +notifyOrderSelesai(array transaksi, array pelanggan)
        +notifyOrderDiambil(array transaksi, array pelanggan)
        -sendRequest()
    }

    class Backup {
        -level: string
        +index()
        +download()
    }

    class MTransaksi {
        +table: string = tb_transaksi
        +primaryKey: string = id_transaksi
        +allowedFields: array
        +getTransaksiWithDetail()
        +getLaporanBulanan()
    }

    class MKaryawan {
        +table: string = tb_karyawan
        +primaryKey: string = id_karyawan
        +allowedFields: array
        +verifyLogin()
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

    BaseController <|-- Auth
    BaseController <|-- Transaksi
    BaseController <|-- Karyawan
    BaseController <|-- Laporan
    BaseController <|-- Backup

    Auth ..> LoginThrottle : Menggunakan
    AuthFilter ..> BaseController : Mengamankan Akses Rute
    Transaksi ..> MTransaksi : Memakai
    Transaksi ..> MLayananInventaris : Memakai
    Transaksi ..> WhatsAppNotifier : Menggunakan
    Karyawan ..> MKaryawan : Memakai
    Karyawan ..> activity_log_helper : Mencatat Aksi
    activity_log_helper ..> MLogAktivitas : Menyimpan ke
    Laporan ..> MTransaksi : Memakai
    Backup ..> MLogAktivitas : Mencatat Log Keamanan
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

    KARYAWAN {
        int id_karyawan PK
        varchar nama_karyawan
        varchar username
        varchar password
        enum level "admin, kasir, pemilik"
        varchar no_telp
        decimal gaji_pokok
        datetime created_at
        datetime updated_at
    }

    PELANGGAN {
        int id_pelanggan PK
        varchar nama_pelanggan
        varchar no_hp
        text alamat
        int poin
        datetime created_at
        datetime updated_at
    }

    LAYANAN {
        int id_layanan PK
        varchar nama_layanan
        decimal harga_per_kg
        int estimasi_hari
        datetime created_at
        datetime updated_at
    }

    TRANSAKSI {
        int id_transaksi PK
        varchar no_nota
        int id_pelanggan FK
        int id_kasir FK
        date tgl_masuk
        date tgl_selesai_estimasi
        date tgl_selesai_aktual
        decimal total_berat
        decimal total_biaya
        decimal diskon
        decimal grand_total
        int poin_ditebus
        int poin_diperoleh
        enum status "menunggu, proses, setrika, selesai, diambil"
        enum status_pembayaran "lunas, cicil, belum"
        enum metode_pembayaran "cash, qris, transfer"
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
        date tgl_bayar
        decimal nominal_bayar
        decimal sisa_tagihan
        enum metode_bayar "cash, qris, transfer"
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
        int stok
        varchar satuan
        decimal harga_beli
        int stok_minimal
        datetime created_at
        datetime updated_at
    }

    PENGGUNAAN_INVENTARIS {
        int id_penggunaan PK
        int id_transaksi FK
        int id_inventaris FK
        int jumlah_pakai
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
        enum level_karyawan "admin, kasir, pemilik"
        enum modul "karyawan, inventaris, Keamanan, Autentikasi"
        enum aksi "tambah, ubah, hapus, aktifkan, nonaktifkan, Login, Gagal Login, Blokir Login, Backup Database"
        int id_referensi
        varchar nama_referensi
        text detail
        varchar ip_address
        datetime created_at
    }
```

---

## 6. Logical Record Structure (LRS)

LRS menyajikan hubungan antar tabel database yang dinormalisasi dengan memperlihatkan Cardinality, Primary Key (PK), dan Foreign Key (FK) secara tekstual terstruktur.

```text
tb_karyawan (
   id_karyawan: INT (PK)
   nama_karyawan: VARCHAR(100)
   username: VARCHAR(50)
   password: VARCHAR(255)
   level: ENUM('admin', 'kasir', 'pemilik')
   no_telp: VARCHAR(20)
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
   nama_referensi: VARCHAR(100)                       |
   detail: TEXT                                       |
   ip_address: VARCHAR(45)                            |
   created_at: DATETIME                               |
)                                                     |
                                                      |
tb_pelanggan (                                        |
   id_pelanggan: INT (PK)                             |
   nama_pelanggan: VARCHAR(100)                       |
   no_hp: VARCHAR(20)                                 |
   alamat: TEXT                                       |
   poin: INT                                          |
   is_aktif: TINYINT(1)                               |
   created_at: DATETIME                               |
   updated_at: DATETIME                               |
)                                                     |
   |                                                  |
   |-- memiliki (1:N) --------+                       |
                              |                       |
                           tb_transaksi (             |
                              id_transaksi: INT (PK)  |
                              no_nota: VARCHAR(50)    |
                              id_pelanggan: INT (FK) -+
                              id_kasir: INT (FK) <----+
                              tgl_masuk: DATETIME
                              tgl_selesai_estimasi: DATETIME
                              tgl_selesai_aktual: DATETIME
                              total_berat: DECIMAL(5,2)
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
                              |                                  tgl_bayar: DATE
                              |                                  nominal_bayar: DECIMAL(10,2)
                              |                                  sisa_tagihan: DECIMAL(10,2)
                              |                                  metode_bayar: ENUM('cash', 'qris', 'transfer')
                              |                               )
                              |
                              |-- dicatat riwayat (1:N) ----> tb_status_log (
                              |                                  id_log: INT (PK)
                              |                                  id_transaksi: INT (FK)
                              |                                  status_lama: VARCHAR(50)
                              |                                  status_baru: VARCHAR(50)
                              |                                  tgl_ubah: DATETIME
                              |                                  id_karyawan: INT (FK)
                              |                                  catatan: TEXT
                              |                               )
                              |
                              |-- berisi item (1:N) --------> tb_detail_transaksi (
                              |                                  id_detail: INT (PK)
                              |                                  id_transaksi: INT (FK)
                              |                                  id_layanan: INT (FK) ---+
                              |                                  berat: DECIMAL(5,2)     |
                              |                                  harga_satuan: DECIMAL   |
                              |                                  subtotal: DECIMAL       |
                              |                               )                        |
                              |                                                        |
                              |                               tb_layanan (             |
                              |                                  id_layanan: INT (PK) <+
                              |                                  nama_layanan: VARCHAR
                              |                                  harga_per_kg: DECIMAL
                              |                                  estimasi_hari: INT
                              |                                  is_aktif: TINYINT
                              |                               )
                              |                                  |
                              |                                  |-- memiliki resep (1:N) --> tb_layanan_inventaris (
                              |                                                                  id_layanan_inventaris: INT (PK)
                              |                                                                  id_layanan: INT (FK)
                              |                                                                  id_inventaris: INT (FK) --+
                              |                                                                  jumlah_default: DECIMAL   |
                              |                                                                  created_at: DATETIME      |
                              |                                                                  updated_at: DATETIME      |
                              |                                                               )                           |
                              |                                                                                            |
                              |-- digunakan (1:N) ----------> tb_penggunaan_inventaris (                                   |
                                                                 id_penggunaan: INT (PK)                                   |
                                                                 id_transaksi: INT (FK)                                    |
                                                                 id_inventaris: INT (FK) --+                               |
                                                                 jumlah_pakai: INT         |                               |
                                                                 keterangan: TEXT          |                               |
                                                              )                            |                               |
                                                                                           |                               |
                                                              tb_inventaris (              |                               |
                                                                 id_inventaris: INT (PK) <-+<-----------------------------+
                                                                 nama_barang: VARCHAR
                                                                 stok: INT
                                                                 satuan: VARCHAR
                                                                 harga_beli: DECIMAL
                                                                 is_aktif: TINYINT
                                                              )
```

---

## 7. Deployment Diagram

Deployment diagram menggambarkan bagaimana komponen perangkat lunak didistribusikan ke atas infrastruktur fisik jaringan.

```mermaid
flowchart TB
    subgraph ClientDevice ["Client Device (PC/Laptop/Smartphone)"]
        Browser["Web Browser (Chrome/Edge/Safari)"]
    end

    subgraph LocalNetwork ["Local Network (LAN / WiFi)"]
        LAN["Connection Link"]
    end

    subgraph ServerMachine ["Local Server / VPS Machine"]
        subgraph WebServer ["Web Server (Apache v2.4 / Nginx)"]
            PHP["PHP Runtime 8.2+"]
            CI4["CodeIgniter 4 App (Sparkleen)"]
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
    AksesPelanggan --> PilihAksi{Pilih Aksi CRUD}
    
    PilihAksi -- Tambah --> FormTambah[Klik 'Tambah Pelanggan']
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

    SelesaiAksi --> ReloadTabel[Reload Tabel Pelanggan & Tampilkan Toast Sukses]
    ReloadTabel --> End([Selesai])
```

---

## 7. Business Process Model and Notation (BPMN)
Menggambarkan alur kerja utama bisnis dari pelanggan datang, pelayanan kasir, otomatisasi sistem, hingga pengambilan cucian menggunakan format Flowchart ber-Swimlanes yang mensimulasikan standar BPMN.

```mermaid
graph TD
    %% BPMN Swimlanes for Sparkleen Laundry
    subgraph Pelanggan [LANE: Pelanggan]
        Start([Mulai]) --> B1[Datang Bawa Pakaian]
        B1 --> B2[Pilih Layanan & Wangi]
        B5[Melakukan Pembayaran] --> B6[Menerima Struk & Pakaian]
        B6 --> End([Selesai])
    end

    subgraph Kasir [LANE: Kasir]
        B2 --> K1[Input Pelanggan & Timbang Pakaian]
        K1 --> K2{Pilihan Pembayaran?}
        K2 -->|DP / Lunas| K3[Proses Pembayaran]
        K2 -->|Belum Bayar| K4[Simpan Transaksi]
        K3 --> K4
        K4 --> K5[Serahkan Pakaian ke Bagian Cuci]
        K5 --> K6[Proses Cuci, Kering, Setrika]
        K6 --> K7[Ubah Status: Selesai]
        K7 --> K8[Pakaian Siap di Rak]
        K8 --> K9[Serahkan Pakaian & Konfirmasi Selesai]
    end

    subgraph Sistem [LANE: Sistem Sparkleen]
        K4 -.-> S1[Simpan ke Database & Potong Inventaris]
        S1 -.-> S2[Kirim WA: Order Baru]
        K3 -.-> S3[Simpan Detail Pembayaran]
        K7 -.-> S4[Kirim WA: Pakaian Selesai]
        K9 -.-> S5[Simpan Log Aktivitas & Kirim WA: Diambil]
        S2 -.-> B5
        S4 -.-> B5
        S5 -.-> End
    end

    %% Styling BPMN Elements
    classDef laneStyle fill:#1e293b,stroke:#475569,stroke-width:2px,color:#e2e8f0;
    classDef startEnd fill:#10b981,stroke:#059669,stroke-width:2px,color:#fff,rx:20px,ry:20px;
    classDef taskStyle fill:#3b82f6,stroke:#2563eb,stroke-width:2px,color:#fff;
    classDef gatewayStyle fill:#f59e0b,stroke:#d97706,stroke-width:2px,color:#fff;
    classDef systemStyle fill:#06b6d4,stroke:#0891b2,stroke-width:2px,color:#fff;

    class Pelanggan,Kasir,Sistem laneStyle;
    class Start,End startEnd;
    class B1,B2,B5,B6,K1,K3,K4,K5,K6,K7,K8,K9 taskStyle;
    class K2 gatewayStyle;
    class S1,S2,S3,S4,S5 systemStyle;
```

---
*Dokumentasi UML dan Desain Sistem ini disusun berdasarkan spesifikasi aktual basis data, relasi model-controller, dan fitur pengamanan terbaru dari proyek Sparkleen Laundry.*
