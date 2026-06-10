# Sparkleen Laundry Management System — Architecture Description

> **Target audience:** AI Diagram Generators (system architecture, data flow, ERD)
> **Framework:** CodeIgniter 4 (PHP MVC) · **Database:** MySQL · **Runtime:** Apache/XAMPP

---

## 1. HIGH-LEVEL SYSTEM OVERVIEW

Sparkleen is a **web-based laundry management system** running on CodeIgniter 4 (PHP MVC). It is a single-server, monolithic application with no frontend JavaScript framework. The browser communicates with the server exclusively through synchronous HTTP form submissions and standard page loads (no AJAX/REST API). An optional outbound HTTP call is made to a third-party WhatsApp API provider (Fonnte or Wablas) for customer notifications.

**Three user roles exist, each with a scoped dashboard and access level:**

- `kasir` (cashier) — creates transactions, manages orders, processes payments
- `admin` — all kasir rights + manages master data (customers, employees, inventory, services) + reports
- `pemilik` (owner) — read-only analytics and financial overview

---

## 2. COMPONENT CATALOGUE

### 2.1 Entry Point & Routing Layer

| Component      | File                         | Responsibility                                                         |
| -------------- | ---------------------------- | ---------------------------------------------------------------------- |
| Apache / XAMPP | `public/.htaccess`           | Rewrites all HTTP requests to `public/index.php`                       |
| Bootstrap      | `public/index.php`           | Loads CodeIgniter core; dispatches to Router                           |
| Router         | `app/Config/Routes.php`      | Maps URI patterns to Controller::method pairs                          |
| AuthFilter     | `app/Filters/AuthFilter.php` | Pre-request middleware; redirects unauthenticated requests to `/login` |

### 2.2 Controllers (Request Handlers)

| Controller       | File                             | Manages                                                            |
| ---------------- | -------------------------------- | ------------------------------------------------------------------ |
| `Auth`           | `Controllers/Auth.php`           | Login form, credential verification, logout, session creation      |
| `Dashboard`      | `Controllers/Dashboard.php`      | Role-branched dashboard views (kasir / admin / owner)              |
| `Transaksi`      | `Controllers/Transaksi.php`      | Full transaction lifecycle: cart → save → status update → payment  |
| `Pelanggan`      | `Controllers/Pelanggan.php`      | Customer CRUD and active/inactive toggling                         |
| `Karyawan`       | `Controllers/Karyawan.php`       | Employee CRUD (admin only)                                         |
| `Layanan`        | `Controllers/Layanan.php`        | Service CRUD + service–inventory recipe management                 |
| `Inventaris`     | `Controllers/Inventaris.php`     | Inventory CRUD, stock management, low-stock warnings               |
| `Laporan`        | `Controllers/Laporan.php`        | Daily/monthly reports + PDF/Excel export                           |
| `LogAktivitas`   | `Controllers/LogAktivitas.php`   | Activity audit log viewer (owner only)                             |
| `Profile`        | `Controllers/Profile.php`        | Authenticated user profile & password change                       |
| `Backup`         | `Controllers/Backup.php`         | Database backup download (admin/owner)                             |
| `Home`           | `Controllers/Home.php`           | Redirect stub to login                                             |
| `BaseController` | `Controllers/BaseController.php` | Shared parent: loads helpers, initializes request/response objects |

### 2.3 Models (Database Access Layer)

Each model wraps a single MySQL table using CodeIgniter's `Model` base class with built-in query builder.

