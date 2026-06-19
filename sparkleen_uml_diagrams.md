# Sparkleen Laundry Management System — UML Diagrams

> **Kode Mermaid siap-render** untuk diagram: Use Case, Sequence, Activity, Flowchart, BPMN, ERD, dan LRS.
> **Render cepat:** salin satu blok `mermaid` ke [mermaid.live](https://mermaid.live) atau buka `mermaid_renderer.html` di browser.
> **Framework:** CodeIgniter 4 · **DB:** MySQL · **Tanggal refactor:** 2026-06-16

---

## Daftar Diagram

| #  | Diagram                       | Tipe Mermaid        | Fokus                                                  |
| -- | ----------------------------- | ------------------- | ------------------------------------------------------ |
| 0A | **High-Level: System Context**| `flowchart`         | C4 L1 — Siapa & apa yang berinteraksi dengan sistem   |
| 0B | **High-Level: Container**     | `flowchart`         | C4 L2 — Arsitektur container & lapisan teknologi      |
| 1  | Use Case Diagram              | `flowchart`         | 4 actor & seluruh fitur sistem                         |
| 2  | Sequence: Login               | `sequenceDiagram`   | Autentikasi + throttle + audit                         |
| 3  | Sequence: Transaksi           | `sequenceDiagram`   | 3-step wizard + DB transaction + WA notify             |
| 4  | Sequence: Pembayaran          | `sequenceDiagram`   | Cicilan + status update                                |
| 5  | Sequence: Lacak               | `sequenceDiagram`   | Public tracking + rate-limit + masking                 |
| 6  | Activity: Login               | `flowchart`         | Alur login + throttle + alur alternatif                |
| 7  | Activity: Transaksi           | `flowchart`         | Wizard + simpan + notifikasi                           |
| 8  | Activity: Status              | `flowchart`         | Perubahan status + log + WA                            |
| 9  | Flowchart: Login              | `flowchart`         | Diagram alur top-down login                            |
| 10 | Flowchart: Transaksi          | `flowchart`         | Diagram alur top-down pembuatan transaksi             |
| 11 | BPMN: Order Process           | `flowchart`         | End-to-end proses bisnis laundry                       |
| 12 | ERD                           | `erDiagram`         | Conceptual Entity-Relationship (semua tabel)           |
| 13 | LRS                           | `flowchart`         | Logical Record Structure dengan FK & junction          |

---

## 0A. HIGH-LEVEL: SYSTEM CONTEXT (C4 Level 1)

Gambaran siapa dan apa saja yang berinteraksi dengan sistem. Cocok untuk presentasi ke stakeholder non-teknis.

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#e8f0fe', 'primaryBorderColor': '#1e3a5f', 'primaryTextColor': '#0b1d3a', 'lineColor': '#4a90d9', 'secondaryColor': '#fef3c7', 'tertiaryColor': '#dbeafe', 'fontSize': '14px'}, 'flowchart': {'curve': 'basis', 'useMaxWidth': true, 'htmlLabels': true, 'diagramPadding': 20}}}%%
flowchart LR
    classDef person fill:#08427b,color:#fff,stroke:#052e56,stroke-width:2px
    classDef system fill:#1168bd,color:#fff,stroke:#0b4884,stroke-width:3px
    classDef ext fill:#999,color:#fff,stroke:#6b6b6b,stroke-width:2px
    classDef central fill:#1168bd,color:#fff,stroke:#fff,stroke-width:4px

    K([👤 Kasir]):::person
    A([👤 Admin]):::person
    P([👤 Pemilik]):::person
    C([🌐 Customer<br/>Anonim]):::person

    SP["<b>Sparkleen Laundry<br/>Management System</b><br/>━━━━━━━━━━━━━━━<br/>Aplikasi web laundry<br/>berbasis CodeIgniter 4<br/>untuk operasional harian<br/>laundry skala UMKM"]:::central

    FONN["<b>Fonnte API</b><br/>api.fonnte.com<br/>━━━━━━━━━<br/>WhatsApp Gateway<br/>Gateway 1"]:::ext
    WAB["<b>Wablas API</b><br/>solo.wablas.com<br/>━━━━━━━━━<br/>WhatsApp Gateway<br/>Gateway 2"]:::ext
    WAME["<b>wa.me</b><br/>Tautan manual<br/>━━━━━━━━━<br/>Fallback WA<br/>tanpa API"]:::ext

    K -->|Login, transaksi,<br/>pembayaran| SP
    A -->|Login, master data,<br/>laporan| SP
    P -->|Login, baca laporan,<br/>audit, backup| SP
    C -->|GET /lacak<br/>no auth, rate-limited| SP

    SP -->|Kirim WA<br/>order_baru| FONN
    SP -->|Kirim WA<br/>order_selesai| FONN
    SP -->|Kirim WA<br/>order_diambil| FONN
    SP -->|Alt: kirim WA| WAB
    SP -->|Fallback: link WA| WAME
```

---

## 0B. HIGH-LEVEL: CONTAINER (C4 Level 2)

Arsitektur container: proses/artefak teknologi di dalam sistem.

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#e8f0fe', 'primaryBorderColor': '#1e3a5f', 'primaryTextColor': '#0b1d3a', 'lineColor': '#4a90d9', 'secondaryColor': '#fef3c7', 'tertiaryColor': '#dbeafe', 'fontSize': '14px'}, 'flowchart': {'curve': 'basis', 'useMaxWidth': true, 'htmlLabels': true, 'diagramPadding': 20}}}%%
flowchart TB
    classDef browser fill:#08427b,color:#fff
    classDef webapp fill:#1168bd,color:#fff,stroke-width:2px
    classDef layer fill:#438dd5,color:#fff
    classDef ext fill:#999,color:#fff
    classDef store fill:#999,color:#fff

    B[<b>Browser</b><br/>━━━━━━━━━<br/>Chrome/Edge/Firefox<br/>+ AdminLTE 4 UI<br/>+ jQuery, Chart.js<br/>+ SweetAlert2]:::browser

    subgraph APP["🖥️ Apache + XAMPP (localhost)"]
        direction TB

        subgraph PRES["<b>Presentation Layer</b><br/>(Views: PHP templates + AdminLTE 4)"]
            V_AUTH[auth/login.php]:::layer
            V_DASH[dashboard/kasir_admin_owner.php]:::layer
            V_TRX[transaksi/create, daftar,<br/>detail, nota, ubah_status,<br/>pembayaran.php]:::layer
            V_MAS[pelanggan, karyawan,<br/>layanan, inventaris.php]:::layer
            V_REP[laporan/index, rentang.php]:::layer
            V_PUB[profile, backup,<br/>log_*, lacak/*.php]:::layer
        end

        subgraph CTRL["<b>Controller Layer</b><br/>(14 Controllers + PaginationTrait)"]
            C_AUTH[Auth]:::layer
            C_DASH[Dashboard<br/>+ apiStatusKasir]:::layer
            C_TRX[Transaksi<br/>+ ajaxDetail, ajaxSearch]:::layer
            C_MAS[Pelanggan, Karyawan,<br/>Layanan, Inventaris]:::layer
            C_LAP[Laporan]:::layer
            C_LOG[LogAktivitas,<br/>LogWhatsapp]:::layer
            C_PUB[Lacak publik,<br/>Profile, Backup]:::layer
            C_PT[PaginationTrait<br/>shared sort/page logic]:::layer
        end

        subgraph SVC["<b>Service + Library Layer</b>"]
            S_TRX[TransaksiService<br/>prosesTransaksiBaru,<br/>prosesPembayaran,<br/>prosesUbahStatus]:::layer
            S_DASH[DashboardService]:::layer
            S_LAP[LaporanService]:::layer
            S_LACAK[LacakService<br/>search, mask, rate-limit]:::layer
            S_KAR[KaryawanService]:::layer
            S_BAK[BackupService<br/>generateSqlDump]:::layer
            S_WA[WhatsAppNotifier<br/>+ CURLRequest]:::layer
            S_LT[LoginThrottle<br/>+ CI Cache]:::layer
            S_HLP[Helpers: activity_log,<br/>password, shift, ui,<br/>validation]:::layer
            S_CFG[Config: StatusTransaksi,<br/>StatusPembayaran constants]:::layer
        end

        subgraph FILT["<b>Filter Layer (Middleware)</b>"]
            F_AUTH[AuthFilter<br/>global: cek isLoggedIn]:::layer
            F_ROLE[RoleFilter<br/>route-scoped: cek level]:::layer
        end

        subgraph MODL["<b>Model Layer</b><br/>(17 Models)"]
            M_DOM["MKaryawan, MPelanggan,<br/>MLayanan, MInventaris,<br/>MLayananInventaris,<br/>MTransaksi soft-delete,<br/>MDetailTransaksi, MPembayaran,<br/>MPenggunaanInventaris,<br/>MStatusLog, MTempCart"]:::layer
            M_LOG["MLogAktivitas,<br/>MLogWhatsapp"]:::layer
            M_MST["5x Master*Model<br/>lookup tables"]:::layer
        end
    end

    DB[("🗄️ MySQL / MariaDB<br/>━━━━━━━━━<br/>XAMPP<br/>17 domain tables<br/>+ 5 master tables")]:::store
    CA[(📁 CI Cache<br/>━━━━━━━━━<br/>File-based<br/>throttle +<br/>lacak rate-limit)]:::store

    FONN[<b>Fonnte API</b><br/>api.fonnte.com]:::ext
    WAB[<b>Wablas API</b><br/>solo.wablas.com]:::ext

    B <-->|HTTP form<br/>+ AJAX JSON| F_AUTH
    F_AUTH --> F_ROLE
    F_ROLE --> CTRL
    CTRL --> SVC
    CTRL --> MODL
    SVC --> MODL
    MODL <-->|Query Builder| DB
    S_LT <-->|cache read/write| CA
    S_WA -->|POST JSON| FONN
    S_WA -->|POST JSON| WAB
    PRES -.->|render| B
```

---

## 1. USE CASE DIAGRAM

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#e8f0fe', 'primaryBorderColor': '#1e3a5f', 'primaryTextColor': '#0b1d3a', 'lineColor': '#4a90d9', 'fontSize': '14px'}, 'flowchart': {'curve': 'basis', 'useMaxWidth': true, 'htmlLabels': true}}}%%
flowchart TB
    %% === Aktor (top row) ===
    subgraph AKTORS["Aktor (Pengguna Sistem)"]
        direction LR
        K([👤 Kasir])
        A([👤 Admin])
        P([👑 Pemilik])
        C([🌐 Publik<br/>Pelanggan])
    end

    %% === Sistem boundary ===
    subgraph SYS["<b>Sistem Sparkleen Laundry</b>"]
        direction TB

        %% --- Autentikasi ---
        subgraph AUTH["🔐 Autentikasi"]
            direction LR
            UC01(["Login"])
            UC02(["Logout"])
        end

        %% --- Dashboard ---
        subgraph DASH["📊 Dashboard"]
            direction TB
            UC03(["Kasir"])
            UC04(["Admin"])
            UC05(["Pemilik"])
            UC06(["Live Counter"])
        end

        %% --- Transaksi POS ---
        subgraph TRX["💳 Transaksi POS"]
            direction TB
            UC10(["Buat Transaksi"])
            UC11(["Cart"])
            UC12(["Simpan + PIN"])
            UC13(["Cetak Nota"])
            UC14(["Ubah Status"])
            UC15(["Pembayaran"])
            UC16(["Daftar Transaksi"])
            UC17(["Quick View"])
            UC18(["Kirim Ulang WA"])
        end

        %% --- Master Data ---
        subgraph MASTER["⚙️ Master Data"]
            direction TB
            UC20(["Pelanggan"])
            UC21(["Karyawan"])
            UC22(["Layanan"])
            UC23(["Inventaris"])
            UC24(["Resep"])
        end

        %% --- Laporan ---
        subgraph REPORT["📊 Laporan & Log"]
            direction TB
            UC30(["Laporan Transaksi"])
            UC31(["Export PDF/XLSX"])
            UC32(["Log Aktivitas"])
            UC33(["Log WhatsApp"])
        end

        %% --- Sistem ---
        subgraph SYSCORE["🛠️ Sistem"]
            direction TB
            UC40(["Backup DB"])
            UC41(["Profile & Password"])
            UC42(["Toggle Aktif"])
        end

        %% --- Publik ---
        subgraph PUB["🌐 Akses Publik"]
            direction LR
            UC50(["Lacak by Nota"])
            UC51(["Lacak by No HP"])
        end
    end

    %% === Asosiasi Actor → Use Case (dari aktor ke UC) ===
    K --> UC01 & UC02
    A --> UC01 & UC02
    P --> UC01 & UC02

    K --> UC03
    A --> UC04
    P --> UC05
    K -.->|AJAX poll| UC06

    K --> UC10 & UC11 & UC12 & UC13 & UC14 & UC15 & UC16 & UC17 & UC18

    A --> UC20 & UC21 & UC22 & UC23 & UC24

    A --> UC30 & UC31
    P --> UC30 & UC31 & UC32
    A --> UC33
    K --> UC33
    P --> UC33

    P --> UC40
    A --> UC41
    K --> UC41
    P --> UC41
    A --> UC42
    P --> UC42

    C --> UC50 & UC51

    classDef actor fill:#1e3a5f,color:#fff,stroke:#0b1d3a,stroke-width:2px
    classDef uc fill:#e8f0fe,color:#0b1d3a,stroke:#4a90d9,stroke-width:1px
    classDef pubUC fill:#cffafe,color:#0e7490,stroke:#0891b2,stroke-width:1px

    class K,A,P,C actor
    class UC01,UC02,UC03,UC04,UC05,UC06,UC10,UC11,UC12,UC13,UC14,UC15,UC16,UC17,UC18,UC20,UC21,UC22,UC23,UC24,UC30,UC31,UC32,UC33,UC40,UC41,UC42 uc
    class UC50,UC51 pubUC
```

---

## 2. SEQUENCE DIAGRAM: LOGIN

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'actorBkg': '#1e3a5f', 'actorTextColor': '#fff', 'actorLineColor': '#4a90d9', 'signalColor': '#0b1d3a', 'signalTextColor': '#0b1d3a', 'noteBkgColor': '#fef3c7', 'noteTextColor': '#78350f', 'noteBorderColor': '#f59e0b', 'fontSize': '14px'}, 'sequence': {'showSequenceNumbers': true, 'useMaxWidth': true}}}%%
sequenceDiagram
    autonumber
    actor U as Browser/User
    participant F as AuthFilter
    participant A as Auth Controller
    participant LT as LoginThrottle
    participant C as CI Cache
    participant MK as MKaryawan
    participant DB as tb_karyawan
    participant LOG as MLogAktivitas
    participant S as Session

    U->>F: GET /dashboard
    F->>F: session('isLoggedIn')?
    alt Belum login
        F-->>U: 302 → /login
    end

    U->>A: POST /doLogin (username, password)
    A->>LT: isBlocked(ip, username)
    LT->>C: cache('login_throttle_ip_xxx')
    C-->>LT: record
    alt Locked
        LT-->>A: true
        A->>LOG: save('Login ditolak: throttled')
        A-->>U: redirect /login + flash error
    else Not locked
        A->>MK: getUserByIdentifier(username|email)
        MK->>DB: SELECT * WHERE username=? OR email=?
        DB-->>MK: row | null
        alt User tidak ditemukan
            MK-->>A: null
            A->>LT: recordFailure(ip, username)
            A->>LOG: save('Login gagal: user tidak ada')
            A-->>U: redirect /login + flash error
        else User ditemukan
            A->>A: password_verify(input, hash)
            alt Password salah
                A->>LT: recordFailure(ip, username)
                A->>LOG: save('Login gagal: password salah')
                A-->>U: redirect /login + flash error
            else Password benar
                A->>LT: clear(ip, username)
                A->>S: set(id_karyawan, nama, level, isLoggedIn=true)
                A->>LOG: save('Login berhasil')
                A-->>U: 302 → /dashboard
            end
        end
    end
```

---

## 3. SEQUENCE DIAGRAM: BUAT TRANSAKSI (3-STEP WIZARD)

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'actorBkg': '#1e3a5f', 'actorTextColor': '#fff', 'actorLineColor': '#4a90d9', 'signalColor': '#0b1d3a', 'signalTextColor': '#0b1d3a', 'noteBkgColor': '#fef3c7', 'noteTextColor': '#78350f', 'noteBorderColor': '#f59e0b', 'fontSize': '14px'}, 'sequence': {'showSequenceNumbers': true, 'useMaxWidth': true}}}%%
sequenceDiagram
    autonumber
    actor KS as Kasir
    participant V as Browser
    participant F as AuthFilter
    participant T as Transaksi Controller
    participant SVC as TransaksiService
    participant DTC as MTempCart
    participant LI as MLayananInventaris
    participant INV as MInventaris
    participant PLG as MPelanggan
    participant TRX as MTransaksi
    participant DET as MDetailTransaksi
    participant PI as MPenggunaanInventaris
    participant WA as WhatsAppNotifier
    participant WLOG as MLogWhatsapp

    KS->>V: Buka /transaksi
    V->>F: GET /transaksi
    F->>T: dispatch (session valid)
    T->>SVC: calculateCartRekomendasi(temp)
    SVC->>LI: whereIn(id_layanan, [...])
    LI-->>SVC: resep items (batch)
    SVC-->>T: resepRekomendasi
    T->>DTC: where id_kasir=session AND id_pelanggan=NULL
    DTC-->>T: temp items
    T-->>V: render create.php (grid: form | struk)

    KS->>V: Pilih layanan + berat
    V->>T: POST /transaksi/addToCart
    T->>DTC: save({id_pelanggan:NULL, id_kasir, id_layanan, berat})
    DTC-->>T: id_temp
    T-->>V: redirect back (refresh struk)

    KS->>V: Set pelanggan + qty inventaris + klik "Simpan"
    V->>T: POST /transaksi/simpanTransaksi

    T->>SVC: validateStokInventaris(postData)
    SVC->>INV: find(id) per item
    INV-->>SVC: stok data
    alt Stok tidak cukup
        SVC-->>T: {valid: false, error_message}
        T-->>V: flash error + redirect step 2
    end

    T->>SVC: prosesTransaksiBaru(post, id_kasir, items, pelanggan)
    Note over SVC: $db->transBegin()

    SVC->>TRX: save(header transaksi)
    TRX-->>SVC: id_transaksi
    SVC->>TRX: update(no_nota)
    SVC->>PLG: update(poin, total_transaksi)
    SVC->>DET: save ×N (satu per item layanan)
    SVC->>PI: save ×N (penggunaan inventaris)
    SVC->>INV: kurangiStok(id, jumlah)

    Note over SVC: $db->transCommit()
    SVC-->>T: {success, id_transaksi, pin_pengambilan}

    T->>DTC: delete WHERE id_kasir=session AND id_pelanggan=NULL

    T->>WA: notifyOrderBaru(transaksi, pelanggan)
    WA->>WA: build message (embed pin + /lacak link)
    alt provider = fonnte + token
        WA->>WA: POST api.fonnte.com/send
    else provider = wablas + token
        WA->>WA: POST solo.wablas.com/api/send-message
    else provider = link / no token
        WA->>WA: build wa.me link only
    end
    WA->>WLOG: insert (no_nota, status=sukses|gagal|pending, respon_api)
    WLOG-->>WA: id_log

    T-->>V: 302 → /transaksi/nota/{id_transaksi}
