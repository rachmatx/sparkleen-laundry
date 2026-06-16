# Sparkleen Laundry Management System — Architecture Description

> **Target audience:** AI Diagram Generators (system architecture, data flow, ERD)
> **Framework:** CodeIgniter 4 (PHP MVC) · **Database:** MySQL · **Runtime:** Apache/XAMPP
> **PHP:** 8.2+ · **Package manager:** Composer

---

## 1. HIGH-LEVEL SYSTEM OVERVIEW

Sparkleen is a **web-based laundry management system** running on CodeIgniter 4 (PHP MVC). It is a single-server, monolithic application built on the classic server-rendered template pattern (PHP + AdminLTE 4). The system is **hybrid I/O**: most browser traffic is synchronous HTTP form submissions and full page loads, but a small but growing set of endpoints return **JSON via AJAX** (live counters, live search, quick view, public order tracking, WhatsApp manual resend). An optional outbound HTTP call is made to a third-party WhatsApp API provider (Fonnte or Wablas) for customer notifications.

**Three user roles exist, each with a scoped dashboard and access level, plus one anonymous public surface:**

- `kasir` (cashier) — creates transactions, manages orders, processes payments
- `admin` — all kasir rights + manages master data (customers, employees, inventory, services) + reports
- `pemilik` (owner) — read-only analytics, financial overview, audit logs, backups
- **public (no auth)** — order tracking page at `/lacak` (rate-limited)

The application is structured in three layers: **Controller → Service / Library → Model → Database**, with global `AuthFilter` + route-scoped `RoleFilter` enforcing access, and helper functions consolidating cross-cutting UI/logging logic.

---

## 2. COMPONENT CATALOGUE

### 2.1 Entry Point & Routing Layer

| Component      | File                         | Responsibility                                                                 |
| -------------- | ---------------------------- | ------------------------------------------------------------------------------ |
| Apache / XAMPP | `public/.htaccess`           | Rewrites all HTTP requests to `public/index.php`                               |
| Bootstrap      | `public/index.php`           | Loads CodeIgniter core; dispatches to Router                                   |
| Router         | `app/Config/Routes.php`      | Maps URI patterns to Controller::method pairs; declares route-level filters   |
| AuthFilter     | `app/Filters/AuthFilter.php` | Global before-filter: redirects unauthenticated requests to `/login`           |
| RoleFilter     | `app/Filters/RoleFilter.php` | Route-scoped filter: accepts `role:admin,kasir,pemilik` arguments; returns 403 JSON for AJAX, redirect for normal requests |

### 2.2 Controllers (Request Handlers)

| Controller       | File                             | Manages                                                                                |
| ---------------- | -------------------------------- | -------------------------------------------------------------------------------------- |
| `Auth`           | `Controllers/Auth.php`           | Login form, credential verification, logout, session creation                          |
| `Dashboard`      | `Controllers/Dashboard.php`      | Role-branched dashboard views (kasir / admin / owner) + live-status JSON polling API   |
| `Transaksi`      | `Controllers/Transaksi.php`      | Full transaction lifecycle: cart → save → status update → payment + AJAX detail & search |
| `Pelanggan`      | `Controllers/Pelanggan.php`      | Customer CRUD, toggle active, AJAX live search                                         |
| `Karyawan`       | `Controllers/Karyawan.php`       | Employee CRUD (admin/owner), AJAX live search                                          |
| `Layanan`        | `Controllers/Layanan.php`        | Service CRUD + service–inventory recipe (resep) management, AJAX live search          |
| `Inventaris`     | `Controllers/Inventaris.php`     | Inventory CRUD, stock management, low-stock warnings, AJAX stock check                  |
| `Laporan`        | `Controllers/Laporan.php`        | Transaction reports (rentang waktu) + PDF/Excel export                                |
| `LogAktivitas`   | `Controllers/LogAktivitas.php`   | System audit log viewer (owner only)                                                   |
| `LogWhatsapp`    | `Controllers/LogWhatsapp.php`    | WhatsApp delivery log viewer (all logged-in roles)                                     |
| `Lacak`          | `Controllers/Lacak.php`          | **Public** order tracking page (no auth, IP rate-limited)                              |
| `Profile`        | `Controllers/Profile.php`        | Authenticated user profile & password change                                           |
| `Backup`         | `Controllers/Backup.php`         | Database SQL backup download (owner only)                                               |
| `Home`           | `Controllers/Home.php`           | Redirect stub to login                                                                 |
| `BaseController` | `Controllers/BaseController.php` | Shared parent: loads helpers, initializes request/response objects                     |

### 2.3 Models (Database Access Layer)

Each model wraps a single MySQL table using CodeIgniter's `Model` base class with built-in query builder. All domain models are prefixed with `M`; all lookup-table models are prefixed with `Master`.