| Model                   | Table                      | Key Custom Methods                                                         |
| ----------------------- | -------------------------- | -------------------------------------------------------------------------- |
| `MPelanggan`            | `tb_pelanggan`             | `generateKode()` — auto-generates customer code (PLGxxxx)                  |
| `MKaryawan`             | `tb_karyawan`              | `getUserByIdentifier()` — login by username OR email                       |
| `MLayanan`              | `tb_layanan`               | —                                                                          |
| `MTransaksi`            | `tb_transaksi`             | `generateNota()` — auto-generates receipt number (LAU/YYYYMMDD/xxxx)       |
| `MDetailTransaksi`      | `tb_detail_transaksi`      | —                                                                          |
| `MPembayaran`           | `tb_pembayaran`            | —                                                                          |
| `MInventaris`           | `tb_inventaris`            | `kurangiStok()`, `tambahStok()`, `getStokKritis()`, `getSemuaInventaris()` |
| `MPenggunaanInventaris` | `tb_penggunaan_inventaris` | `getByTransaksi()`                                                         |
| `MLayananInventaris`    | `tb_layanan_inventaris`    | Recipe linking service ↔ inventory items                                   |
| `MTempPeminjaman`       | `tb_temp_peminjaman`       | Temporary cart storage per kasir session                                   |
| `MLogAktivitas`         | `tb_log_aktivitas`         | `getDaftarLog()` — filterable audit log retrieval                          |

> **Note:** `tb_status_log` has no dedicated Model — it is accessed directly via the database builder in `Transaksi` and `Dashboard` controllers.

### 2.4 Libraries (Custom Services)

| Library            | File                             | Responsibility                                                                                                                                            |
| ------------------ | -------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `WhatsAppNotifier` | `Libraries/WhatsAppNotifier.php` | Sends outbound HTTP POST to Fonnte or Wablas API; builds `wa.me` fallback links for 3 notification types: order created, order completed, order picked up |
| `LoginThrottle`    | `Libraries/LoginThrottle.php`    | Brute-force protection using CodeIgniter Cache (file/redis); tracks failed attempts per IP and per username                                               |

Both libraries are registered as named services in `app/Config/Services.php` and consumed via `service('whatsapp')` and `service('loginThrottle')`.

### 2.5 Views (Presentation Layer)

Views are pure PHP template files concatenated by controllers into a full HTML response. The layout pattern is:

```
header.php  +  sidebar.php  +  [page-specific view]  +  footer.php
```

| View Group       | Path                      | Description                                                   |
| ---------------- | ------------------------- | ------------------------------------------------------------- |
| Backend Template | `Views/backend/template/` | Shared header (AdminLTE 4 CSS/JS), sidebar navigation, footer |
| Auth             | `Views/auth/`             | Login page                                                    |
| Dashboard        | `Views/dashboard/`        | `kasir.php`, `admin.php`, `owner.php`                         |
| Transaksi        | `Views/transaksi/`        | step1, step2, daftar, detail, nota, ubah_status, pembayaran   |
| Pelanggan        | `Views/pelanggan/`        | index, create/edit forms                                      |
| Karyawan         | `Views/karyawan/`         | index, create/edit forms                                      |
| Layanan          | `Views/layanan/`          | index, create/edit, resep (recipe) management                 |
| Inventaris       | `Views/inventaris/`       | index, create/edit, warning (low stock)                       |
| Laporan          | `Views/laporan/`          | harian, bulanan, export templates for PDF/Excel               |
| Log Aktivitas    | `Views/log_aktivitas/`    | Audit log table                                               |
| Partials         | `Views/partials/`         | Reusable fragments                                            |
| Errors           | `Views/errors/`           | HTTP error pages                                              |

### 2.6 Config Layer

| Config File               | Purpose                                                                          |
| ------------------------- | -------------------------------------------------------------------------------- |
| `Config/Routes.php`       | All URI-to-controller mappings                                                   |
| `Config/Filters.php`      | Registers `AuthFilter` as a global before-filter                                 |
| `Config/WhatsApp.php`     | `enabled`, `provider` (fonnte/wablas/link), `apiToken`, `apiUrl`, `businessName` |
| `Config/AuthThrottle.php` | `maxAttempts`, `lockoutSeconds`, `showRemainingAttempts`                         |
| `Config/Database.php`     | MySQL DSN, credentials                                                           |
| `Config/Services.php`     | Registers `WhatsAppNotifier` and `LoginThrottle` as named services               |
| `.env`                    | Overrides config values (DB password, WA token, app env)                         |

### 2.7 External System