```

---

## 4. SEQUENCE DIAGRAM: PEMBAYARAN (CICILAN/LUNAS)

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'actorBkg': '#1e3a5f', 'actorTextColor': '#fff', 'actorLineColor': '#4a90d9', 'signalColor': '#0b1d3a', 'signalTextColor': '#0b1d3a', 'noteBkgColor': '#fef3c7', 'noteTextColor': '#78350f', 'noteBorderColor': '#f59e0b', 'fontSize': '14px'}, 'sequence': {'showSequenceNumbers': true, 'useMaxWidth': true}}}%%
sequenceDiagram
    autonumber
    actor KS as Kasir
    participant V as Browser
    participant T as Transaksi Controller
    participant SVC as TransaksiService
    participant TRX as MTransaksi
    participant PBY as MPembayaran

    KS->>V: GET /transaksi/pembayaran/{id}
    V->>T: GET
    T->>TRX: getTransaksiWithJoins(id)
    TRX-->>T: header + nama status
    T->>PBY: where id_transaksi (riwayat bayar)
    PBY-->>T: list pembayaran
    T->>SVC: hitungSisaTagihan(id)
    SVC-->>T: sisa
    T->>SVC: generateQrCode(sisa, no_nota)
    SVC-->>T: qrCodeDataUri | null
    T-->>V: render pembayaran.php

    KS->>V: Isi nominal + metode + klik Simpan
    V->>T: POST /transaksi/simpanPembayaran

    T->>SVC: prosesPembayaran(id_transaksi, data)
    SVC->>PBY: SUM(nominal_bayar) WHERE id_transaksi
    PBY-->>SVC: total_sudah_dibayar
    SVC->>SVC: sisa = grand_total - total_sudah_dibayar

    alt nominal_bayar > sisa
        SVC->>SVC: kembalian = nominal_bayar - sisa<br/>nominal_bayar = sisa (cap)
    end

    Note over SVC: $db->transStart()
    SVC->>PBY: save({id_transaksi, tgl_bayar, nominal_bayar,<br/>sisa_tagihan, id_metode_pembayaran})
    SVC->>SVC: status_pembayaran = (sisa<=0) ? LUNAS : CICIL
    SVC->>TRX: update({id_status_pembayaran, id_metode_pembayaran})
    Note over SVC: $db->transComplete()
    SVC-->>T: {success, message, kembalian}
    T-->>V: 302 → /transaksi/pembayaran/{id} + flash success
```