| Model                         | Table                          | Key Custom Methods                                                                                  |
| ----------------------------- | ------------------------------ | --------------------------------------------------------------------------------------------------- |
| `MKaryawan`                   | `tb_karyawan`                  | `getUserByIdentifier()` — login by username OR email                                                |
| `MPelanggan`                  | `tb_pelanggan`                 | `generateKode()` — auto-generates customer code (PLGxxxx)                                           |
| `MLayanan`                    | `tb_layanan`                   | —                                                                                                   |
| `MInventaris`                 | `tb_inventaris`                | `kurangiStok()`, `tambahStok()`, `getStokKritis()`, `getSemuaInventaris()`                          |
| `MLayananInventaris`          | `tb_layanan_inventaris`        | Recipe linking service ↔ inventory items                                                            |
| `MTransaksi`                  | `tb_transaksi`                 | `generateNota()`, `getTransaksiWithJoins()`, `buildPencarianQuery()`, `prosesTransaksiBaru()`; **uses `useSoftDeletes=true`** with `deleted_at` field |
| `MDetailTransaksi`            | `tb_detail_transaksi`          | —                                                                                                   |
| `MPembayaran`                 | `tb_pembayaran`                | —                                                                                                   |
| `MPenggunaanInventaris`       | `tb_penggunaan_inventaris`     | `getByTransaksi()`                                                                                  |
| `MStatusLog`                  | `tb_status_log`                | Plain CRUD; read by `Lacak` & `Dashboard` to surface status timeline                               |
| `MTempCart`                   | `tb_temp_keranjang` *(renamed from `tb_temp_peminjaman`)* | Temporary cart storage per kasir session; `id_pelanggan` is now nullable (cart can be built before customer is chosen) |
| `MLogAktivitas`               | `tb_log_aktivitas`             | `getDaftarLog(modul, aksi, dari, sampai, search, limit, offset)` — filterable audit log retrieval   |
| `MLogWhatsapp`                | `tb_log_whatsapp`              | `getDaftarLog(status, tipe, dari, sampai, search, limit, offset)` — filterable WA delivery log     |
| `MasterStatusTransaksiModel`  | `tb_master_status_transaksi`   | Lookup: `menunggu`, `proses`, `setrika`, `selesai`, `diambil`                                      |
| `MasterStatusPembayaranModel` | `tb_master_status_pembayaran`  | Lookup: `belum_lunas`, `cicil`, `lunas`                                                             |
| `MasterMetodePembayaranModel` | `tb_master_metode_pembayaran`  | Lookup: `tunai`, `transfer`, `qris`, etc.                                                           |
| `MasterStatusWhatsappModel`   | `tb_master_status_whatsapp`    | Lookup: `sukses`, `gagal`, `pending`                                                                |
| `MasterLevelKaryawanModel`    | `tb_master_level_karyawan`     | Lookup: `kasir`, `admin`, `pemilik`                                                                 |

### 2.4 Services (Application Layer)

| Service           | File                            | Responsibility                                                                                                                   |
| ----------------- | ------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| `DashboardService`| `Services/DashboardService.php` | Centralizes all dashboard aggregation queries (chart 7-day revenue, top services, kasir ranking, low stock, jatuh-tempo, etc.). Consumed by `Dashboard` controller to keep controllers thin. |

### 2.5 Libraries (Custom Services / Integrations)

| Library            | File                             | Responsibility                                                                                                                                                                |
| ------------------ | -------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `WhatsAppNotifier` | `Libraries/WhatsAppNotifier.php` | Sends outbound HTTP POST to Fonnte or Wablas API; builds `wa.me` fallback links; **writes a delivery row to `tb_log_whatsapp` for every attempt** (sukses/gagal/pending). Supports 3 notification types: `order_baru`, `order_selesai`, `order_diambil`. Messages embed the customer's `pin_pengambilan` and a `/lacak?kode={nota}` deep-link. Uses cURL (`Config\Services::curlrequest`) with 15s timeout, 10s connect timeout; SSL verification is disabled outside `production` env. |
| `LoginThrottle`    | `Libraries/LoginThrottle.php`    | Brute-force protection using CodeIgniter Cache (file/redis); tracks failed attempts per IP and per username                                                                   |

Both libraries are registered as named services in `app/Config/Services.php` and consumed via `service('whatsapp')` and `service('loginThrottle')`.

### 2.6 Helpers (Cross-cutting Utilities)

| Helper                     | File                                    | Purpose                                                                                            |
| -------------------------- | --------------------------------------- | -------------------------------------------------------------------------------------------------- |
| `activity_log_helper.php`  | `log_aktivitas($modul, $aksi, $idRef, $namaRef, $detail)` | Writes audit rows to `tb_log_aktivitas` from any controller; auto-fills user, IP, level.       |
| `password_helper.php`      | `password_validation_error()`           | Shared validation rules for password & confirm fields.                                             |
| `shift_helper.php`         | `deteksi_shift($datetime)`              | Maps a datetime to a working shift label (Pagi/Siang/Malam).                                       |
| `ui_helper.php`            | `get_pending_trx_count()` etc.          | UI badge/indicator helpers (pending count, etc.) queried directly via DB builder.                 |
| `validation_helper.php`    | `cekTypoEmail($email)`                  | Detects common email-domain typos (e.g. `gmial.com` → `gmail.com`).                               |

### 2.7 Views (Presentation Layer)

Views are pure PHP template files concatenated by controllers into a full HTML response. The standard backend layout pattern is:

```
header.php  +  sidebar.php  +  [page-specific view]  +  footer.php
```

| View Group       | Path                          | Description                                                                                  |
| ---------------- | ----------------------------- | -------------------------------------------------------------------------------------------- |
| Backend Template | `Views/backend/template/`     | Shared header (AdminLTE 4 CSS/JS), sidebar navigation, footer                                |
| Auth             | `Views/auth/`                 | Login page                                                                                   |
| Dashboard        | `Views/dashboard/`            | `kasir.php`, `admin.php`, `owner.php`                                                        |
| Transaksi        | `Views/transaksi/`            | `create`, `daftar`, `detail`, `nota`, `ubah_status`, `pembayaran`                            |
| Pelanggan        | `Views/pelanggan/`            | index, create/edit forms                                                                     |
| Karyawan         | `Views/karyawan/`             | index, create/edit forms                                                                     |
| Layanan          | `Views/layanan/`              | index, create/edit, resep (recipe) management                                                |
| Inventaris       | `Views/inventaris/`           | index, create/edit, warning (low stock)                                                      |
| Laporan          | `Views/laporan/`              | `index`, `rentang`, `export_rentang_pdf` templates                                            |
| Log Aktivitas    | `Views/log_aktivitas/`        | Audit log table (owner)                                                                      |
| Log WhatsApp     | `Views/log_whatsapp/`         | WhatsApp delivery log table                                                                  |
| Lacak (public)   | `Views/lacak/`                | Public 5-step tracking UI with timeline & masked customer info                                |
| Profile          | `Views/profile/`              | User profile + change-password form                                                           |
| Backup           | `Views/backup/`               | Backup download UI (owner)                                                                   |
| Partials         | `Views/partials/`             | Reusable fragments                                                                           |
| Errors           | `Views/errors/`               | HTTP error pages                                                                             |