| External System                    | Integration Type    | Direction                                |
| ---------------------------------- | ------------------- | ---------------------------------------- |
| **Fonnte API** (`api.fonnte.com`)  | HTTPS POST via cURL | Outbound only — sends WhatsApp messages  |
| **Wablas API** (`solo.wablas.com`) | HTTPS POST via cURL | Outbound only — alternative WA provider  |
| **wa.me**                          | Hyperlink redirect  | Outbound only — manual WhatsApp fallback |

---

## 3. DATABASE SCHEMA & ENTITY RELATIONSHIPS

### 3.1 Tables

#### `tb_karyawan` (Employee / System User)

```
id_karyawan (PK)
nama_karyawan, username, password (bcrypt), email
level: ENUM('kasir', 'admin', 'pemilik')
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

#### `tb_transaksi` (Transaction / Order Header)

```
id_transaksi (PK)
no_nota (e.g. LAU/20260526/0001)
id_pelanggan (FK → tb_pelanggan)
id_kasir (FK → tb_karyawan)
tgl_masuk, tgl_selesai_estimasi, tgl_selesai_aktual
total_berat, total_biaya, diskon
poin_ditebus, poin_diperoleh, grand_total
status: ENUM('menunggu', 'proses', 'setrika', 'selesai', 'diambil')
status_pembayaran: ENUM('belum', 'cicil', 'lunas')
metode_pembayaran
created_at, updated_at
```

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
tgl_bayar, nominal_bayar, sisa_tagihan, metode_bayar
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
status_lama, status_baru, tgl_ubah, catatan
```

#### `tb_temp_peminjaman` (Shopping Cart — temporary)

```
id_temp (PK)
id_pelanggan (FK → tb_pelanggan)
id_kasir (FK → tb_karyawan)
id_layanan (FK → tb_layanan)
berat
```

#### `tb_log_aktivitas` (System-wide Audit Log)

```
id_log (PK)
id_karyawan (nullable FK → tb_karyawan)
nama_karyawan, level_karyawan (denormalized for history)
modul, aksi, id_referensi, nama_referensi
detail (JSON string)
ip_address
created_at
```

### 3.2 Entity Relationship Summary

```
tb_karyawan  1──────n  tb_transaksi        (id_kasir)
tb_karyawan  1──────n  tb_temp_peminjaman  (id_kasir)
tb_karyawan  1──────n  tb_status_log       (id_karyawan)
tb_karyawan  1──────n  tb_log_aktivitas    (id_karyawan, nullable)

tb_pelanggan 1──────n  tb_transaksi        (id_pelanggan)
tb_pelanggan 1──────n  tb_temp_peminjaman  (id_pelanggan)

tb_layanan   1──────n  tb_detail_transaksi (id_layanan)
tb_layanan   1──────n  tb_temp_peminjaman  (id_layanan)
tb_layanan   n──────n  tb_inventaris       [via tb_layanan_inventaris]

tb_transaksi 1──────n  tb_detail_transaksi (id_transaksi)
tb_transaksi 1──────n  tb_pembayaran       (id_transaksi)
tb_transaksi 1──────n  tb_penggunaan_inventaris (id_transaksi)
tb_transaksi 1──────n  tb_status_log       (id_transaksi)

tb_inventaris 1─────n  tb_penggunaan_inventaris (id_inventaris)
tb_inventaris n─────n  tb_layanan          [via tb_layanan_inventaris]
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

### 4.2 Dashboard Rendering Flow (Role-Branched)

```
Browser ──GET /dashboard──► AuthFilter (check session isLoggedIn)
                                │
                            Dashboard::index()
                                │
                    switch(session level)
                    ├─ 'kasir'   ──► dashboardKasir()
                    │               ├─ MTransaksi: count by status
                    │               ├─ MTransaksi: 5 latest JOIN tb_pelanggan
                    │               └─ render view/dashboard/kasir.php
                    │
                    ├─ 'admin'   ──► dashboardAdmin()
                    │               ├─ MTransaksi: daily totals (7-day loop)
                    │               ├─ MInventaris::getStokKritis()
                    │               ├─ tb_status_log: 10 recent changes
                    │               └─ render view/dashboard/admin.php
                    │
                    └─ 'pemilik' ──► dashboardOwner()
                                    ├─ tb_transaksi: monthly revenue aggregation
                                    ├─ tb_karyawan: total salary SUM
                                    ├─ tb_log_aktivitas: inventory cost computation
                                    ├─ tb_pembayaran: payment method breakdown
                                    ├─ tb_pelanggan: new customer count
                                    ├─ tb_detail_transaksi: top 5 services
                                    ├─ tb_transaksi: top 5 kasir performance
                                    └─ render view/dashboard/owner.php