---

## 5. SEQUENCE DIAGRAM: LACAK ORDER PUBLIK

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'actorBkg': '#1e3a5f', 'actorTextColor': '#fff', 'actorLineColor': '#4a90d9', 'signalColor': '#0b1d3a', 'signalTextColor': '#0b1d3a', 'noteBkgColor': '#fef3c7', 'noteTextColor': '#78350f', 'noteBorderColor': '#f59e0b', 'fontSize': '14px'}, 'sequence': {'showSequenceNumbers': true, 'useMaxWidth': true}}}%%
sequenceDiagram
    autonumber
    actor C as Customer (no auth)
    participant V as Browser
    participant L as Lacak Controller
    participant LS as LacakService
    participant CA as CI Cache
    participant TRX as MTransaksi
    participant DET as MDetailTransaksi
    participant SLG as MStatusLog
    participant DB as MySQL

    C->>V: GET /lacak?nota=LAU/20260526/0001
    V->>L: dispatch
    L->>LS: checkRateLimit(ip)
    LS->>CA: cache('lacak_throttle_'.md5(ip))
    CA-->>LS: record {attempts, locked_until}
    LS-->>L: {blocked, sisa_detik}

    alt blocked = true
        L-->>V: render lacak/index + flash<br/>"Terlalu banyak percobaan"
    else not locked
        L->>LS: recordAttempt(ip)
        LS->>CA: increment attempts<br/>if >=15 set locked_until = now+60s

        L->>LS: searchByNota(nota)
        LS->>TRX: select + join tb_pelanggan,<br/>LEFT JOIN tb_master_status_transaksi st,<br/>LEFT JOIN tb_master_status_pembayaran sp
        TRX->>DB: SELECT ...
        DB-->>TRX: row | null

        alt nota ditemukan
            TRX-->>LS: transaksi
            LS->>DET: select + join tb_layanan WHERE id_transaksi
            DET-->>LS: detail items
            LS->>SLG: select + join tb_karyawan<br/>WHERE id_transaksi ORDER BY tgl_ubah DESC
            SLG-->>LS: status history

            LS->>LS: maskName(nama_pelanggan)<br/>maskPhone(no_telp)
            LS-->>L: {transaksi, detail, status_log}
            L-->>V: render lacak/index.php<br/>(5-step timeline + masked info)
        else nota tidak ada
            LS-->>L: {error: "Nomor nota tidak ditemukan"}
            L-->>V: render + errorMsg
        end
    end