### 2.8 Config Layer

| Config File               | Purpose                                                                                       |
| ------------------------- | --------------------------------------------------------------------------------------------- |
| `Config/Routes.php`       | All URI-to-controller mappings; declares per-route `role:` filter arguments                   |
| `Config/Filters.php`      | Registers `AuthFilter` as a global before-filter                                              |
| `Config/WhatsApp.php`     | `enabled`, `provider` (fonnte/wablas/link), `apiToken`, `apiUrl`, `businessName`              |
| `Config/AuthThrottle.php` | `maxAttempts`, `lockoutSeconds`, `showRemainingAttempts`                                      |
| `Config/Database.php`     | MySQL DSN, credentials                                                                        |
| `Config/Services.php`     | Registers `WhatsAppNotifier` and `LoginThrottle` as named services                            |
| `.env`                    | Overrides config values (DB password, WA token, app env)                                      |

### 2.9 External Systems

| External System                    | Integration Type    | Direction                                                       |
| ---------------------------------- | ------------------- | --------------------------------------------------------------- |
| **Fonnte API** (`api.fonnte.com`)  | HTTPS POST via cURL | Outbound only — sends WhatsApp messages                        |
| **Wablas API** (`solo.wablas.com`) | HTTPS POST via cURL | Outbound only — alternative WA provider                        |
| **wa.me**                          | Hyperlink redirect  | Outbound only — manual WhatsApp fallback (no API call)          |
| **Browser → /lacak**               | GET (public)        | Inbound — anonymous order tracking                              |

---

## 3. DATABASE SCHEMA & ENTITY RELATIONSHIPS

### 3.1 Lookup Tables (`tb_master_*`)

The system intentionally **does not use `ENUM` columns**. Status & type values are stored in dedicated lookup tables and joined by ID. This allows runtime updates without `ALTER TABLE`.

| Table                              | Columns                                  | Seed Values                                                |
| ---------------------------------- | ---------------------------------------- | ---------------------------------------------------------- |
| `tb_master_level_karyawan`         | `id` (PK), `nama_level`                  | `kasir`, `admin`, `pemilik`                                |
| `tb_master_status_transaksi`       | `id` (PK), `nama_status`                 | `menunggu`, `proses`, `setrika`, `selesai`, `diambil`      |
| `tb_master_status_pembayaran`      | `id` (PK), `nama_status`                 | `belum_lunas`, `cicil`, `lunas`                            |
| `tb_master_metode_pembayaran`      | `id` (PK), `nama_metode`                 | `tunai`, `transfer`, `qris`                                |
| `tb_master_status_whatsapp`        | `id` (PK), `nama_status`                 | `sukses`, `gagal`, `pending`                               |

`MasterSeeder` populates these on first migration.

### 3.2 Domain Tables

#### `tb_karyawan` (Employee / System User)

```
id_karyawan (PK)
nama_karyawan, username, password (bcrypt), email
id_level_karyawan (FK → tb_master_level_karyawan)
no_telp, gaji_pokok, is_aktif
created_at, updated_at
```

#### `tb_pelanggan` (Customer)

```
id_pelanggan (PK)
kode_pelanggan (e.g. PLG0001), nama_pelanggan
no_telp (unique), alamat
poin (loyalty points balance), total_transaksi (cumulative spend)
is_aktif
created_at, updated_at
```

#### `tb_layanan` (Service / Laundry Type)

```
id_layanan (PK)
nama_layanan, harga_per_kg
is_aktif
```

#### `tb_inventaris` (Inventory / Raw Material)

```
id_inventaris (PK)
nama_barang, stok (DECIMAL), satuan
harga_beli, stok_minimal
is_aktif
created_at, updated_at
```

#### `tb_layanan_inventaris` (Service–Inventory Recipe)

```
id (PK)
id_layanan (FK → tb_layanan)
id_inventaris (FK → tb_inventaris)
jumlah_default (quantity of material used per kg of this service)
```

#### `tb_transaksi` (Transaction / Order Header) — **soft delete enabled**

```
id_transaksi (PK)
no_nota (e.g. LAU/20260526/0001)
id_pelanggan (FK → tb_pelanggan, NULL when temporary)
id_kasir (FK → tb_karyawan)
tgl_masuk, tgl_selesai_estimasi, tgl_selesai_aktual
total_berat, total_biaya, diskon
poin_ditebus, poin_diperoleh, grand_total
id_status_transaksi (FK → tb_master_status_transaksi)
id_status_pembayaran (FK → tb_master_status_pembayaran)
id_metode_pembayaran (FK → tb_master_metode_pembayaran, NULL until paid)
pin_pengambilan (VARCHAR 4)        -- 4-digit pickup PIN
created_at, updated_at, deleted_at  -- soft delete
```