```

### 4.3 Transaction Creation Flow (3-Step Wizard)

```
── STEP 1: Select Customer ──────────────────────────────────────────
Browser ──GET /transaksi──► Transaksi::index()
                                └─► MPelanggan: findAll(is_aktif=1)
                                └─► render transaksi/step1.php

── STEP 2: Select Services ──────────────────────────────────────────
Browser ──GET /transaksi/pilih-layanan/{id}──► Transaksi::pilihLayanan()
    session: stores id_pelanggan_transaksi
    ├─ MTempPeminjaman: load current cart (by pelanggan + kasir)
    ├─ MLayananInventaris: for each cart item, compute ingredient totals
    │       (resepRekomendasi = SUM of jumlah_default × berat per ingredient)
    ├─ MLayanan: findAll(is_aktif=1)
    ├─ MInventaris: findAll(is_aktif=1)
    └─► render transaksi/step2.php

Browser ──POST /transaksi/addToCart──► Transaksi::addToCart()
    ├─ MTempPeminjaman::save({id_pelanggan, id_kasir, id_layanan, berat})
    └─► redirect /transaksi/pilih-layanan/{id}

Browser ──GET /transaksi/removeFromCart/{id_temp}──► Transaksi::removeFromCart()
    ├─ MTempPeminjaman::delete(id_temp)
    └─► redirect back

── STEP 3: Confirm & Save ───────────────────────────────────────────
Browser ──POST /transaksi/simpanTransaksi──► Transaksi::simpanTransaksi()
    ├─ MTempPeminjaman: load cart items
    ├─ MInventaris: validate stock sufficiency for each requested material
    │       if INSUFFICIENT ──► flashdata error, redirect to step2
    │
    ├─ CALCULATE:
    │   total_berat = SUM(berat per item)
    │   total_biaya = SUM(berat × harga_per_kg per item)
    │   diskon = poin_ditebus × 300 (capped at total_biaya)
    │   grand_total = total_biaya - diskon
    │   poin_diperoleh = FLOOR(grand_total / 10000)
    │
    ├─ MTransaksi::save(header data) → id_transaksi
    │
    ├─ MPelanggan::update(id_pelanggan):
    │       poin = poin - poin_ditebus + poin_diperoleh
    │       total_transaksi += grand_total
    │
    ├─ MDetailTransaksi::save() ×N  (one row per cart item)
    │
    ├─ FOR EACH inventory item in form POST:
    │   ├─ MPenggunaanInventaris::save({id_transaksi, id_inventaris, jumlah_pakai})
    │   └─ MInventaris::kurangiStok(id_inventaris, jumlah)
    │           └─► UPDATE tb_inventaris SET stok = stok - jumlah
    │
    ├─ MTempPeminjaman::delete(WHERE id_pelanggan AND id_kasir)  [clear cart]
    │
    ├─ WhatsAppNotifier::notifyOrderBaru(transaksi, pelanggan)
    │   ├─ build text message
    │   ├─ if provider = 'fonnte' → HTTPS POST api.fonnte.com/send
    │   ├─ if provider = 'wablas' → HTTPS POST solo.wablas.com/api/send-message
    │   └─ if provider = 'link'  → build wa.me link (no API call)
    │
    └─► redirect /transaksi/nota/{id_transaksi}