```

---

## 6. ACTIVITY DIAGRAM: LOGIN

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#e8f0fe', 'primaryBorderColor': '#1e3a5f', 'primaryTextColor': '#0b1d3a', 'lineColor': '#4a90d9', 'secondaryColor': '#fef3c7', 'tertiaryColor': '#dbeafe', 'fontSize': '14px'}, 'flowchart': {'curve': 'basis', 'useMaxWidth': true, 'htmlLabels': true, 'diagramPadding': 20}}}%%
flowchart TD
    START([Start]) --> INPUT[/Input username &<br/>password/]
    INPUT --> CHK_SESSION{Session<br/>isLoggedIn?}
    CHK_SESSION -- Ya --> REDIR_DASH[Redirect ke<br/>/dashboard]
    CHK_SESSION -- Tidak --> CHK_THROTTLE{Throttle<br/>isBlocked?}
    CHK_THROTTLE -- Ya --> LOG_BLOCKED[log_aktivitas:<br/>Login ditolak throttled]
    LOG_BLOCKED --> END_LOCK([End - Locked])
    CHK_THROTTLE -- Tidak --> GET_USER[MKaryawan::getUserByIdentifier]
    GET_USER --> USER_FOUND{User<br/>ditemukan?}
    USER_FOUND -- Tidak --> RECORD_FAIL[LoginThrottle::<br/>recordFailure]
    RECORD_FAIL --> LOG_NF[log_aktivitas:<br/>Login gagal: user]
    LOG_NF --> REDIR_LOGIN[Redirect /login<br/>+ flash error]
    REDIR_LOGIN --> END_NF([End])
    USER_FOUND -- Ya --> VERIFY[password_verify]
    VERIFY --> PW_OK{Password<br/>cocok?}
    PW_OK -- Tidak --> RECORD_FAIL2[LoginThrottle::<br/>recordFailure]
    RECORD_FAIL2 --> LOG_PW[log_aktivitas:<br/>Login gagal: password]
    LOG_PW --> REDIR_LOGIN
    PW_OK -- Ya --> CLEAR_THROTTLE[LoginThrottle::clear]
    CLEAR_THROTTLE --> SET_SESS[session.set<br/>id, nama, level,<br/>isLoggedIn]
    SET_SESS --> LOG_OK[log_aktivitas:<br/>Login berhasil]
    LOG_OK --> REDIR_DASH
    REDIR_DASH --> END_OK([End - Success])
```

---