> Rows are never hard-deleted; `MTransaksi::$useSoftDeletes = true`. `delete()` sets `deleted_at = NOW()` and queries auto-filter it out.

#### `tb_detail_transaksi` (Transaction Line Items)

```
id_detail (PK)
id_transaksi (FK → tb_transaksi)
id_layanan (FK → tb_layanan)
berat, harga_satuan, subtotal
```

#### `tb_pembayaran` (Payment Records — supports installments)

```
id_pembayaran (PK)
id_transaksi (FK → tb_transaksi)
tgl_bayar, nominal_bayar, sisa_tagihan
id_metode_pembayaran (FK → tb_master_metode_pembayaran)
```

#### `tb_penggunaan_inventaris` (Inventory Usage Per Transaction)

```
id_penggunaan (PK)
id_transaksi (FK → tb_transaksi)
id_inventaris (FK → tb_inventaris)
jumlah_pakai, keterangan
```

#### `tb_status_log` (Transaction Status Change Audit)

```
id_log (PK)
id_transaksi (FK → tb_transaksi)
id_karyawan (FK → tb_karyawan)
status_lama, status_baru, tgl_ubah, catatan (a.k.a. keterangan)
```

> Has its own model now (`MStatusLog`); accessed by `Lacak` (public tracking timeline) and `Transaksi::detail`.

#### `tb_temp_keranjang` (Shopping Cart — renamed from `tb_temp_peminjaman`)

```
id_temp (PK)
id_pelanggan (FK → tb_pelanggan, **NULLABLE** — can build cart before picking customer)
id_kasir (FK → tb_karyawan)
id_layanan (FK → tb_layanan)
berat
```

> Nullable FK is the workaround for the previous checkout-blocking FK constraint issue; backfilled by migration `2026-06-11-034605_AlterTempKeranjangNullableIdPelanggan`.

#### `tb_log_aktivitas` (System-wide Audit Log)

```
id_log (PK)
id_karyawan (nullable FK → tb_karyawan)
nama_karyawan, level_karyawan (VARCHAR — denormalized snapshot)
modul (VARCHAR), aksi (VARCHAR), id_referensi, nama_referensi
detail (JSON string)
ip_address
created_at
```

#### `tb_log_whatsapp` (WhatsApp Delivery Log)

```
id_log (PK)
no_nota (VARCHAR 50, indexed)
no_tujuan (VARCHAR 20, indexed)
isi_pesan (TEXT)
tipe_notifikasi (VARCHAR 50)         -- order_baru | order_selesai | order_diambil
provider (VARCHAR 50)                -- fonnte | wablas | link
id_status_whatsapp (FK → tb_master_status_whatsapp)   -- sukses | gagal | pending
respon_api (TEXT, nullable)          -- raw response body or error reason
created_at (DATETIME, indexed)
```

> Every call into `WhatsAppNotifier` produces exactly one row here (success, failure, or manual-link). Read back via `LogWhatsapp::index` (all roles) and surfaced on owner dashboard.

### 3.3 Entity Relationship Summary

```
tb_karyawan  1──────n  tb_transaksi        (id_kasir)
tb_karyawan  1──────n  tb_temp_keranjang   (id_kasir)
tb_karyawan  1──────n  tb_status_log       (id_karyawan)
tb_karyawan  1──────n  tb_log_aktivitas    (id_karyawan, nullable)
tb_karyawan  1──────n  tb_status_log       (id_karyawan)  [actor on status change]

tb_pelanggan 1──────n  tb_transaksi        (id_pelanggan, nullable)
tb_pelanggan 1──────n  tb_temp_keranjang   (id_pelanggan, nullable)

tb_layanan   1──────n  tb_detail_transaksi (id_layanan)
tb_layanan   1──────n  tb_temp_keranjang   (id_layanan)
tb_layanan   n──────n  tb_inventaris       [via tb_layanan_inventaris]

tb_transaksi 1──────n  tb_detail_transaksi       (id_transaksi)
tb_transaksi 1──────n  tb_pembayaran             (id_transaksi)
tb_transaksi 1──────n  tb_penggunaan_inventaris  (id_transaksi)
tb_transaksi 1──────n  tb_status_log             (id_transaksi)
tb_transaksi 1──────n  tb_log_whatsapp           (no_nota, logical FK)

tb_inventaris 1─────n  tb_penggunaan_inventaris  (id_inventaris)
tb_inventaris n─────n  tb_layanan                [via tb_layanan_inventaris]

tb_master_level_karyawan    1─────n  tb_karyawan              (id_level_karyawan)
tb_master_status_transaksi  1─────n  tb_transaksi             (id_status_transaksi)
tb_master_status_pembayaran 1─────n  tb_transaksi             (id_status_pembayaran)
tb_master_status_pembayaran 1─────n  tb_pembayaran            (id_status_pembayaran)
tb_master_metode_pembayaran 1─────n  tb_transaksi             (id_metode_pembayaran)
tb_master_metode_pembayaran 1─────n  tb_pembayaran            (id_metode_pembayaran)
tb_master_status_whatsapp   1─────n  tb_log_whatsapp          (id_status_whatsapp)
```

---

## 4. DATA FLOW DESCRIPTIONS

### 4.1 Authentication Flow