```

### 4.4 Transaction Status Update Flow

```
Browser ──POST /transaksi/prosesUbahStatus──► Transaksi::prosesUbahStatus()
    ├─ MTransaksi::update(id_transaksi, {status: status_baru})
    ├─ INSERT INTO tb_status_log {status_lama, status_baru, tgl_ubah, id_karyawan, catatan}
    │
    ├─ if status_baru == 'selesai':
    │   ├─ MTransaksi::update({tgl_selesai_aktual: NOW()})
    │   └─ WhatsAppNotifier::notifyOrderSelesai() [if kirim_wa=1]
    │
    ├─ if status_baru == 'diambil':
    │   └─ WhatsAppNotifier::notifyOrderDiambil() [if kirim_wa=1]
    │
    └─► redirect /transaksi/detail/{id_transaksi}
```

### 4.5 Payment Flow (Supports Installments)

```
Browser ──POST /transaksi/simpanPembayaran──► Transaksi::simpanPembayaran()
    ├─ SELECT SUM(nominal_bayar) FROM tb_pembayaran WHERE id_transaksi
    ├─ sisa = grand_total - sudah_dibayar
    │
    ├─ if nominal_bayar > sisa:
    │   kembalian = nominal_bayar - sisa
    │   nominal_bayar = sisa (cap to remaining balance)
    │
    ├─ INSERT INTO tb_pembayaran {id_transaksi, tgl_bayar, nominal_bayar, sisa_tagihan, metode_bayar}
    │
    ├─ status_pembayaran = (sisa_after <= 0) ? 'lunas' : 'cicil'
    ├─ MTransaksi::update({status_pembayaran, metode_pembayaran})
    │
    └─► redirect /transaksi/pembayaran/{id_transaksi}
```

### 4.6 Inventory Stock Deduction Flow

```
When Transaksi::simpanTransaksi() is called:
    For each inventory item submitted via POST (inv_id[], inv_jumlah_{id}):
        1. Validate: SELECT stok FROM tb_inventaris WHERE id_inventaris = id
        2. If jumlah > stok → abort with error
        3. MPenggunaanInventaris::save({id_transaksi, id_inventaris, jumlah_pakai})
        4. MInventaris::kurangiStok(id, jumlah)
               stok_baru = MAX(0, stok_current - jumlah)
               UPDATE tb_inventaris SET stok = stok_baru
```

### 4.7 Report Export Flow

```
Browser ──GET /laporan/export_harian_pdf?tanggal=YYYY-MM-DD──►
    Laporan::export_harian_pdf()
        ├─ MTransaksi::select().join(tb_pelanggan).where(DATE).findAll()
        ├─ render view('laporan/export_harian_pdf', data) → $html string
        ├─ Dompdf::loadHtml($html)
        ├─ Dompdf::render() + setPaper('A4', 'landscape')
        └─ stream to browser as PDF file

Browser ──GET /laporan/export_harian_excel?tanggal=YYYY-MM-DD──►
    Laporan::export_harian_excel()
        ├─ MTransaksi query (same as above)
        ├─ PhpSpreadsheet::Spreadsheet() → build sheet with headers + rows
        ├─ Xlsx::save('php://output')
        └─ stream to browser as .xlsx file