## 7. ACTIVITY DIAGRAM: BUAT TRANSAKSI

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#e8f0fe', 'primaryBorderColor': '#1e3a5f', 'primaryTextColor': '#0b1d3a', 'lineColor': '#4a90d9', 'secondaryColor': '#fef3c7', 'tertiaryColor': '#dbeafe', 'fontSize': '14px'}, 'flowchart': {'curve': 'basis', 'useMaxWidth': true, 'htmlLabels': true, 'diagramPadding': 20}}}%%
flowchart TD
    START([Start]) --> SESSION{Login?}
    SESSION -- Tidak --> REDIR_LOGIN[Redirect /login]
    SESSION -- Ya --> CEK_ROLE{Level kasir<br/>atau admin?}
    CEK_ROLE -- Tidak --> REDIR_BACK[Redirect back<br/>+ flash error]
    CEK_ROLE -- Ya --> LOAD_FORM[Load form create.php<br/>pelanggan + layanan + inventaris]
    LOAD_FORM --> PILIH_LAY[Kasir pilih layanan + berat]
    PILIH_LAY --> ADD_CART[addToCart:<br/>MTempCart.save]
    ADD_CART --> ADD_LAGI{Tambah<br/>lagi?}
    ADD_LAGI -- Ya --> PILIH_LAY
    ADD_LAGI -- Tidak --> SET_PLG[Set / pilih pelanggan]
    SET_PLG --> KONFIRM[Kasir konfirmasi + qty inventaris]
    KONFIRM --> SIMPAN[simpanTransaksi]
    SIMPAN --> DB_BEGIN[(db::transBegin)]
    DB_BEGIN --> HITUNG[Hitung total_berat, total_biaya,<br/>diskon, poin, grand_total, PIN]
    HITUNG --> CEK_STOK{Stok inventaris<br/>cukup?}
    CEK_STOK -- Tidak --> DB_ROLLBACK1[(transRollback)]
    DB_ROLLBACK1 --> REDIR_ERR[Redirect step 2 + flash error]
    CEK_STOK -- Ya --> SAVE_HEADER[MTransaksi.save]
    SAVE_HEADER --> UPD_PLG[MPelanggan.update poin & total]
    UPD_PLG --> SAVE_DET[MDetailTransaksi.save ×N]
    SAVE_DET --> SAVE_PI[MPenggunaanInventaris.save ×N]
    SAVE_PI --> KURANGI_STOK[MInventaris.kurangiStok ×N]
    KURANGI_STOK --> DEL_TEMP[MTempCart.delete cart]
    DEL_TEMP --> WA_NOTIFY[WhatsAppNotifier<br/>notifyOrderBaru]
    WA_NOTIFY --> WA_LOG[MLogWhatsapp.insert log row]
    WA_LOG --> LOG_AKT[log_aktivitas simpan]
    LOG_AKT --> DB_COMMIT[(db::transCommit)]
    DB_COMMIT --> REDIR_NOTA[Redirect /transaksi/nota/id]
    REDIR_NOTA --> CETAK[Kasir cetak nota thermal]
    CETAK --> END([End])
```

---

## 8. ACTIVITY DIAGRAM: UBAH STATUS TRANSAKSI

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#e8f0fe', 'primaryBorderColor': '#1e3a5f', 'primaryTextColor': '#0b1d3a', 'lineColor': '#4a90d9', 'secondaryColor': '#fef3c7', 'tertiaryColor': '#dbeafe', 'fontSize': '14px'}, 'flowchart': {'curve': 'basis', 'useMaxWidth': true, 'htmlLabels': true, 'diagramPadding': 20}}}%%
flowchart TD
    START([Start]) --> OPEN_FORM[Transaksi::ubahStatus id]
    OPEN_FORM --> LOAD[Load transaksi + master status +<br/>TransaksiService::checkPinRequired]
    LOAD --> RENDER[Render ubah_status.php]
    RENDER --> PILIH[Kasir pilih status_baru +<br/>opsional kirim_wa]
    PILIH --> SUBMIT[POST /transaksi/prosesUbahStatus]
    SUBMIT --> SVC[TransaksiService::prosesUbahStatus]
    SVC --> UPDATE[MTransaksi.update status]
    UPDATE --> LOG_STATUS[MStatusLog.save]
    LOG_STATUS --> CEK_SELESAI{status_baru ==<br/>'selesai'?}
    CEK_SELESAI -- Ya --> SET_AKTUAL[MTransaksi.update<br/>tgl_selesai_aktual]
    SET_AKTUAL --> KIRIM_WA1{kirim_wa<br/>== 1?}
    KIRIM_WA1 -- Ya --> WA_SELESAI[WhatsAppNotifier<br/>notifyOrderSelesai]
    KIRIM_WA1 -- Tidak --> CEK_AMBIL
    WA_SELESAI --> WLOG1[MLogWhatsapp.insert]
    WLOG1 --> CEK_AMBIL{status_baru ==<br/>'diambil'?}
    CEK_SELESAI -- Tidak --> CEK_AMBIL
    CEK_AMBIL -- Ya --> KIRIM_WA2{kirim_wa<br/>== 1?}
    KIRIM_WA2 -- Ya --> WA_AMBIL[WhatsAppNotifier<br/>notifyOrderDiambil]
    KIRIM_WA2 -- Tidak --> SVC_RET
    WA_AMBIL --> WLOG2[MLogWhatsapp.insert]
    WLOG2 --> SVC_RET
    CEK_AMBIL -- Tidak --> SVC_RET
    SVC_RET["SVC returns: success, message, wa_result"]
    SVC_RET --> REDIR_DET[Redirect /transaksi/detail/id]
    REDIR_DET --> END([End])
```

---

## 9. FLOWCHART: SISTEM (MASTER)

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#e8f0fe', 'primaryBorderColor': '#1e3a5f', 'primaryTextColor': '#0b1d3a', 'lineColor': '#4a90d9', 'secondaryColor': '#fef3c7', 'tertiaryColor': '#dbeafe', 'fontSize': '14px'}, 'flowchart': {'curve': 'basis', 'useMaxWidth': true, 'htmlLabels': true, 'diagramPadding': 20}}}%%
flowchart TD
    START([User Buka Browser]) --> ROUTE{HTTP<br/>Request}
    ROUTE --> AUTH[AuthFilter<br/>cek isLoggedIn]
    AUTH -- Gagal --> LOGIN_PAGE[Halaman /login]
    LOGIN_PAGE --> DO_LOGIN[POST /doLogin]
    DO_LOGIN --> ROLE_AUTH{Level<br/>Session}
    ROLE_AUTH -- kasir --> DASH_K[Dashboard Kasir]
    ROLE_AUTH -- admin --> DASH_A[Dashboard Admin]
    ROLE_AUTH -- pemilik --> DASH_P[Dashboard Pemilik]

    AUTH -- Sukses --> ROUTE2{URI<br/>Route}

    ROUTE2 -- /lacak --> LACAK[Lacak publik<br/>+ rate limit]
    ROUTE2 -- /transaksi* --> TRX[Transaksi Controller]
    ROUTE2 -- /pelanggan* --> PLG[Pelanggan]
    ROUTE2 -- /layanan* --> LAY[Layanan]
    ROUTE2 -- /karyawan* --> KAR[Karyawan]
    ROUTE2 -- /inventaris* --> INV[Inventaris]
    ROUTE2 -- /laporan* --> LAP[Laporan]
    ROUTE2 -- /log-aktivitas --> LOGA[LogAktivitas]
    ROUTE2 -- /log-whatsapp --> LOGW[LogWhatsapp]
    ROUTE2 -- /backup --> BAK[Backup DB]
    ROUTE2 -- /profile --> PRO[Profile]

    DASH_K --> TRX
    DASH_A --> TRX
    DASH_A --> PLG
    DASH_A --> LAY
    DASH_A --> INV
    DASH_A --> LAP
    DASH_P --> TRX
    DASH_P --> PLG
    DASH_P --> LAY
    DASH_P --> INV
    DASH_P --> KAR
    DASH_P --> LAP
    DASH_P --> LOGA
    DASH_P --> BAK

    TRX --> DB[(MySQL)]
    PLG --> DB
    LAY --> DB
    KAR --> DB
    INV --> DB
    LAP --> DB
    LOGA --> DB
    LOGW --> DB
    BAK --> DB

    TRX -.-> WA_API[WhatsApp API<br/>Fonnte / Wablas]
    WA_API -.-> TRX

    LACAK --> DB