```
Browser ──POST /doLogin──► Auth::doLogin()
                                │
                                ├─► LoginThrottle::isBlocked(ip, username)
                                │       └─► CI Cache (file-based)
                                │           if BLOCKED ──► MLogAktivitas::save() → redirect /login
                                │
                                ├─► MKaryawan::getUserByIdentifier(username|email)
                                │       └─► SELECT FROM tb_karyawan
                                │
                                ├─► password_verify(input, hash)
                                │       if FAIL ──► LoginThrottle::recordFailure()
                                │                   MLogAktivitas::save() → redirect /login
                                │
                                ├─► LoginThrottle::clear(ip, username)
                                ├─► session()->set([id_karyawan, nama, level, isLoggedIn])
                                ├─► MLogAktivitas::save('Login berhasil')
                                └─► redirect /dashboard
```

### 4.2 Authorization Flow (Two-Stage)

```
HTTP request
   │
   ▼
AuthFilter (global before-filter)
   │  if !session('isLoggedIn') → redirect /login
   ▼
Router dispatches URI to Controller::method
   │
   ▼
RoleFilter (route-scoped, only when URI is in a `role:` group)
   │  arguments: e.g. ['admin', 'pemilik']
   │  if session('level') ∉ arguments:
   │      if AJAX → 403 JSON
   │      else    → flashdata error + redirect()->back()
   ▼
Controller::method executes
```

### 4.3 Dashboard Rendering Flow (Role-Branched, Service-Driven)

```
Browser ──GET /dashboard──► AuthFilter
                                │
                            Dashboard::index()
                                │
                    switch(session level)
                    ├─ 'kasir'   ──► dashboardKasir()
                    │               ├─ DashboardService::getKasirData()
                    │               │    ├─ MTransaksi: 7-day chart
                    │               │    ├─ MTransaksi: status counters
                    │               │    ├─ MTransaksi: 5 latest
                    │               │    └─ MTransaksi: jatuh-tempo list
                    │               └─ render dashboard/kasir.php
                    │
                    ├─ 'admin'   ──► dashboardAdmin()
                    │               ├─ DashboardService::getAdminData()
                    │               │    ├─ chart 7-day, status counters
                    │               │    ├─ MInventaris::getStokKritis()
                    │               │    ├─ MStatusLog: 10 recent changes
                    │               │    └─ MLogWhatsapp: success/fail ratio
                    │               └─ render dashboard/admin.php
                    │
                    └─ 'pemilik' ──► dashboardOwner()
                                    ├─ DashboardService::getOwnerData()
                                    │    ├─ monthly revenue aggregation
                                    │    ├─ MKaryawan: total salary SUM
                                    │    ├─ tb_log_aktivitas: inventory cost
                                    │    ├─ tb_pembayaran: method breakdown
                                    │    ├─ MPelanggan: new customer count
                                    │    ├─ MDetailTransaksi: top 5 services
                                    │    └─ MTransaksi: top 5 kasir performance
                                    └─ render dashboard/owner.php

Browser ──GET /dashboard/api/status-kasir──► AuthFilter
                                                │
                                            Dashboard::apiStatusKasir()
                                                ├─ reject if not AJAX
                                                └─ return JSON counters
                                                   (polled every 30s by kasir.php)
```

### 4.4 Transaction Creation Flow (3-Step Wizard, with DB Transaction)

```
── STEP 1: Select Customer (or skip and add to temp cart) ──────────
Browser ──GET /transaksi──► Transaksi::index()
                                └─► MPelanggan: findAll(is_aktif=1)
                                └─► MTempCart: load current cart (nullable id_pelanggan)
                                └─► render transaksi/create.php  [grid layout: form | struk]

── STEP 2: Select Services & Inventory ─────────────────────────────
Browser ──POST /transaksi/addToCart──► Transaksi::addToCart()
    ├─ MTempCart::save({id_pelanggan: NULL OK, id_kasir, id_layanan, berat})
    └─► redirect back

Browser ──POST /transaksi/removeFromCart/{id_temp}──► Transaksi::removeFromCart()
    ├─ MTempCart::delete(id_temp)
    └─► redirect back

── STEP 3: Confirm & Save (atomic via MTransaksi::prosesTransaksiBaru) ─
Browser ──POST /transaksi/simpanTransaksi──► Transaksi::simpanTransaksi()
    │
    └─► MTransaksi::prosesTransaksiBaru(postData, id_kasir, itemsTemp, pelanggan)
            │  $db->transBegin()
            │
            ├─ CALCULATE:
            │   total_berat  = SUM(berat)
            │   total_biaya  = SUM(berat × harga_per_kg)
            │   diskon       = poin_ditebus × 300   (capped at total_biaya)
            │   grand_total  = total_biaya - diskon
            │   poin_diperoleh = FLOOR(grand_total / 10000)
            │   pin_pengambilan = random_int(1000, 9999)  -- 4-digit PIN
            │
            ├─ MTransaksi::save(header)  →  id_transaksi
            │
            ├─ MPelanggan::update(id_pelanggan):
            │       poin = poin - poin_ditebus + poin_diperoleh
            │       total_transaksi += grand_total
            │
            ├─ MDetailTransaksi::save() ×N  (one row per cart item)
            │
            ├─ FOR EACH inventory item in POST (inv_id[], inv_jumlah_{id}):
            │   ├─ MPenggunaanInventaris::save()
            │   └─ MInventaris::kurangiStok()
            │
            ├─ MTempCart::delete(WHERE id_pelanggan AND id_kasir)
            │
            ├─ WhatsAppNotifier::notifyOrderBaru(transaksi, pelanggan)
            │   ├─ builds text (embeds pin_pengambilan & /lacak deep-link)
            │   ├─ if provider='fonnte' → POST api.fonnte.com/send
            │   ├─ if provider='wablas' → POST solo.wablas.com/api/send-message
            │   └─ ALWAYS: insert row into tb_log_whatsapp
            │              ({no_nota, no_tujuan, isi_pesan, tipe_notifikasi,
            │                provider, id_status_whatsapp, respon_api})
            │
            ├─ log_aktivitas('transaksi', 'simpan', id_transaksi, no_nota, ...)
            │
            ├─ $db->transCommit()  (or transRollback on exception)
            │
            └─► redirect /transaksi/nota/{id_transaksi}
```