```

### 4.8 WhatsApp Notification Flow (Detail)

```
WhatsAppNotifier::notifyOrderBaru / notifyOrderSelesai / notifyOrderDiambil
    │
    ├─ Check config->enabled → if false, return {success: false}
    ├─ normalizePhone(no_telp):
    │       strip non-digits
    │       if starts with '0' → replace with '62'
    │       if starts with '8' → prepend '62'
    │
    ├─ build message text (Indonesian template with nota, berat, total, estimasi)
    ├─ build wa.me fallback link (https://wa.me/{phone}?text={urlencode(msg)})
    │
    └─ switch(config->provider):
        ├─ 'fonnte' → POST https://api.fonnte.com/send
        │               headers: Authorization: {apiToken}
        │               body: target={phone}&message={text}
        │
        ├─ 'wablas' → POST https://solo.wablas.com/api/send-message
        │               headers: Authorization: {apiToken}, Content-Type: application/json
        │               body: {phone, message}
        │
        └─ 'link' / no token → return {success: false, wa_link: {wa.me link}}
```

### 4.9 Login Throttle Flow

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

### 5.2 Authorization Pattern

Role enforcement is done **inline** at the top of each controller method:

```php
if (!in_array($this->level, ['admin', 'kasir'])) {
    session()->setFlashdata('error', '...');
    return redirect()->to('/...');
}
```

`AuthFilter` only ensures the user is logged in (session exists). Fine-grained role checks are controller-side.

### 5.3 Activity Logging (tb_log_aktivitas)

Written to by: `Auth` (login/logout/fail), `Karyawan`, `Inventaris`, `Layanan` controllers. Owner dashboard reads from it to compute inventory purchase cost estimates (parses `detail` JSON field).

### 5.4 Loyalty Points System

- **Earn:** `poin_diperoleh = FLOOR(grand_total / 10000)` per transaction
- **Redeem:** `poin_ditebus × 300 = discount in IDR` (user-input, capped at available balance)
- **Net poin update:** `poin_baru = poin - poin_ditebus + poin_diperoleh` written to `tb_pelanggan`

### 5.5 Service–Inventory Recipe System

`tb_layanan_inventaris` stores a recipe: for each (service, inventory_item) pair, it holds `jumlah_default` = quantity of material consumed **per kg** of that service. During Step 2 of transaction creation, the system multiplies `jumlah_default × berat` for each cart item to compute a recommended inventory usage quantity shown to the kasir.

---

## 6. COMPONENT DEPENDENCY DIAGRAM (Text Notation)

```
Browser
  │
  ▼
Apache (.htaccess rewrite)
  │
  ▼
public/index.php  ──► CodeIgniter 4 Core
  │
  ▼
AuthFilter ──► CI Session
  │ (pass-through if authenticated)
  ▼
Router (Config/Routes.php)
  │
  ├──► Auth Controller ──► MKaryawan
  │                    ──► MLogAktivitas
  │                    ──► LoginThrottle ──► CI Cache
  │
  ├──► Dashboard Controller ──► MTransaksi
  │                         ──► MPelanggan
  │                         ──► MKaryawan
  │                         ──► MInventaris
  │                         ──► CI DB Builder (direct: tb_status_log, tb_log_aktivitas, tb_pembayaran)
  │
  ├──► Transaksi Controller ──► MPelanggan
  │                         ──► MLayanan
  │                         ──► MTransaksi
  │                         ──► MDetailTransaksi
  │                         ──► MTempPeminjaman
  │                         ──► MInventaris
  │                         ──► MPenggunaanInventaris
  │                         ──► MLayananInventaris
  │                         ──► WhatsAppNotifier ──► [Fonnte API | Wablas API | wa.me]
  │
  ├──► Laporan Controller ──► MTransaksi
  │                       ──► Dompdf (PDF generation)
  │                       ──► PhpSpreadsheet (Excel generation)
  │
  ├──► Inventaris Controller ──► MInventaris
  │                          ──► MLogAktivitas
  │
  ├──► Karyawan Controller ──► MKaryawan
  │                        ──► MLogAktivitas
  │
  ├──► Layanan Controller ──► MLayanan
  │                       ──► MLayananInventaris
  │                       ──► MInventaris
  │
  └──► [Other Controllers] ──► [Respective Models]

All Models ──► MySQL (via CodeIgniter DB / Query Builder)
```

---

## 7. TECHNOLOGY STACK SUMMARY

| Layer           | Technology                                              |
| --------------- | ------------------------------------------------------- |
| Language        | PHP 8.x                                                 |
| Framework       | CodeIgniter 4                                           |
| Database        | MySQL (via XAMPP)                                       |
| Server          | Apache 2.4 (XAMPP)                                      |
| Frontend CSS    | AdminLTE 4 (Bootstrap-based)                            |
| PDF Export      | Dompdf                                                  |
| Excel Export    | PhpSpreadsheet                                          |
| WhatsApp API    | Fonnte (`api.fonnte.com`) or Wablas (`solo.wablas.com`) |
| Cache           | CodeIgniter File Cache (for login throttle)             |
| Package Manager | Composer                                                |
| Session         | PHP native session (via CI Session library)             |