```

---

## 10. FLOWCHART: TRANSAKSI POS

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#e8f0fe', 'primaryBorderColor': '#1e3a5f', 'primaryTextColor': '#0b1d3a', 'lineColor': '#4a90d9', 'secondaryColor': '#fef3c7', 'tertiaryColor': '#dbeafe', 'fontSize': '14px'}, 'flowchart': {'curve': 'basis', 'useMaxWidth': true, 'htmlLabels': true, 'diagramPadding': 20}}}%%
flowchart TD
    START([Kasir buka /transaksi]) --> FORM[Tampil form POS<br/>Grid: input kiri, struk kanan]
    FORM --> PILIH_LAY{Pilih<br/>layanan?}
    PILIH_LAY -- Ya --> ADD[POST addToCart]
    ADD --> SIMPAN_TEMP[(MTempCart.save)]
    SIMPAN_TEMP --> RELOAD[Refresh form<br/>+ struk + resep]
    RELOAD --> PILIH_LAY
    PILIH_LAY -- Tidak --> SET_PLG{id_pelanggan<br/>di-set?}
    SET_PLG -- Ya --> ATUR_QTY[Atur qty inventaris]
    SET_PLG -- Tidak --> LANJUT[Bisa skip - id_pelanggan NULL]
    LANJUT --> ATUR_QTY
    ATUR_QTY --> SIMPAN{Simpan<br/>Transaksi?}
    SIMPAN -- Tidak --> FORM
    SIMPAN -- Ya --> POST_SIMPAN[POST simpanTransaksi]
    POST_SIMPAN --> DB_BEGIN[(db::transBegin)]
    DB_BEGIN --> HITUNG[Hitung total + poin + pin]
    HITUNG --> CEK_STOK{Stok cukup?}
    CEK_STOK -- Tidak --> ROLLBACK[(transRollback)]
    ROLLBACK --> ERR_STOK[Flash error +<br/>redirect step 2]
    ERR_STOK --> ATUR_QTY
    CEK_STOK -- Ya --> SAVE_ALL[Save header + detail +<br/>penggunaan + kurangi stok]
    SAVE_ALL --> CLEAR_CART[MTempCart.delete cart]
    CLEAR_CART --> WA[WhatsAppNotifier<br/>notifyOrderBaru]
    WA --> WLOG[MLogWhatsapp.insert]
    WLOG --> COMMIT[(transCommit)]
    COMMIT --> REDIR_NOTA[Redirect /transaksi/nota/id]
    REDIR_NOTA --> CETAK[Cetak Nota Thermal 58mm]
    CETAK --> END([End])
```

---

## 11. BPMN: PROSES ORDER LAUNDRY (END-TO-END)

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#e8f0fe', 'primaryBorderColor': '#1e3a5f', 'primaryTextColor': '#0b1d3a', 'lineColor': '#4a90d9', 'secondaryColor': '#fef3c7', 'tertiaryColor': '#dbeafe', 'fontSize': '14px'}, 'flowchart': {'curve': 'basis', 'useMaxWidth': true, 'htmlLabels': true, 'diagramPadding': 20}}}%%
flowchart LR
    %% Pool 1: Customer
    subgraph POOL_C["🏊 Pool: Customer"]
        C1([Start: Bawa cucian]) --> C2[Datang ke outlet]
        C2 --> C2a{Bawa<br/>struk lama?}
        C2a -- Ya --> C2b[Tunjukkan<br/>nomor nota]
        C2a -- Tidak --> C2c[Setor cucian<br/>baru]
        C2c --> C2d[Pesan layanan]
    end

    C2b --> P1
    C2d --> P1

    %% Pool 2: Kasir
    subgraph POOL_K["🏊 Pool: Kasir Sparkleen"]
        P1[Terima cucian] --> P2{Cek<br/>pelanggan?}
        P2 -- Baru --> P3[Buat entry<br/>pelanggan]
        P2 -- Lama --> P4[Pilih dari list]
        P3 --> P5
        P4 --> P5
        P5[Pilih layanan + berat] --> P6[Tambah ke cart]
        P6 --> P7{Stok<br/>bahan baku<br/>cukup?}
        P7 -- Tidak --> P8[Tolak sebagian<br/>atau info delay]
        P7 -- Ya --> P9[Generate PIN<br/>+ Nota]
        P9 --> P10[Simpan ke DB]
        P10 --> P11[Kirim WA<br/>order baru]
        P11 --> P12((Parallel:))
        P12 --> P12a[Cuci]
        P12a --> P12b[Setrika]
        P12b --> P13{Selesai?}
        P13 -- Ya --> P14[Update status<br/>'selesai']
        P14 --> P15[Kirim WA<br/>order selesai]
        P15 --> P16[Cucian siap<br/>diambil]
        P16 --> P17{Pelanggan<br/>datang?}
        P17 -- Ya --> P18[Verifikasi PIN]
        P18 --> P19{Pin valid?}
        P19 -- Tidak --> P20[Tolak<br/>pengambilan]
        P19 -- Ya --> P21[Serahkan cucian]
        P21 --> P22[Update status<br/>'diambil']
        P22 --> P23[Kirim WA<br/>order diambil]
        P23 --> P24([End])
        P17 -- Belum --> P16
    end

    %% Pool 3: Admin/Pemilik
    subgraph POOL_A["🏊 Pool: Admin / Pemilik"]
        P10 -.->|notif| M1[Monitor dashboard]
        M1 --> M2[Lihat laporan<br/>harian/bulanan]
        M2 --> M3[Export PDF/Excel]
        M3 --> M4([End: Reporting])
    end

    %% Styling hint
    classDef startEnd fill:#ffd966,stroke:#333,stroke-width:2px
    classDef task fill:#9bc2e6,stroke:#333
    classDef gateway fill:#f4b183,stroke:#333
    class C1,C24,P24,M4 startEnd
    classDef poolC fill:#d9ead3,stroke:#38761d
    classDef poolK fill:#cfe2f3,stroke:#1c4587
    classDef poolA fill:#fff2cc,stroke:#7f6000
    class POOL_C poolC
    class POOL_K poolK
    class POOL_A poolA