### 4.5 Transaction Status Update Flow

```
Browser ──POST /transaksi/prosesUbahStatus──► Transaksi::prosesUbahStatus()
    ├─ MTransaksi::update(id_transaksi, {id_status_transaksi: status_baru})
    │
    ├─ MStatusLog::save({id_transaksi, id_karyawan, status_lama, status_baru, tgl_ubah, catatan})
    │
    ├─ if status_baru == 'selesai':
    │   ├─ MTransaksi::update({tgl_selesai_aktual: NOW()})
    │   └─ WhatsAppNotifier::notifyOrderSelesai()  [+ WA log row]
    │
    ├─ if status_baru == 'diambil':
    │   └─ WhatsAppNotifier::notifyOrderDiambil()  [+ WA log row]
    │
    └─► redirect /transaksi/detail/{id_transaksi}
```

### 4.6 Payment Flow (Supports Installments)

```
Browser ──POST /transaksi/simpanPembayaran──► Transaksi::simpanPembayaran()
    ├─ SELECT SUM(nominal_bayar) FROM tb_pembayaran WHERE id_transaksi
    ├─ sisa = grand_total - sudah_dibayar
    │
    ├─ if nominal_bayar > sisa:
    │   kembalian = nominal_bayar - sisa
    │   nominal_bayar = sisa   (cap)
    │
    ├─ MPembayaran::save({id_transaksi, tgl_bayar, nominal_bayar, sisa_tagihan, id_metode_pembayaran})
    │
    ├─ id_status_pembayaran = (sisa_after <= 0) ? 3(lunas) : 2(cicil)
    ├─ MTransaksi::update({id_status_pembayaran, id_metode_pembayaran})
    │
    └─► redirect /transaksi/pembayaran/{id_transaksi}
```

### 4.7 Inventory Stock Deduction Flow

```
Inside MTransaksi::prosesTransaksiBaru(), for each POSTed inventory line:
    1. Validate: SELECT stok FROM tb_inventaris WHERE id_inventaris = id
       If jumlah > stok  →  throw exception → transRollback
    2. MPenggunaanInventaris::save({id_transaksi, id_inventaris, jumlah_pakai})
    3. MInventaris::kurangiStok(id, jumlah)
           stok_baru = MAX(0, stok_current - jumlah)
           UPDATE tb_inventaris SET stok = stok_baru
```

### 4.8 Public Order Tracking Flow (Lacak)

```
Browser (no auth) ──GET /lacak?nota=LAU/...──► Lacak::index()
    │
    ├─ Rate-limit guard (CI Cache, 15 req / 60s per IP):
    │   if attempts >= 15 → set locked_until = time() + 60s
    │   cache key: 'lacak_throttle_' . md5(ip)
    │
    ├─ Lookup MTransaksi JOIN tb_pelanggan
    │           LEFT JOIN tb_master_status_transaksi st
    │           LEFT JOIN tb_master_status_pembayaran sp
    │           WHERE no_nota = ?
    │
    ├─ Load MDetailTransaksi JOIN tb_layanan
    │
    ├─ Load MStatusLog JOIN tb_karyawan, ORDER BY tgl_ubah DESC
    │
    ├─ Mask sensitive fields:
    │     nama_pelanggan_masked = "Jo****lo"        (maskName)
    │     no_telp_masked        = "0812****7890"    (maskPhone)
    │
    └─ render Views/lacak/index.php
        ├─ search form (nota or telp)
        ├─ if searched → 5-step timeline component:
        │     [1] menunggu → [2] proses → [3] setrika → [4] selesai → [5] diambil
        │     current step derived from id_status_transaksi
        ├─ masked customer info
        └─ detail table

Browser ──GET /lacak?telp=0812...──► Lacak::index()
    │  Normalize phone: strip non-digits, 0→62 prefix
    │  Find customer → most recent non-diambil transaksi → same masking & render
```

### 4.9 Report Export Flow (PDF & Excel)

```
Browser ──GET /laporan/export_rentang_pdf?tgl_awal=...&tgl_akhir=...──►
    Laporan::export_rentang_pdf()
        ├─ MTransaksi::buildPencarianQuery(...).findAll()
        ├─ render view('laporan/export_rentang_pdf', data)  → $html string
        ├─ Dompdf::loadHtml($html)
        ├─ Dompdf::render() + setPaper('A4', 'landscape')
        └─ stream to browser as PDF

Browser ──GET /laporan/export_rentang_excel?tgl_awal=...&tgl_akhir=...──►
    Laporan::export_rentang_excel()
        ├─ MTransaksi query (same as above)
        ├─ PhpSpreadsheet::Spreadsheet() → build sheet with headers + rows
        ├─ Xlsx::save('php://output')
        └─ stream to browser as .xlsx
```

### 4.10 WhatsApp Notification Flow (Detail, with Delivery Logging)

```
WhatsAppNotifier::notifyOrderBaru / notifyOrderSelesai / notifyOrderDiambil
    │
    ├─ Check config->enabled → if false, logDelivery(... 'pending' 'disabled') + return
    ├─ normalizePhone(no_telp): strip non-digits, 0→62, 8→62
    │
    ├─ build message text (Indonesian template, embeds:
    │       no_nota, berat, total, estimasi, **pin_pengambilan**, /lacak?kode=...)
    ├─ build wa.me fallback link (https://wa.me/{phone}?text={urlencode(msg)})
    │
    └─ switch(config->provider):
        ├─ 'link' or empty apiToken:
        │       logDelivery(... 'link' 'pending' 'Token API belum dikonfigurasi')
        │       return wa_link only
        │
        ├─ 'fonnte' → POST https://api.fonnte.com/send
        │              headers: Authorization: {apiToken}
        │              body: target={phone}&message={text}
        │              logDelivery(... 'fonnte' 'sukses'|'gagal' rawBody|err)
        │
        └─ 'wablas' → POST https://solo.wablas.com/api/send-message
                       headers: Authorization: {apiToken}, Content-Type: application/json
                       body: {phone, message}
                       logDelivery(... 'wablas' 'sukses'|'gagal' rawBody|err)

logDelivery()  -- always called, always inserts to tb_log_whatsapp:
    INSERT INTO tb_log_whatsapp
        (no_nota, no_tujuan, isi_pesan, tipe_notifikasi,
         provider, id_status_whatsapp, respon_api, created_at)
    id_status_whatsapp mapped from string:
        'sukses'  → 1
        'gagal'   → 2
        'pending' → 3
```

### 4.11 Login Throttle Flow

```
LoginThrottle uses CodeIgniter Cache (file-based by default).
Cache key format: login_throttle_{scope}_{md5(value)}
    scope 'ip'   → keyed by client IP address
    scope 'user' → keyed by lowercased username/email

On each failed login:
    increment(ip, 'ip')   → record['attempts']++
    increment(username, 'user')
    if attempts >= config->maxAttempts:
        record['locked_until'] = time() + config->lockoutSeconds
        cache()->save(key, record, lockoutSeconds)

isBlocked():
    return record['locked_until'] > time()

On successful login:
    LoginThrottle::clear() → cache()->delete(ip_key) + cache()->delete(user_key)
```

### 4.12 Backup Download Flow

```
Browser ──POST /backup/download──► AuthFilter → RoleFilter('pemilik')
    │
    └─► Backup::download()
        ├─ exec `mysqldump --user={DB user} --password={DB pass} {DB name}`
        ├─ headers: Content-Type: application/sql,
        │           Content-Disposition: attachment; filename="backup-YYYY-MM-DD-HHMMSS.sql"
        └─ stream raw SQL to browser
```

### 4.13 Quick View (AJAX) & Live Search (AJAX) Pattern

```
Browser ──GET /transaksi/ajaxDetail/{id}──► AuthFilter → RoleFilter
                                                │
                                            Transaksi::ajaxDetail($id)
                                                ├─ reject if !isAJAX()
                                                ├─ MTransaksi::getTransaksiWithJoins($id)
                                                ├─ MDetailTransaksi::findAll by id_transaksi
                                                └─ return $this->response->setJSON([...])

Browser ──GET /{resource}/ajaxSearch?q=...──► AuthFilter → RoleFilter
                                                │
                                            {Resource}::ajaxSearch()
                                                ├─ reject if !isAJAX()
                                                ├─ like('%q%') on resource name/identifier
                                                └─ return JSON array (used by Select2 / DataTables)
```

### 4.14 Live Counter Polling (Kasir Dashboard)

```
Browser loads dashboard/kasir.php
    │  setInterval(30_000ms)
    ▼
fetch('/dashboard/api/status-kasir')
    │
    ▼
AuthFilter ──► Dashboard::apiStatusKasir()
                ├─ if !isAJAX → 403
                └─ return JSON { pesananMasukHariIni, statusMenunggu, statusProses,
                                 statusSetrika, totalDiproses, timestamp }
    │
    ▼
JS updates DOM counters in-place (no page reload)
```

---

## 5. CROSS-CUTTING CONCERNS

### 5.1 Session State (CodeIgniter Session)

All authentication state is stored in the PHP session:

- `isLoggedIn` (bool)
- `id_karyawan` (int)
- `nama_karyawan` (string)
- `username` (string)
- `email` (string)
- `level` (string: kasir | admin | pemilik)
- `id_pelanggan_transaksi` (int) — temporary, set during transaction wizard

### 5.2 Authorization Pattern (Two-Stage)

1. **Authentication** — `AuthFilter` (global before-filter) verifies `isLoggedIn`.
2. **Authorization** — `RoleFilter` (route-level, declared in `Routes.php` group) verifies `level ∈ arguments`. Returns 403 JSON for AJAX; sets flashdata + `redirect()->back()` for normal requests.

Inline role checks inside controller methods are now **legacy**; the route-level `role:` argument is the canonical mechanism.

### 5.3 Activity Logging (`tb_log_aktivitas`)

Written to by:
- `Auth` (login / logout / failed login)
- `Karyawan`, `Inventaris`, `Layanan` controllers via `log_aktivitas()` helper
- `Transaksi::simpanTransaksi` (new transaksi, status change)

Owner dashboard reads from it to compute inventory purchase cost estimates (parses `detail` JSON field).

### 5.4 WhatsApp Delivery Logging (`tb_log_whatsapp`)

Every call into `WhatsAppNotifier::*` (success, failure, or manual-link) writes exactly one row. Read back via `LogWhatsapp::index` (all roles) and surfaced as success/fail/pending counts on admin and owner dashboards.

### 5.5 Loyalty Points System

- **Earn:** `poin_diperoleh = FLOOR(grand_total / 10000)` per transaction
- **Redeem:** `poin_ditebus × 300 = discount in IDR` (user-input, capped at available balance)
- **Net poin update:** `poin_baru = poin - poin_ditebus + poin_diperoleh` written to `tb_pelanggan`