```

---

## 12. ERD (ENTITY-RELATIONSHIP DIAGRAM)

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#e8f0fe', 'primaryBorderColor': '#1e3a5f', 'primaryTextColor': '#0b1d3a', 'lineColor': '#4a90d9', 'fontSize': '14px'}, 'er': {'useMaxWidth': true, 'diagramPadding': 20}}}%%
erDiagram
    tb_karyawan ||--o{ tb_transaksi : "id_kasir"
    tb_karyawan ||--o{ tb_temp_keranjang : "id_kasir"
    tb_karyawan ||--o{ tb_status_log : "id_karyawan"
    tb_karyawan |o--o{ tb_log_aktivitas : "id_karyawan (nullable)"

    tb_pelanggan ||--o{ tb_transaksi : "id_pelanggan (nullable)"
    tb_pelanggan ||--o{ tb_temp_keranjang : "id_pelanggan (nullable)"

    tb_layanan ||--o{ tb_detail_transaksi : "id_layanan"
    tb_layanan ||--o{ tb_temp_keranjang : "id_layanan"
    tb_layanan ||--o{ tb_layanan_inventaris : "recipe"
    tb_inventaris ||--o{ tb_layanan_inventaris : "dipakai oleh"
    tb_inventaris ||--o{ tb_penggunaan_inventaris : "id_inventaris"

    tb_transaksi ||--o{ tb_detail_transaksi : "id_transaksi"
    tb_transaksi ||--o{ tb_pembayaran : "id_transaksi"
    tb_transaksi ||--o{ tb_penggunaan_inventaris : "id_transaksi"
    tb_transaksi ||--o{ tb_status_log : "id_transaksi"
    tb_transaksi ||--o{ tb_log_whatsapp : "no_nota"

    tb_master_level_karyawan ||--o{ tb_karyawan : "id_level_karyawan"
    tb_master_status_transaksi ||--o{ tb_transaksi : "id_status_transaksi"
    tb_master_status_pembayaran ||--o{ tb_transaksi : "id_status_pembayaran"
    tb_master_metode_pembayaran ||--o{ tb_transaksi : "id_metode_pembayaran"
    tb_master_metode_pembayaran ||--o{ tb_pembayaran : "id_metode_pembayaran"
    tb_master_status_whatsapp ||--o{ tb_log_whatsapp : "id_status_whatsapp"

    tb_karyawan {
        INT id_karyawan PK
        VARCHAR nama_karyawan
        VARCHAR username
        VARCHAR password
        VARCHAR email
        INT id_level_karyawan FK
        VARCHAR no_telp
        DECIMAL gaji_pokok
        TINYINT is_aktif
        DATETIME created_at
        DATETIME updated_at
    }

    tb_pelanggan {
        INT id_pelanggan PK
        VARCHAR kode_pelanggan
        VARCHAR nama_pelanggan
        VARCHAR no_telp "UNIQUE"
        TEXT alamat
        INT poin
        DECIMAL total_transaksi
        TINYINT is_aktif
        DATETIME created_at
        DATETIME updated_at
    }

    tb_layanan {
        INT id_layanan PK
        VARCHAR nama_layanan
        DECIMAL harga_per_kg
        TINYINT is_aktif
    }

    tb_inventaris {
        INT id_inventaris PK
        VARCHAR nama_barang
        DECIMAL stok
        VARCHAR satuan
        DECIMAL harga_beli
        DECIMAL stok_minimal
        TINYINT is_aktif
        DATETIME created_at
        DATETIME updated_at
    }

    tb_layanan_inventaris {
        INT id PK
        INT id_layanan FK
        INT id_inventaris FK
        DECIMAL jumlah_default
    }

    tb_transaksi {
        INT id_transaksi PK
        VARCHAR no_nota
        INT id_pelanggan FK
        INT id_kasir FK
        DATETIME tgl_masuk
        DATETIME tgl_selesai_estimasi
        DATETIME tgl_selesai_aktual
        DECIMAL total_berat
        DECIMAL total_biaya
        DECIMAL diskon
        INT poin_ditebus
        INT poin_diperoleh
        DECIMAL grand_total
        INT id_status_transaksi FK
        INT id_status_pembayaran FK
        INT id_metode_pembayaran FK
        VARCHAR pin_pengambilan
        DATETIME created_at
        DATETIME updated_at
        DATETIME deleted_at "soft delete"
    }

    tb_detail_transaksi {
        INT id_detail PK
        INT id_transaksi FK
        INT id_layanan FK
        DECIMAL berat
        DECIMAL harga_satuan
        DECIMAL subtotal
    }

    tb_pembayaran {
        INT id_pembayaran PK
        INT id_transaksi FK
        DATETIME tgl_bayar
        DECIMAL nominal_bayar
        DECIMAL sisa_tagihan
        INT id_metode_pembayaran FK
    }

    tb_penggunaan_inventaris {
        INT id_penggunaan PK
        INT id_transaksi FK
        INT id_inventaris FK
        DECIMAL jumlah_pakai
        TEXT keterangan
    }

    tb_status_log {
        INT id_log PK
        INT id_transaksi FK
        INT id_karyawan FK
        VARCHAR status_lama
        VARCHAR status_baru
        DATETIME tgl_ubah
        TEXT catatan
    }

    tb_temp_keranjang {
        INT id_temp PK
        INT id_pelanggan FK "nullable"
        INT id_kasir FK
        INT id_layanan FK
        DECIMAL berat
    }

    tb_log_aktivitas {
        INT id_log PK
        INT id_karyawan FK "nullable"
        VARCHAR nama_karyawan
        VARCHAR level_karyawan
        VARCHAR modul
        VARCHAR aksi
        INT id_referensi
        VARCHAR nama_referensi
        TEXT detail
        VARCHAR ip_address
        DATETIME created_at
    }

    tb_log_whatsapp {
        INT id_log PK
        VARCHAR no_nota
        VARCHAR no_tujuan
        TEXT isi_pesan
        VARCHAR tipe_notifikasi
        VARCHAR provider
        INT id_status_whatsapp FK
        TEXT respon_api
        DATETIME created_at
    }

    tb_master_level_karyawan {
        INT id PK
        VARCHAR nama_level
    }

    tb_master_status_transaksi {
        INT id PK
        VARCHAR nama_status
    }

    tb_master_status_pembayaran {
        INT id PK
        VARCHAR nama_status
    }

    tb_master_metode_pembayaran {
        INT id PK
        VARCHAR nama_metode
    }

    tb_master_status_whatsapp {
        INT id PK
        VARCHAR nama_status
    }
```

---

## 13. LRS (LOGICAL RECORD STRUCTURE)