### 5.6 Service–Inventory Recipe System

`tb_layanan_inventaris` stores a recipe: for each (service, inventory_item) pair, it holds `jumlah_default` = quantity of material consumed **per kg** of that service. During Step 2 of transaction creation, the system multiplies `jumlah_default × berat` for each cart item to compute a recommended inventory usage quantity shown to the kasir.

### 5.7 Pickup PIN System (`pin_pengambilan`)

A 4-digit numeric PIN is auto-generated at transaction creation (`random_int(1000, 9999)`) and:
- Persisted on `tb_transaksi.pin_pengambilan`
- Embedded in WhatsApp messages (`order_baru` and `order_selesai`) for customer-PIN-rotation awareness
- Not yet used as a server-side gate; planned for pickup verification

### 5.8 Soft Deletes (`tb_transaksi`)

`MTransaksi::$useSoftDeletes = true` and `$deletedField = 'deleted_at'`. All reads through the model (or the query builder that joins to it) automatically filter out rows where `deleted_at IS NOT NULL`. Backfilled by migration `2026-06-11-030842_AddDeletedAtToTransaksi`.

### 5.9 Public Tracking Privacy

`Lacak` (public, no auth) **always masks** the customer name and phone number on the response payload before sending to the browser. Rate-limited at 15 req/60s per IP.

---

## 6. COMPONENT DEPENDENCY DIAGRAM (Text Notation)

```
Browser
  │
  ├── /  /login  /logout  ──────────► Auth Controller ──► MKaryawan
  │                                                  ──► MLogAktivitas
  │                                                  ──► LoginThrottle ──► CI Cache
  │
  ├── /lacak (public, IP rate-limited) ─► Lacak ──► MTransaksi
  │                                              ──► MDetailTransaksi
  │                                              ──► MStatusLog
  │                                              ──► CI Cache (throttle)
  │
  ├── /dashboard  /dashboard/api/status-kasir ──► Dashboard ──► DashboardService
  │                                                       ├─► MTransaksi
  │                                                       ├─► MPelanggan
  │                                                       ├─► MKaryawan
  │                                                       ├─► MInventaris
  │                                                       ├─► MLogWhatsapp
  │                                                       └─► CI DB Builder (tb_status_log, tb_log_aktivitas, tb_pembayaran)
  │
  ├── /transaksi* (role:admin,kasir) ─► Transaksi ──► MPelanggan
  │                                    │           ──► MLayanan
  │                                    │           ──► MTransaksi   (useSoftDeletes=true)
  │                                    │           ──► MDetailTransaksi
  │                                    │           ──► MTempCart
  │                                    │           ──► MInventaris
  │                                    │           ──► MPenggunaanInventaris
  │                                    │           ──► MLayananInventaris
  │                                    │           ──► MStatusLog
  │                                    │           ──► MLogAktivitas  (via helper)
  │                                    │           ──► WhatsAppNotifier
  │                                    │               ├─► [Fonnte API | Wablas API | wa.me]
  │                                    │               └─► MLogWhatsapp (insert log row)
  │                                    └─► MTransaksi::prosesTransaksiBaru (DB transaction)
  │
  ├── /laporan* (role:admin,pemilik) ─► Laporan ──► MTransaksi
  │                                  ──► Dompdf (PDF generation)
  │                                  ──► PhpSpreadsheet (Excel generation)
  │
  ├── /inventaris* ──► Inventaris ──► MInventaris
  │                ──► MLogAktivitas (via helper)
  │
  ├── /karyawan* (role:admin,pemilik) ──► Karyawan ──► MKaryawan
  │                                       ──► MLogAktivitas (via helper)
  │
  ├── /layanan* ──► Layanan ──► MLayanan
  │              ──► MLayananInventaris
  │              ──► MInventaris
  │
  ├── /log-aktivitas (role:pemilik) ──► LogAktivitas ──► MLogAktivitas
  │
  ├── /log-whatsapp (role:admin,kasir,pemilik) ──► LogWhatsapp ──► MLogWhatsapp
  │                                            ──► MasterStatusWhatsappModel
  │
  ├── /backup (role:pemilik) ──► Backup ──► shell: mysqldump
  │
  └── /profile ──► Profile ──► MKaryawan

MasterSeeder populates all tb_master_* tables.
All Models ──► MySQL (via CodeIgniter DB / Query Builder)
```

---

## 7. TECHNOLOGY STACK SUMMARY

| Layer            | Technology                                                                       |
| ---------------- | -------------------------------------------------------------------------------- |
| Language         | PHP 8.2+                                                                         |
| Framework        | CodeIgniter 4                                                                    |
| Database         | MySQL / MariaDB (via XAMPP)                                                      |
| Server           | Apache 2.4 (XAMPP)                                                               |
| Frontend CSS     | AdminLTE 4 (Bootstrap-based)                                                     |
| Frontend JS      | Vanilla JS + jQuery (AdminLTE 4 deps), Chart.js, SweetAlert2, Mermaid (renderer) |
| PDF Export       | Dompdf 3.x                                                                       |
| Excel Export     | PhpSpreadsheet 5.x                                                               |
| QR Code          | endroid/qr-code 6.x                                                              |
| WhatsApp API     | Fonnte (`api.fonnte.com`) or Wablas (`solo.wablas.com`); cURL via CURLRequest   |
| Cache            | CodeIgniter File Cache (for login throttle + lacak rate-limit)                   |
| Package Manager  | Composer                                                                         |
| Session          | PHP native session (via CI Session library)                                      |
| Testing          | PHPUnit 10.5 with `tests/_support` harness & seeder                              |