Notasi LRS: record = kotak, primary key = garis bawah, foreign key = panah. Junction table = kotak dengan 2 FK keluar.

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#e8f0fe', 'primaryBorderColor': '#1e3a5f', 'primaryTextColor': '#0b1d3a', 'lineColor': '#4a90d9', 'secondaryColor': '#fef3c7', 'tertiaryColor': '#dbeafe', 'fontSize': '14px'}, 'flowchart': {'curve': 'basis', 'useMaxWidth': true, 'htmlLabels': true, 'diagramPadding': 20}}}%%
flowchart LR
    %% Styling
    classDef record fill:#cfe2f3,stroke:#1c4587,stroke-width:2px
    classDef junction fill:#fff2cc,stroke:#7f6000,stroke-width:2px
    classDef master fill:#d9ead3,stroke:#38761d,stroke-width:2px
    classDef pk underline

    %% === Domain Records ===
    subgraph DOM["DOMAIN RECORDS"]
        direction TB
        KAR["<b>tb_karyawan</b><br/>━━━━━━━━━━<br/><u>id_karyawan</u><br/>nama_karyawan<br/>username<br/>password<br/>email<br/>id_level_karyawan →<br/>no_telp<br/>gaji_pokok<br/>is_aktif"]:::record

        PLG["<b>tb_pelanggan</b><br/>━━━━━━━━━━<br/><u>id_pelanggan</u><br/>kode_pelanggan<br/>nama_pelanggan<br/>no_telp<br/>alamat<br/>poin<br/>total_transaksi<br/>is_aktif"]:::record

        LAY["<b>tb_layanan</b><br/>━━━━━━━━━━<br/><u>id_layanan</u><br/>nama_layanan<br/>harga_per_kg<br/>is_aktif"]:::record

        INV["<b>tb_inventaris</b><br/>━━━━━━━━━━<br/><u>id_inventaris</u><br/>nama_barang<br/>stok<br/>satuan<br/>harga_beli<br/>stok_minimal<br/>is_aktif"]:::record

        TRX["<b>tb_transaksi</b> [soft delete]<br/>━━━━━━━━━━━━━━━━<br/><u>id_transaksi</u><br/>no_nota<br/>id_pelanggan →<br/>id_kasir →<br/>tgl_masuk<br/>tgl_selesai_estimasi<br/>tgl_selesai_aktual<br/>total_berat<br/>total_biaya<br/>diskon<br/>poin_ditebus<br/>poin_diperoleh<br/>grand_total<br/>id_status_transaksi →<br/>id_status_pembayaran →<br/>id_metode_pembayaran →<br/>pin_pengambilan<br/>deleted_at"]:::record

        DET["<b>tb_detail_transaksi</b><br/>━━━━━━━━━━━━━━━━<br/><u>id_detail</u><br/>id_transaksi →<br/>id_layanan →<br/>berat<br/>harga_satuan<br/>subtotal"]:::record

        PBY["<b>tb_pembayaran</b><br/>━━━━━━━━━━━<br/><u>id_pembayaran</u><br/>id_transaksi →<br/>tgl_bayar<br/>nominal_bayar<br/>sisa_tagihan<br/>id_metode_pembayaran →"]:::record

        PI["<b>tb_penggunaan_inventaris</b><br/>━━━━━━━━━━━━━━━━<br/><u>id_penggunaan</u><br/>id_transaksi →<br/>id_inventaris →<br/>jumlah_pakai<br/>keterangan"]:::record

        SLG["<b>tb_status_log</b><br/>━━━━━━━━━━<br/><u>id_log</u><br/>id_transaksi →<br/>id_karyawan →<br/>status_lama<br/>status_baru<br/>tgl_ubah<br/>catatan"]:::record

        TMP["<b>tb_temp_keranjang</b><br/>━━━━━━━━━━━━━━━━<br/><u>id_temp</u><br/>id_pelanggan → (NULL OK)<br/>id_kasir →<br/>id_layanan →<br/>berat"]:::record

        LIA["<b>tb_log_aktivitas</b><br/>━━━━━━━━━━━━<br/><u>id_log</u><br/>id_karyawan → (NULL)<br/>nama_karyawan<br/>level_karyawan<br/>modul<br/>aksi<br/>id_referensi<br/>nama_referensi<br/>detail (JSON)<br/>ip_address<br/>created_at"]:::record

        LW["<b>tb_log_whatsapp</b><br/>━━━━━━━━━━<br/><u>id_log</u><br/>no_nota<br/>no_tujuan<br/>isi_pesan<br/>tipe_notifikasi<br/>provider<br/>id_status_whatsapp →<br/>respon_api<br/>created_at"]:::record

        LI["<b>tb_layanan_inventaris</b><br/>━━━━━━━━━━━━━━━━<br/>[JUNCTION]<br/><u>id</u><br/>id_layanan →<br/>id_inventaris →<br/>jumlah_default"]:::junction
    end

    %% === Lookup Tables ===
    subgraph MAST["LOOKUP TABLES (tb_master_*)"]
        direction TB
        MLV["<b>tb_master_<br/>level_karyawan</b><br/>━━━━━━━━━<br/><u>id</u><br/>nama_level"]:::master
        MST["<b>tb_master_<br/>status_transaksi</b><br/>━━━━━━━━━<br/><u>id</u><br/>nama_status"]:::master
        MSP["<b>tb_master_<br/>status_pembayaran</b><br/>━━━━━━━━━<br/><u>id</u><br/>nama_status"]:::master
        MMT["<b>tb_master_<br/>metode_pembayaran</b><br/>━━━━━━━━━<br/><u>id</u><br/>nama_metode"]:::master
        MSW["<b>tb_master_<br/>status_whatsapp</b><br/>━━━━━━━━━<br/><u>id</u><br/>nama_status"]:::master
    end

    %% === Relations (1:N cardinalities) ===
    KAR -- 1..N --> TRX
    KAR -- 1..N --> TMP
    KAR -- 1..N --> SLG
    KAR -- 0..N --> LIA
    PLG -- 0..N --> TRX
    PLG -- 0..N --> TMP
    LAY -- 1..N --> DET
    LAY -- 1..N --> TMP
    INV -- 1..N --> PI
    TRX -- 1..N --> DET
    TRX -- 1..N --> PBY
    TRX -- 1..N --> PI
    TRX -- 1..N --> SLG
    TRX -- 1..N --> LW

    %% Junction (M:N resolution)
    LAY -- "M:N" --> LI
    INV -- "M:N" --> LI

    %% Lookup FKs
    MLV -- 1..N --> KAR
    MST -- 1..N --> TRX
    MSP -- 1..N --> TRX
    MMT -- 1..N --> TRX
    MMT -- 1..N --> PBY
    MSW -- 1..N --> LW
```

---

## Catatan Render

- **Mermaid versi minimum:** 10.x (untuk `flowchart` dengan styling class & `erDiagram` dengan atribut).
- **CDN default:** `https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js`
- **Untuk export:** gunakan [mermaid.live](https://mermaid.live) atau CLI `mmdc -i file.md -o output.png`.
- **Update berikutnya** jika ada perubahan arsitektur: edit file ini + buka `mermaid_renderer.html` untuk live preview.
