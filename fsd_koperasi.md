# FUNCTIONAL SPECIFICATION DOCUMENT (FSD)
## Sistem Informasi Koperasi Simpan Pinjam

---

**Dokumen:** FSD-KOPERASI-v1.0
**Tanggal:** 5 Agustus 2026
**Status:** Draft for Review
**Dibuat oleh:** Senior Business Analyst / System Architect

---

# DAFTAR ISI

1. [Gambaran Umum Sistem](#1-gambaran-umum-sistem)
2. [Global Business Rules](#2-global-business-rules)
3. [Global Database Conventions](#3-global-database-conventions)
4. [Role & Permission Matrix](#4-role--permission-matrix)
5. [Modul 01 — Manajemen User](#modul-01--manajemen-user)
6. [Modul 02 — Manajemen Nasabah](#modul-02--manajemen-nasabah)
7. [Modul 03 — Transaksi Simpanan](#modul-03--transaksi-simpanan)
8. [Modul 04 — Transaksi Penarikan](#modul-04--transaksi-penarikan)
9. [Modul 05 — Manajemen Hari Libur](#modul-05--manajemen-hari-libur)
10. [Modul 06 — Manajemen Bunga](#modul-06--manajemen-bunga)
11. [Modul 07 — Engine Perhitungan Bunga Harian](#modul-07--engine-perhitungan-bunga-harian)
12. [Modul 08 — Posting Bunga Bulanan](#modul-08--posting-bunga-bulanan)
13. [Modul 09 — Manajemen Deposito](#modul-09--manajemen-deposito)
14. [Modul 10 — Dashboard](#modul-10--dashboard)
15. [Modul 11 — Laporan](#modul-11--laporan)
16. [Modul 12 — Activity Log](#modul-12--activity-log)
17. [Urutan Implementasi Global](#17-urutan-implementasi-global)

---

# 1. GAMBARAN UMUM SISTEM

## 1.1 Deskripsi

Sistem Informasi Koperasi (SIK) adalah aplikasi web berbasis Laravel yang menangani operasional koperasi simpan pinjam. Sistem ini fokus pada manajemen simpanan, penarikan, dan deposito nasabah beserta pengelolaan bunga otomatis.

## 1.2 Scope Sistem

| Termasuk | Tidak Termasuk |
|----------|----------------|
| Manajemen User (Karyawan) | Modul Pinjaman / Kredit |
| Manajemen Nasabah | General Ledger / Akuntansi |
| Transaksi Simpanan | Laporan Keuangan Neraca |
| Transaksi Penarikan | Multi-cabang |
| Manajemen Deposito | Mobile App |
| Manajemen Hari Libur | Integrasi Payment Gateway |
| Manajemen Bunga | |
| Engine Bunga Harian | |
| Posting Bunga Bulanan | |
| Dashboard & Laporan | |
| Activity Log | |

## 1.3 Technology Stack

| Komponen | Teknologi |
|----------|-----------|
| Backend Framework | Laravel 9.x |
| Frontend | Blade + AdminLTE 3 + Bootstrap 4 |
| Database | MySQL 8.0 |
| Scheduler | Laravel Scheduler (Cron) |
| PDF Generator | barryvdh/laravel-dompdf |
| DataTable | Yajra DataTables |
| Activity Log | spatie/laravel-activitylog |

## 1.4 Aktor Sistem

| Role | Kode | Deskripsi |
|------|------|-----------|
| Manager | MGR | Akses penuh ke seluruh sistem |
| Teller | TLR | Input transaksi simpanan, penarikan, nasabah |
| Viewer | VWR | Hanya bisa melihat data dan laporan |

---

# 2. GLOBAL BUSINESS RULES

**GBR-01:** Saldo nasabah SELALU dihitung dari `SUM()` seluruh transaksi, bukan dari kolom `current_balance`. Kolom `current_balance` hanya berfungsi sebagai cache untuk performa tampilan.

**GBR-02:** Kolom `current_balance` wajib dihitung ulang (recalculate) setiap kali ada operasi INSERT, UPDATE, atau DELETE transaksi yang mempengaruhi saldo nasabah tersebut.

**GBR-03:** Tidak ada biaya administrasi dalam bentuk apapun.

**GBR-04:** Tidak ada proses approval transaksi — transaksi langsung efektif saat disimpan.

**GBR-05:** Admin (Manager/Teller) dapat mengedit dan menghapus transaksi. Setelah edit/hapus, sistem WAJIB merecalculate saldo semua transaksi setelah tanggal transaksi yang diubah.

**GBR-06:** Nasabah hanya boleh dihapus jika total saldo semua jenis simpanan = 0 dan tidak memiliki deposito aktif.

**GBR-07:** Semua aktivitas pengguna (login, logout, CRUD data, transaksi) wajib tercatat di Activity Log.

**GBR-08:** Pencarian nasabah di form transaksi menggunakan input Nomor Rekening, bukan dropdown nama.

**GBR-09:** Laporan transaksi menampilkan dua kolom terpisah: **Masuk** (simpanan/bunga) dan **Keluar** (penarikan).

**GBR-10:** Bunga hanya dihitung pada hari kerja. Hari libur nasional dan akhir pekan (Sabtu-Minggu) tidak dihitung bunga.

**GBR-11:** Proses perhitungan bunga harian dan posting bulanan bersifat **idempotent** — tidak boleh dijalankan lebih dari sekali untuk periode yang sama.

**GBR-12:** Nominal mata uang menggunakan Rupiah (IDR), format bilangan bulat tanpa desimal. Kolom database menggunakan `BIGINT UNSIGNED`.

---

# 3. GLOBAL DATABASE CONVENTIONS

## 3.1 Naming Convention

| Elemen | Konvensi | Contoh |
|--------|----------|--------|
| Tabel | snake_case, plural | `savings_transactions` |
| Kolom | snake_case | `customer_id` |
| Primary Key | `id` (bigint auto-increment) | `id` |
| Foreign Key | `{table_singular}_id` | `customer_id` |
| Timestamp standard | `created_at`, `updated_at` | — |
| Soft delete | `deleted_at` | — |
| Status | enum atau tinyint | `status` |
| Boolean | `is_{nama}` tinyint(1) | `is_active` |

## 3.2 Kolom Wajib di Setiap Tabel Transaksional

```
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
created_by      BIGINT UNSIGNED NULL FK(users.id)   -- siapa yang membuat
updated_by      BIGINT UNSIGNED NULL FK(users.id)   -- siapa yang mengubah
created_at      TIMESTAMP NULL
updated_at      TIMESTAMP NULL
deleted_at      TIMESTAMP NULL  -- soft delete
```

## 3.3 Tipe Kolom Nominal/Saldo

Semua kolom yang menyimpan nominal uang menggunakan `BIGINT UNSIGNED` (satuan rupiah penuh, tanpa desimal). Tidak menggunakan `DECIMAL` atau `FLOAT`.

## 3.4 Soft Delete Policy

Semua tabel master dan transaksi WAJIB menggunakan Soft Delete (`deleted_at`). Data yang dihapus tidak benar-benar dihapus dari database.

---

# 4. ROLE & PERMISSION MATRIX

| Fitur | Manager | Teller | Viewer |
|-------|:-------:|:------:|:------:|
| **User Management** | | | |
| Lihat daftar user | ✅ | ❌ | ❌ |
| Tambah/Edit/Hapus user | ✅ | ❌ | ❌ |
| Reset password user lain | ✅ | ❌ | ❌ |
| Edit profil sendiri | ✅ | ✅ | ✅ |
| Reset data sistem | ✅ | ❌ | ❌ |
| **Nasabah** | | | |
| Lihat nasabah | ✅ | ✅ | ✅ |
| Tambah/Edit nasabah | ✅ | ✅ | ❌ |
| Hapus nasabah | ✅ | ❌ | ❌ |
| Cetak laporan nasabah | ✅ | ✅ | ✅ |
| **Simpanan** | | | |
| Lihat simpanan | ✅ | ✅ | ✅ |
| Input simpanan baru | ✅ | ✅ | ❌ |
| Edit simpanan | ✅ | ✅ | ❌ |
| Hapus simpanan | ✅ | ❌ | ❌ |
| Cetak laporan simpanan | ✅ | ✅ | ✅ |
| **Penarikan** | | | |
| Lihat penarikan | ✅ | ✅ | ✅ |
| Input penarikan | ✅ | ✅ | ❌ |
| Edit penarikan | ✅ | ✅ | ❌ |
| Hapus penarikan | ✅ | ❌ | ❌ |
| **Hari Libur** | | | |
| Lihat kalender hari libur | ✅ | ✅ | ✅ |
| Kelola hari libur | ✅ | ❌ | ❌ |
| **Manajemen Bunga** | | | |
| Lihat setting bunga | ✅ | ✅ | ✅ |
| Ubah rate bunga | ✅ | ❌ | ❌ |
| **Deposito** | | | |
| Lihat deposito | ✅ | ✅ | ✅ |
| Buka/Perpanjang/Cairkan deposito | ✅ | ✅ | ❌ |
| Hapus deposito | ✅ | ❌ | ❌ |
| **Dashboard & Laporan** | | | |
| Lihat dashboard | ✅ | ✅ | ✅ |
| Cetak semua laporan | ✅ | ✅ | ✅ |
| **Activity Log** | | | |
| Lihat activity log | ✅ | ❌ | ❌ |

---

# MODUL 01 — MANAJEMEN USER

## 01.1 Functional Requirement

| ID | Requirement |
|----|-------------|
| USR-FR-01 | Sistem menyediakan CRUD untuk data karyawan (user) |
| USR-FR-02 | Setiap user memiliki role: Manager, Teller, atau Viewer |
| USR-FR-03 | User dapat mengupdate profil dan password sendiri |
| USR-FR-04 | Manager dapat mereset password user lain |
| USR-FR-05 | Hanya ada satu Manager aktif dalam sistem |
| USR-FR-06 | User tidak dapat menghapus akun dirinya sendiri |
| USR-FR-07 | Manager tidak bisa dihapus oleh siapapun |
| USR-FR-08 | Sistem mendukung cetak laporan data karyawan |
| USR-FR-09 | Reset data sistem hanya dapat dilakukan oleh Manager |

## 01.2 Business Flow

```
[Manager Login]
      |
      ▼
[Menu Karyawan]
      |
      ├──▶ [Tambah Karyawan] → [Isi Form] → [Validasi] → [Simpan] → [Log Activity]
      |
      ├──▶ [Edit Karyawan] → [Ubah Data] → [Validasi] → [Update] → [Log Activity]
      |
      ├──▶ [Hapus Karyawan] → [Cek: bukan Manager?] → [Konfirmasi] → [Soft Delete] → [Log]
      |
      └──▶ [Cetak Laporan] → [Generate PDF] → [Download]

[Semua User]
      |
      └──▶ [Profil Saya] → [Edit Profil/Password] → [Simpan] → [Log Activity]
```

## 01.3 Database Design

### Tabel: `users`

```sql
CREATE TABLE users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    role            ENUM('manager', 'teller', 'viewer') NOT NULL DEFAULT 'teller',
    gender          ENUM('L', 'P') NOT NULL DEFAULT 'L',
    birth           DATE NULL,
    address         TEXT NULL,
    phone           VARCHAR(20) NULL UNIQUE,
    last_education  VARCHAR(50) NULL,
    photo           VARCHAR(255) NULL,
    joined_at       DATE NOT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    remember_token  VARCHAR(100) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL,
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
);
```

**Catatan perubahan dari implementasi saat ini:**
- Hapus kolom `email_verified_at` (tidak digunakan)
- Ubah `joined_at` menjadi `DATE NOT NULL`
- Tambah `is_active`
- Tambah `deleted_at` (soft delete)

## 01.4 API Specification

| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| GET | `/karyawan` | ✅ | MGR | Halaman daftar karyawan |
| GET | `/karyawan/create` | ✅ | MGR | Form tambah karyawan |
| POST | `/karyawan` | ✅ | MGR | Simpan karyawan baru |
| GET | `/karyawan/{id}` | ✅ | MGR | Detail karyawan |
| GET | `/karyawan/{id}/edit` | ✅ | MGR | Form edit karyawan |
| PUT | `/karyawan/{id}` | ✅ | MGR | Update karyawan |
| DELETE | `/karyawan/{id}` | ✅ | MGR | Hapus karyawan (soft) |
| POST | `/karyawan/cetak` | ✅ | MGR | Download PDF laporan |
| GET | `/pengaturan` | ✅ | ALL | Halaman profil sendiri |
| POST | `/pengaturan` | ✅ | ALL | Update profil sendiri |
| POST | `/pengaturan/password` | ✅ | ALL | Ganti password |

## 01.5 Validation Rules

### Store/Update User (oleh Manager)

| Field | Rule | Pesan Error |
|-------|------|-------------|
| name | required, string, max:100 | Nama wajib diisi |
| username | required, unique:users, min:4, max:50, alpha_dash | Username sudah digunakan / hanya huruf, angka, dan underscore |
| password | required (create), min:8, regex kombinasi huruf+angka | Password minimal 8 karakter dan harus kombinasi huruf dan angka |
| role | required, in:manager,teller,viewer | Role tidak valid |
| gender | required, in:L,P | Jenis kelamin tidak valid |
| joined_at | required, date, before_or_equal:today | Tanggal bergabung tidak valid |
| phone | nullable, unique:users, digits_between:10,15 | Nomor telepon sudah terdaftar |
| photo | nullable, image, mimes:jpeg,png,jpg, max:2048 | Format foto tidak valid |

### Update Profil Sendiri

| Field | Rule |
|-------|------|
| name | required, string, max:100 |
| username | required, unique:users,id (ignore self) |
| gender | required |
| current_password | required_with:new_password |
| new_password | nullable, min:8, confirmed, regex kombinasi |
| photo | nullable, image, max:2048 |

## 01.6 UI Layout

### Halaman Daftar Karyawan
```
┌──────────────────────────────────────────────────────┐
│ [+ Tambah Karyawan]          [🖨 Cetak]              │
├──────────────────────────────────────────────────────┤
│ Tabel DataTables:                                    │
│ No | Nama | Username | Role | Status | Bergabung | Aksi │
│ [Detail] [Edit] [Hapus]                             │
│ (Hapus tidak muncul untuk Manager & diri sendiri)   │
└──────────────────────────────────────────────────────┘
```

### Form Karyawan
```
┌──────────────────────────────────────────────────────┐
│ INFORMASI KARYAWAN                                   │
├────────────────────┬─────────────────────────────────┤
│ Nama Lengkap *     │ [__________________________]    │
│ Username *         │ [__________________________]    │
│ Password *         │ [__________________________]    │
│ Konfirmasi PW *    │ [__________________________]    │
│ Role *             │ [Manager ▼]                     │
│ Jenis Kelamin *    │ (●) Laki-laki ( ) Perempuan     │
│ Tanggal Lahir      │ [YYYY-MM-DD]                    │
│ Tanggal Bergabung* │ [YYYY-MM-DD]                    │
│ Nomor Telepon      │ [__________________________]    │
│ Alamat             │ [__________________________]    │
│ Pendidikan Terakhir│ [__________________________]    │
│ Foto               │ [Choose File...]                │
└────────────────────┴─────────────────────────────────┘
│                      [Batal]  [Simpan]               │
└──────────────────────────────────────────────────────┘
```

## 01.7 Acceptance Criteria

| ID | Skenario | Expected Result |
|----|----------|-----------------|
| AC-USR-01 | Manager menambah karyawan baru dengan data valid | Karyawan tersimpan, muncul di daftar, activity log tercatat |
| AC-USR-02 | Manager menghapus karyawan bertipe Teller | Karyawan soft-deleted, tidak muncul di daftar aktif |
| AC-USR-03 | Manager mencoba menghapus akun Manager lain | Sistem menolak dengan pesan "Manager tidak dapat dihapus" |
| AC-USR-04 | User mencoba akses halaman karyawan (bukan Manager) | Redirect ke halaman forbidden (403) |
| AC-USR-05 | User mengubah password dengan password lama salah | Validasi gagal, password tidak berubah |
| AC-USR-06 | User mengubah password dengan aturan valid | Password berhasil diubah, session tetap aktif |

## 01.8 Test Case

### Normal Case
| TC | Input | Expected |
|----|-------|----------|
| TC-USR-N01 | Tambah user: name="Budi", username="budi123", password="Budi1234", role="teller" | User tersimpan, redirect ke daftar |
| TC-USR-N02 | Edit user: ubah phone="081234567890" | Data terupdate, log activity mencatat perubahan |
| TC-USR-N03 | Hapus user teller yang ada | Soft delete, user tidak tampil di list |

### Edge Case
| TC | Input | Expected |
|----|-------|----------|
| TC-USR-E01 | Username tepat 50 karakter | Berhasil disimpan |
| TC-USR-E02 | Photo upload tepat 2048KB | Berhasil diupload |
| TC-USR-E03 | Tambah user dengan phone yang sudah dipakai user lain | Validasi gagal: "Nomor telepon sudah terdaftar" |

### Negative Case
| TC | Input | Expected |
|----|-------|----------|
| TC-USR-NEG01 | Password tanpa angka: "passwordsaja" | Validasi gagal |
| TC-USR-NEG02 | Hapus akun Manager | HTTP 403 / pesan error |
| TC-USR-NEG03 | Teller akses `/karyawan` | HTTP 403 |
| TC-USR-NEG04 | Username dengan spasi: "budi santoso" | Validasi gagal |

## 01.9 Risiko Implementasi

| Risiko | Probabilitas | Dampak | Mitigasi |
|--------|-------------|--------|----------|
| Lupa batasi hapus Manager | Tinggi | Tinggi | Guard di middleware + model level |
| Password lama tidak dicek saat ubah password | Tinggi | Tinggi | Wajib cek `Hash::check()` |
| Foto lama tidak dihapus saat upload baru | Medium | Low | Implementasikan `deleteImage()` sebelum store baru |

---

# MODUL 02 — MANAJEMEN NASABAH

## 02.1 Functional Requirement

| ID | Requirement |
|----|-------------|
| CST-FR-01 | CRUD data nasabah |
| CST-FR-02 | Nasabah memiliki nomor rekening unik yang dibuat oleh sistem (auto-generate) |
| CST-FR-03 | Status nasabah: Active, Blacklist |
| CST-FR-04 | Nasabah hanya dapat dihapus jika saldo = 0 dan tidak ada deposito aktif |
| CST-FR-05 | Sistem mencatat tanggal bergabung nasabah secara otomatis |
| CST-FR-06 | Pencarian nasabah mendukung filter by status, tanggal, dan nomor rekening |
| CST-FR-07 | Halaman detail nasabah menampilkan ringkasan saldo semua jenis simpanan |
| CST-FR-08 | Halaman detail nasabah menampilkan riwayat transaksi |
| CST-FR-09 | Cetak laporan data nasabah berdasarkan periode `joined_at` |

## 02.2 Business Flow

```
[Tambah Nasabah]
    │
    ├─ Input data pribadi
    │
    ├─ Sistem auto-generate nomor rekening (format: [TAHUN][BULAN][SEQUENCE 5 DIGIT])
    │   Contoh: 202608-00001
    │
    ├─ Validasi: NIK unik, telepon unik
    │
    └─ Simpan → joined_at = today → Activity Log

[Hapus Nasabah]
    │
    ├─ Cek saldo tabungan = 0?
    │   └─ Jika TIDAK → Tolak: "Nasabah masih memiliki saldo"
    │
    ├─ Cek ada deposito aktif?
    │   └─ Jika YA → Tolak: "Nasabah masih memiliki deposito aktif"
    │
    └─ Soft Delete → Activity Log
```

## 02.3 Database Design

### Tabel: `customers`

```sql
CREATE TABLE customers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    number          VARCHAR(20) NOT NULL UNIQUE,   -- nomor rekening auto-generate
    nik             VARCHAR(16) NOT NULL UNIQUE,
    name            VARCHAR(100) NOT NULL,
    gender          ENUM('L', 'P') NOT NULL DEFAULT 'L',
    birth           DATE NULL,
    address         TEXT NULL,
    phone           VARCHAR(20) NULL UNIQUE,
    last_education  VARCHAR(50) NULL,
    profession      VARCHAR(100) NULL,
    status          ENUM('active', 'blacklist') NOT NULL DEFAULT 'active',
    photo           VARCHAR(255) NULL,
    joined_at       DATE NOT NULL,
    notes           TEXT NULL,
    created_by      BIGINT UNSIGNED NULL,
    updated_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL,

    INDEX idx_status (status),
    INDEX idx_joined_at (joined_at),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### Format Nomor Rekening
```
Format : [YYYY][MM]-[SEQUENCE 5 DIGIT]
Contoh : 202608-00001
Logic  : MAX(id) dalam bulan berjalan + 1, diformat ZEROFILL 5 digit
```

## 02.4 API Specification

| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| GET | `/nasabah` | ✅ | MGR,TLR,VWR | Daftar nasabah |
| GET | `/nasabah/create` | ✅ | MGR,TLR | Form tambah |
| POST | `/nasabah` | ✅ | MGR,TLR | Simpan nasabah |
| GET | `/nasabah/{id}` | ✅ | ALL | Detail nasabah |
| GET | `/nasabah/{id}/edit` | ✅ | MGR,TLR | Form edit |
| PUT | `/nasabah/{id}` | ✅ | MGR,TLR | Update nasabah |
| DELETE | `/nasabah/{id}` | ✅ | MGR | Hapus (soft) |
| POST | `/nasabah/cetak` | ✅ | ALL | Download PDF |
| GET | `/api/v1/nasabah/cari` | ✅ | ALL | Cari by nomor rekening |
| GET | `/api/v1/nasabah/{id}/saldo` | ✅ | ALL | Ambil saldo terkini |

### Response: GET /api/v1/nasabah/cari?nomor_rekening=202608-00001

```json
{
  "status": "success",
  "data": {
    "id": 1,
    "number": "202608-00001",
    "name": "Budi Santoso",
    "status": "active",
    "saldo": {
      "sukarela": 5000000,
      "wajib": 1000000,
      "pokok": 500000,
      "total": 6500000
    }
  }
}
```

### Response: GET /api/v1/nasabah/{id}/saldo

```json
{
  "status": "success",
  "data": {
    "customer_id": 1,
    "number": "202608-00001",
    "name": "Budi Santoso",
    "saldo_sukarela": 5000000,
    "saldo_wajib": 1000000,
    "saldo_pokok": 500000,
    "saldo_total": 6500000,
    "last_transaction_at": "2026-08-05T10:30:00Z"
  }
}
```

## 02.5 Validation Rules

| Field | Rule | Pesan Error |
|-------|------|-------------|
| nik | required, digits:16, unique:customers | NIK harus 16 digit dan belum terdaftar |
| name | required, string, max:100 | Nama wajib diisi |
| gender | required, in:L,P | Jenis kelamin tidak valid |
| birth | nullable, date, before:today | Tanggal lahir tidak valid |
| phone | nullable, unique:customers, digits_between:10,15 | Nomor telepon sudah terdaftar |
| status | required, in:active,blacklist | Status tidak valid |
| photo | nullable, image, mimes:jpeg,png,jpg, max:2048 | — |

## 02.6 UI Layout

### Halaman Detail Nasabah
```
┌─────────────────────────────────────────────────────────┐
│  PROFIL NASABAH                   No. Rek: 202608-00001 │
│  [Foto]  Nama: Budi Santoso                             │
│          NIK: 1234567890123456                          │
│          Status: [ACTIVE]                               │
├─────────────────────────────────────────────────────────┤
│  RINGKASAN SALDO                                        │
│  ┌──────────────┬────────────────┐                      │
│  │ Simpanan Pokok   │ Rp 500.000    │                   │
│  │ Simpanan Wajib   │ Rp 1.000.000  │                   │
│  │ Simpanan Sukarela│ Rp 5.000.000  │                   │
│  │ TOTAL SALDO      │ Rp 6.500.000  │                   │
│  └──────────────┴────────────────┘                      │
├─────────────────────────────────────────────────────────┤
│  RIWAYAT TRANSAKSI                                      │
│  Tanggal | Kode | Jenis | Masuk | Keluar | Saldo        │
│  [Filter Periode] [Filter Jenis]                        │
└─────────────────────────────────────────────────────────┘
```

## 02.7 Acceptance Criteria

| ID | Skenario | Expected Result |
|----|----------|-----------------|
| AC-CST-01 | Tambah nasabah dengan NIK valid | Tersimpan, nomor rekening ter-generate otomatis |
| AC-CST-02 | Tambah nasabah dengan NIK duplikat | Validasi gagal: "NIK sudah terdaftar" |
| AC-CST-03 | Hapus nasabah yang masih punya saldo | Ditolak: "Nasabah masih memiliki saldo" |
| AC-CST-04 | Hapus nasabah dengan saldo 0 dan tanpa deposito | Berhasil soft delete |
| AC-CST-05 | Cari nasabah via nomor rekening yang valid | Data nasabah dan saldo dikembalikan |
| AC-CST-06 | Cari nasabah via nomor rekening tidak ada | Response: 404 Not Found |

## 02.8 Test Case

### Normal Case
| TC | Input | Expected |
|----|-------|----------|
| TC-CST-N01 | Tambah nasabah lengkap | Tersimpan, nomor rekening format YYYYMM-00001 |
| TC-CST-N02 | Edit alamat nasabah | Terupdate, log activity tercatat |
| TC-CST-N03 | Cari nomor rekening "202608-00001" | Data nasabah kembali |

### Edge Case
| TC | Input | Expected |
|----|-------|----------|
| TC-CST-E01 | Tambah nasabah ke-99999 di bulan yang sama | Nomor rekening YYYYMM-99999 |
| TC-CST-E02 | Hapus nasabah yang punya saldo tabungan 0 tapi ada bunga terakumulasi | Ditolak atau hitung sebagai saldo 0 (spec tergantung keputusan bisnis) |

### Negative Case
| TC | Input | Expected |
|----|-------|----------|
| TC-CST-NEG01 | NIK kurang dari 16 digit | Validasi gagal |
| TC-CST-NEG02 | Hapus nasabah berstatus active dengan saldo Rp 50.000 | HTTP 422, pesan "Nasabah masih memiliki saldo" |

---

# MODUL 03 — TRANSAKSI SIMPANAN

## 03.1 Functional Requirement

| ID | Requirement |
|----|-------------|
| SAV-FR-01 | Input simpanan dengan jenis: Pokok, Wajib, Sukarela |
| SAV-FR-02 | Nasabah dicari berdasarkan nomor rekening (input text + auto-fill) |
| SAV-FR-03 | Sistem menghitung `current_balance` secara otomatis menggunakan SUM |
| SAV-FR-04 | Riwayat simpanan ditampilkan di DataTables dengan filter |
| SAV-FR-05 | Admin dapat edit simpanan; saldo berikutnya dihitung ulang |
| SAV-FR-06 | Admin dapat hapus simpanan; saldo berikutnya dihitung ulang |
| SAV-FR-07 | Cetak laporan simpanan per nasabah dengan kolom Masuk dan Keluar |
| SAV-FR-08 | Cetak slip transaksi per record |
| SAV-FR-09 | Simpanan bertipe "Bunga" hanya dapat dibuat oleh sistem (posting otomatis) |

## 03.2 Business Flow

```
[Input Simpanan]
    │
    ├─ Input Nomor Rekening → Auto-fetch nama & saldo nasabah
    │
    ├─ Pilih Jenis: Pokok / Wajib / Sukarela
    │
    ├─ Input Nominal (> 0)
    │
    ├─ Input Tanggal (default: hari ini)
    │
    ├─ Submit → Kalkulasi current_balance = SUM semua transaksi jenis tersebut s.d. tanggal ini
    │
    ├─ Simpan record baru
    │
    └─ Log Activity → Tampil notifikasi sukses

[Edit Simpanan]
    │
    ├─ Edit nominal/tanggal/jenis
    │
    ├─ Simpan perubahan
    │
    └─ Recalculate: UPDATE current_balance semua transaksi nasabah ini yang created_at >= tanggal edit
        Pseudocode:
        SELECT * FROM savings_transactions
        WHERE customer_id = X
        ORDER BY created_at ASC, id ASC

        running_balance = 0
        FOR EACH record:
            IF type IN (pokok, wajib, sukarela, bunga):
                running_balance += amount
            ELIF type = penarikan:
                running_balance -= amount
            UPDATE record.current_balance = running_balance
```

## 03.3 Database Design

### Tabel: `savings_transactions`

```sql
CREATE TABLE savings_transactions (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id      BIGINT UNSIGNED NOT NULL,
    type             ENUM('pokok','wajib','sukarela','bunga','penarikan') NOT NULL,
    amount           BIGINT UNSIGNED NOT NULL,   -- selalu positif
    previous_balance BIGINT UNSIGNED NOT NULL DEFAULT 0,
    current_balance  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    notes            TEXT NULL,
    transaction_date DATE NOT NULL,               -- tanggal efektif transaksi
    created_by       BIGINT UNSIGNED NULL,
    updated_by       BIGINT UNSIGNED NULL,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,
    deleted_at       TIMESTAMP NULL,

    INDEX idx_customer_type (customer_id, type),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_customer_date (customer_id, transaction_date),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

**Kunci desain:**
- `amount` SELALU positif (tidak ada negatif)
- `type = 'penarikan'` disimpan di tabel ini dengan amount positif, tetapi dikurangi saat kalkulasi saldo
- `transaction_date` adalah tanggal efektif, bukan `created_at`
- Urutan recalculate berdasarkan `transaction_date ASC, id ASC`

## 03.4 API Specification

| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| GET | `/transaksi/simpanan` | ✅ | ALL | Daftar simpanan |
| GET | `/transaksi/simpanan/create` | ✅ | MGR,TLR | Form input simpanan |
| POST | `/transaksi/simpanan` | ✅ | MGR,TLR | Simpan simpanan |
| GET | `/transaksi/simpanan/{id}` | ✅ | ALL | Detail simpanan |
| GET | `/transaksi/simpanan/{id}/edit` | ✅ | MGR,TLR | Form edit |
| PUT | `/transaksi/simpanan/{id}` | ✅ | MGR,TLR | Update + recalculate |
| DELETE | `/transaksi/simpanan/{id}` | ✅ | MGR | Hapus + recalculate |
| POST | `/transaksi/simpanan/cetak` | ✅ | ALL | Download PDF laporan |
| GET | `/transaksi/simpanan/{id}/slip` | ✅ | ALL | Cetak slip transaksi |

## 03.5 Validation Rules

| Field | Rule | Pesan Error |
|-------|------|-------------|
| customer_number | required, exists:customers,number, status=active | Nasabah tidak ditemukan atau tidak aktif |
| type | required, in:pokok,wajib,sukarela | Jenis simpanan tidak valid |
| amount | required, integer, min:1000 | Nominal minimal Rp 1.000 |
| transaction_date | required, date, before_or_equal:today | Tanggal tidak valid |
| notes | nullable, string, max:255 | — |

## 03.6 UI Layout

### Form Input Simpanan
```
┌─────────────────────────────────────────────────────┐
│  TRANSAKSI SIMPANAN BARU                            │
├─────────────────────────────────────────────────────┤
│  No. Rekening *   │ [202608-00001    ] [🔍 Cari]   │
│  Nama Nasabah     │ [Budi Santoso — auto-filled]   │
│  Saldo Saat Ini   │ [Rp 6.500.000 — auto-filled]  │
├─────────────────────────────────────────────────────┤
│  Jenis Simpanan * │ (●) Sukarela  ( ) Wajib  ( ) Pokok │
│  Nominal *        │ [________________] Rp              │
│  Tanggal *        │ [2026-08-05]                    │
│  Keterangan       │ [________________________________]  │
├─────────────────────────────────────────────────────┤
│                      [Batal]  [Simpan Transaksi]    │
└─────────────────────────────────────────────────────┘
```

### Daftar Simpanan (DataTables)
```
Kolom: No | Tanggal | Kode Trx | Nasabah | Jenis | Masuk | Keluar | Saldo | Aksi
Filter: [Nasabah by No.Rek] [Jenis ▼] [Dari Tgl] [s/d Tgl] [Cari...]
```

## 03.7 Acceptance Criteria

| ID | Skenario | Expected Result |
|----|----------|-----------------|
| AC-SAV-01 | Input simpanan Sukarela Rp 100.000 | Tersimpan, saldo terupdate, slip dapat dicetak |
| AC-SAV-02 | Input simpanan dengan nomor rekening tidak ada | Validasi gagal: nasabah tidak ditemukan |
| AC-SAV-03 | Edit simpanan dari Rp 100.000 → Rp 150.000 | Simpanan terupdate, semua saldo transaksi berikutnya dihitung ulang |
| AC-SAV-04 | Hapus simpanan yang bukan tipe 'bunga' | Soft deleted, saldo berikutnya recalculate |
| AC-SAV-05 | Coba input simpanan tipe 'bunga' manual | Validasi gagal: "Tipe bunga dibuat oleh sistem" |

---

# MODUL 04 — TRANSAKSI PENARIKAN

## 04.1 Functional Requirement

| ID | Requirement |
|----|-------------|
| WDR-FR-01 | Input penarikan simpanan dengan validasi saldo mencukupi (server-side) |
| WDR-FR-02 | Penarikan hanya berlaku untuk jenis simpanan Sukarela |
| WDR-FR-03 | Nasabah dicari berdasarkan nomor rekening |
| WDR-FR-04 | Sistem menampilkan saldo Sukarela real-time sebelum input nominal |
| WDR-FR-05 | Saldo TIDAK BOLEH menjadi negatif setelah penarikan |
| WDR-FR-06 | Admin dapat edit dan hapus penarikan; saldo dihitung ulang |
| WDR-FR-07 | Cetak laporan penarikan dengan kolom Masuk dan Keluar |
| WDR-FR-08 | Cetak slip penarikan per record |

## 04.2 Business Flow

```
[Input Penarikan]
    │
    ├─ Input Nomor Rekening → Auto-fetch nama & saldo Sukarela
    │
    ├─ Input Nominal Penarikan
    │
    ├─ Validasi Server-Side:
    │   └─ Cek: saldo_sukarela >= nominal_penarikan?
    │       ├─ YA → lanjut
    │       └─ TIDAK → Tolak: "Saldo tidak mencukupi. Saldo tersedia: Rp X"
    │
    ├─ Simpan sebagai savings_transactions dengan type='penarikan'
    │
    ├─ Recalculate current_balance
    │
    └─ Log Activity → Notifikasi sukses
```

## 04.3 Database Design

Menggunakan tabel yang sama: `savings_transactions`
- `type = 'penarikan'`
- `amount` tetap positif
- Saat kalkulasi saldo: saldo -= amount jika type = penarikan

## 04.4 Validation Rules

| Field | Rule | Pesan Error |
|-------|------|-------------|
| customer_number | required, exists aktif | Nasabah tidak ditemukan |
| amount | required, integer, min:1000 | Nominal minimal Rp 1.000 |
| amount | custom: amount <= saldo_sukarela | Saldo tidak mencukupi. Saldo tersedia: Rp {X} |
| transaction_date | required, date, before_or_equal:today | Tanggal tidak valid |

> [!IMPORTANT]
> Validasi saldo WAJIB dilakukan di server-side controller, BUKAN hanya di JavaScript/HTML. Gunakan DB transaction dengan `lockForUpdate()` untuk mencegah race condition.

## 04.5 Pseudocode Validasi Saldo (Server-Side)

```php
DB::beginTransaction();
try {
    // Lock row untuk mencegah race condition
    $currentBalance = SavingsTransaction::where('customer_id', $customerId)
        ->lockForUpdate()
        ->whereNotNull('...')
        ->calculateSukarela(); // SUM masuk - SUM keluar jenis sukarela

    if ($request->amount > $currentBalance) {
        DB::rollBack();
        return back()->withErrors([
            'amount' => "Saldo tidak mencukupi. Saldo tersedia: Rp " . number_format($currentBalance)
        ]);
    }

    SavingsTransaction::create([...]);
    $this->recalculateBalance($customerId, $transactionDate);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

## 04.6 Acceptance Criteria

| ID | Skenario | Expected Result |
|----|----------|-----------------|
| AC-WDR-01 | Tarik Rp 100.000 dengan saldo Rp 500.000 | Berhasil, saldo menjadi Rp 400.000 |
| AC-WDR-02 | Tarik Rp 600.000 dengan saldo Rp 500.000 | Gagal: "Saldo tidak mencukupi" |
| AC-WDR-03 | Bypass HTML max attr via Postman, tarik melebihi saldo | Server menolak, saldo tidak berubah |
| AC-WDR-04 | Dua transaksi penarikan bersamaan (race condition) | Hanya satu yang berhasil, saldo konsisten |
| AC-WDR-05 | Hapus penarikan, saldo recalculate | Saldo kembali bertambah sesuai nilai penarikan |

## 04.7 Test Case

### Negative Case (Critical)
| TC | Skenario | Expected |
|----|----------|----------|
| TC-WDR-NEG01 | POST manual (via curl) amount=999999999 | HTTP 422, pesan saldo tidak mencukupi |
| TC-WDR-NEG02 | Amount = 0 | Validasi gagal: minimal Rp 1.000 |
| TC-WDR-NEG03 | Nasabah berstatus blacklist | Validasi gagal: nasabah tidak aktif |

---

# MODUL 05 — MANAJEMEN HARI LIBUR

## 05.1 Tujuan Modul

Mengelola daftar hari libur nasional dan hari kerja khusus dalam satu tahun. Data ini digunakan oleh **Engine Bunga Harian** (Modul 07) untuk menentukan apakah bunga perlu dihitung pada hari tertentu.

## 05.2 Functional Requirement

| ID | Requirement |
|----|-------------|
| HOL-FR-01 | Manager dapat menambah, mengedit, dan menghapus hari libur |
| HOL-FR-02 | Hari libur memiliki tipe: `holiday` (libur) atau `workday` (hari kerja pengganti) |
| HOL-FR-03 | Sabtu dan Minggu secara default adalah hari libur (hard-coded) |
| HOL-FR-04 | Hari libur tipe `workday` mengoverride Sabtu/Minggu menjadi hari kerja |
| HOL-FR-05 | Sistem menyediakan endpoint API untuk mengecek apakah tanggal tertentu adalah hari kerja |
| HOL-FR-06 | Manager dapat melakukan seed hari libur nasional per tahun dari data baku |
| HOL-FR-07 | Tampilan kalender menunjukkan hari libur dan hari kerja dalam satu bulan |
| HOL-FR-08 | Tidak boleh ada duplikat tanggal |

## 05.3 Business Flow

```
[Cek Apakah Hari Kerja - digunakan Engine Bunga]
    │
    ├─ Apakah hari Sabtu atau Minggu?
    │   └─ YA → Cek apakah ada entry di holidays dengan tipe = 'workday'?
    │               ├─ YA → HARI KERJA ✅
    │               └─ TIDAK → HARI LIBUR ❌
    │
    └─ Bukan Sabtu/Minggu → Cek apakah ada entry di holidays dengan tipe = 'holiday'?
        ├─ YA → HARI LIBUR ❌
        └─ TIDAK → HARI KERJA ✅

[Tambah Hari Libur Manual]
    │
    ├─ Input: Tanggal, Nama, Tipe (holiday/workday)
    ├─ Validasi: Tanggal belum terdaftar di tahun yang sama
    └─ Simpan → Log Activity

[Seed Hari Libur Nasional]
    │
    ├─ Input: Tahun
    ├─ Load data hari libur nasional baku dari config/storage
    ├─ Bulk insert (skip duplikat)
    └─ Log Activity
```

## 05.4 Database Design

### Tabel: `holidays`

```sql
CREATE TABLE holidays (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date        DATE NOT NULL,
    name        VARCHAR(100) NOT NULL,
    type        ENUM('holiday', 'workday') NOT NULL DEFAULT 'holiday',
    year        SMALLINT UNSIGNED NOT NULL,   -- redundant for fast query
    description TEXT NULL,
    created_by  BIGINT UNSIGNED NULL,
    updated_by  BIGINT UNSIGNED NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL,

    UNIQUE KEY uq_date (date),
    INDEX idx_year (year),
    INDEX idx_type (type),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

**Catatan desain:**
- Kolom `year` redundan dengan `DATE`, namun mempercepat query filter per tahun
- UNIQUE pada `date` mencegah duplikat tanggal
- Soft delete tetap ada namun untuk hari libur yang sama tanggalnya, unique key akan mencegah restore (perlu pertimbangan)

## 05.5 API Specification

| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| GET | `/hari-libur` | ✅ | ALL | Halaman daftar/kalender |
| GET | `/hari-libur/create` | ✅ | MGR | Form tambah |
| POST | `/hari-libur` | ✅ | MGR | Simpan hari libur |
| GET | `/hari-libur/{id}/edit` | ✅ | MGR | Form edit |
| PUT | `/hari-libur/{id}` | ✅ | MGR | Update |
| DELETE | `/hari-libur/{id}` | ✅ | MGR | Hapus (soft) |
| POST | `/hari-libur/seed` | ✅ | MGR | Seed hari libur nasional |
| GET | `/api/v1/hari-libur/cek` | ✅ | INTERNAL | Cek apakah tanggal adalah hari kerja |

### Request: GET /api/v1/hari-libur/cek?tanggal=2026-08-17

```json
// Response:
{
  "status": "success",
  "data": {
    "date": "2026-08-17",
    "day_name": "Monday",
    "is_workday": false,
    "reason": "Hari Kemerdekaan RI"
  }
}
```

## 05.6 UI Layout

### Tampilan Kalender Bulanan
```
┌────────────────────────────────────────────────────────┐
│  MANAJEMEN HARI LIBUR                    [+Tambah]     │
│  [◀ Juli 2026]  AGUSTUS 2026  [Agustus 2026 ▼]  [▶]  │
├────────────────────────────────────────────────────────┤
│  Sen   Sel   Rab   Kam   Jum   SAB   MIN              │
│   3     4     5     6     7    [8]   [9]              │
│  10    11    12    13    14   [15]  [16]              │
│ [17]   18    19    20    21   [22]  [23]              │
│  24    25    26    27    28   [29]  [30]              │
│  31                                                    │
│                                                        │
│  Legenda: [  ] Hari Kerja  [🔴] Libur Nasional        │
│           [🟡] Weekend     [🟢] Hari Kerja Pengganti  │
├────────────────────────────────────────────────────────┤
│  DAFTAR HARI LIBUR AGUSTUS 2026                       │
│  17 Agt | Hari Kemerdekaan RI | 🔴 Libur | [Edit][Hapus]│
└────────────────────────────────────────────────────────┘
```

### Form Tambah Hari Libur
```
┌────────────────────────────────────────┐
│ Tanggal *     │ [2026-08-17]           │
│ Nama *        │ [Hari Kemerdekaan RI]  │
│ Tipe *        │ (●) Hari Libur         │
│               │ ( ) Hari Kerja Pengganti│
│ Keterangan    │ [__________________]   │
└────────────────────────────────────────┘
│              [Batal] [Simpan]          │
└────────────────────────────────────────┘
```

## 05.7 Validation Rules

| Field | Rule | Pesan Error |
|-------|------|-------------|
| date | required, date, unique:holidays,date | Tanggal sudah terdaftar |
| name | required, string, max:100 | Nama wajib diisi |
| type | required, in:holiday,workday | Tipe tidak valid |

## 05.8 Business Rules Detail

**BR-HOL-01 — Prioritas Penentuan Hari Kerja:**
```
URUTAN PRIORITAS (dari tertinggi ke terendah):
1. Entry di tabel holidays dengan type='workday' → HARI KERJA
2. Entry di tabel holidays dengan type='holiday' → HARI LIBUR
3. Hari Sabtu atau Minggu → HARI LIBUR (default)
4. Hari biasa (Senin-Jumat) → HARI KERJA (default)
```

**BR-HOL-02:** Hari libur yang sudah digunakan dalam perhitungan bunga **tidak dapat dihapus** untuk menjaga integritas data historis.

## 05.9 Acceptance Criteria

| ID | Skenario | Expected Result |
|----|----------|-----------------|
| AC-HOL-01 | Tambah 17 Agustus sebagai hari libur | Muncul di kalender dengan warna merah |
| AC-HOL-02 | Tambah tanggal yang sudah ada | Validasi gagal: "Tanggal sudah terdaftar" |
| AC-HOL-03 | Cek API apakah 17 Agustus adalah hari kerja | Response: `is_workday: false`, reason: "Hari Kemerdekaan RI" |
| AC-HOL-04 | Tambah Sabtu sebagai 'workday' | API mengembalikan `is_workday: true` untuk Sabtu tersebut |
| AC-HOL-05 | Seed hari libur nasional 2026 | Semua hari libur nasional ter-insert, duplikat di-skip |
| AC-HOL-06 | Teller mencoba tambah hari libur | HTTP 403 Forbidden |

## 05.10 Test Case

### Normal Case
| TC | Input | Expected |
|----|-------|----------|
| TC-HOL-N01 | Tambah: date=2026-12-25, name="Natal", type=holiday | Tersimpan, tampil di kalender |
| TC-HOL-N02 | Cek API: 2026-01-01 (Tahun Baru) | `is_workday: false` |
| TC-HOL-N03 | Cek API: 2026-08-03 (Senin biasa) | `is_workday: true` |

### Edge Case
| TC | Input | Expected |
|----|-------|----------|
| TC-HOL-E01 | Tambah Minggu 2026-08-09 sebagai 'workday' | Tersimpan, API return `is_workday: true` untuk 2026-08-09 |
| TC-HOL-E02 | Seed 2026, kemudian seed ulang 2026 | Duplikat di-skip, tidak error |

### Negative Case
| TC | Input | Expected |
|----|-------|----------|
| TC-HOL-NEG01 | Tambah tanggal duplikat | HTTP 422, "Tanggal sudah terdaftar" |
| TC-HOL-NEG02 | Tambah tanggal format salah: "31-08-2026" | HTTP 422, "Format tanggal tidak valid" |
| TC-HOL-NEG03 | Teller akses POST /hari-libur | HTTP 403 |

## 05.11 Risiko Implementasi

| Risiko | Mitigasi |
|--------|----------|
| Lupa handle weekend override | Unit test spesifik untuk kasus Sabtu→workday |
| Seed data hari libur tidak akurat | Gunakan data resmi SKB Menteri dari file JSON yang di-maintain |
| Performa query cek hari kerja lambat | Index pada kolom `date` dan `year`, hasil cek bisa di-cache (Redis/file cache) |

---

# MODUL 06 — MANAJEMEN BUNGA

## 06.1 Tujuan Modul

Mengelola konfigurasi rate bunga untuk simpanan tabungan dan deposito. Setiap perubahan rate bunga memiliki riwayat (audit trail), dan hanya rate yang `is_active = true` yang digunakan dalam perhitungan.

## 06.2 Functional Requirement

| ID | Requirement |
|----|-------------|
| INT-FR-01 | Manager dapat menambah, mengedit rate bunga |
| INT-FR-02 | Rate bunga memiliki tipe: `tabungan_sukarela`, `tabungan_wajib`, `deposito_3_bulan`, `deposito_6_bulan`, `deposito_12_bulan` |
| INT-FR-03 | Setiap tipe bunga hanya boleh memiliki satu rate aktif |
| INT-FR-04 | Saat Manager menambah rate baru untuk tipe yang sudah ada, rate lama otomatis dinonaktifkan |
| INT-FR-05 | Rate bunga memiliki `effective_date` — berlaku mulai tanggal tertentu |
| INT-FR-06 | Sistem menyimpan riwayat semua perubahan rate |
| INT-FR-07 | Rate tabungan dalam format persen per tahun (% p.a.) |
| INT-FR-08 | Rate deposito dalam format persen per tahun (% p.a.) |
| INT-FR-09 | Engine bunga menggunakan rate yang aktif pada tanggal perhitungan |

## 06.3 Business Flow

```
[Tambah Rate Bunga Baru]
    │
    ├─ Input: Tipe, Persentase (% p.a.), Tanggal Efektif
    │
    ├─ Validasi: effective_date >= today
    │
    ├─ DB Transaction:
    │   ├─ UPDATE interest_rates SET is_active=0 WHERE type=? AND is_active=1
    │   └─ INSERT interest_rates (type, rate, effective_date, is_active=1)
    │
    └─ Log Activity
```

## 06.4 Database Design

### Tabel: `interest_rates`

```sql
CREATE TABLE interest_rates (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type            ENUM(
                        'tabungan_sukarela',
                        'tabungan_wajib',
                        'deposito_3_bulan',
                        'deposito_6_bulan',
                        'deposito_12_bulan'
                    ) NOT NULL,
    rate_percent    DECIMAL(5,2) NOT NULL,  -- misal: 5.50 untuk 5.50% p.a.
    effective_date  DATE NOT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    notes           TEXT NULL,
    created_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    INDEX idx_type_active (type, is_active),
    INDEX idx_effective_date (effective_date),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

**Catatan:** `interest_rates` tidak menggunakan soft delete — riwayat dipertahankan dengan `is_active = 0`. Edit tidak diperbolehkan (hanya tambah baru yang menonaktifkan yang lama).

## 06.5 Kalkulasi Bunga Harian

```
✅ KEPUTUSAN BISNIS (FINAL):
   - Pembagi  : Jumlah hari kerja AKTUAL dalam tahun berjalan
   - Pembulatan: FLOOR (ke bawah, bulatkan ke satuan rupiah)

──────────────────────────────────────────────────────────────
FORMULA:

  Bunga Harian = FLOOR( (Saldo × Rate% p.a.) / Hari_Kerja_Aktual_Tahun )

DIMENSI:
  Saldo              = Rupiah (BIGINT)
  Rate % p.a.        = Persen per tahun (DECIMAL 5,2 — misal: 4.00 = 4%)
  Hari_Kerja_Aktual  = Jumlah hari kerja nyata dalam tahun berjalan
                       (Senin–Jumat, dikurangi libur nasional,
                        ditambah hari kerja pengganti jika ada)
                       Dihitung dari tabel `holidays` dan logika weekend
  Bunga Harian       = Rupiah (BIGINT, minimal 0)
──────────────────────────────────────────────────────────────

CONTOH PERHITUNGAN (tahun 2026):
  Asumsi hari kerja aktual 2026 = 247 hari
  (Catatan: nilai ini dihitung ulang setiap tahun dari tabel holidays)

  Nasabah A — Saldo Sukarela: Rp 10.000.000, Rate: 4.00% p.a.
  → Bunga Harian = FLOOR(10.000.000 × 0.04 / 247)
                 = FLOOR(400.000 / 247)
                 = FLOOR(1.619,43...)
                 = Rp 1.619

  Nasabah B — Saldo Sukarela: Rp 500.000, Rate: 4.00% p.a.
  → Bunga Harian = FLOOR(500.000 × 0.04 / 247)
                 = FLOOR(20.000 / 247)
                 = FLOOR(80,97...)
                 = Rp 80

  Nasabah C — Saldo Sukarela: Rp 5.000, Rate: 4.00% p.a.
  → Bunga Harian = FLOOR(5.000 × 0.04 / 247)
                 = FLOOR(200 / 247)
                 = FLOOR(0,81...)
                 = Rp 0 → tidak disimpan ke tabel
──────────────────────────────────────────────────────────────

CACHE JUMLAH HARI KERJA:
  Jumlah hari kerja aktual TIDAK dihitung setiap kali engine berjalan.
  Nilai ini di-cache per tahun di tabel `workday_year_counts`.
  Cache diperbarui otomatis setiap kali ada perubahan di tabel `holidays`.
  Nilai fallback jika tabel belum tersedia: gunakan 245 (estimasi konservatif).
```

## 06.6 Tabel Pendukung: `workday_year_counts`

Tabel ini menyimpan cache jumlah hari kerja aktual per tahun. Diperbarui otomatis via **Observer** setiap kali ada INSERT/UPDATE/DELETE di tabel `holidays`.

```sql
CREATE TABLE workday_year_counts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year            SMALLINT UNSIGNED NOT NULL UNIQUE,
    workday_count   SMALLINT UNSIGNED NOT NULL,  -- jumlah hari kerja aktual
    calculated_at   TIMESTAMP NOT NULL,           -- kapan terakhir dihitung ulang
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

**Cara hitung `workday_count` untuk satu tahun:**

```
workday_count = (Jumlah hari Senin–Jumat dalam tahun)
              - (Jumlah hari libur nasional yang jatuh di Senin–Jumat)
              + (Jumlah hari kerja pengganti / Sabtu-Minggu yang di-override menjadi workday)
```

**Contoh data:**

| year | workday_count | calculated_at |
|------|--------------|---------------|
| 2025 | 244 | 2025-01-01 00:00:00 |
| 2026 | 247 | 2026-01-01 00:00:00 |

**Trigger recalculate:** Setiap kali `holidays` berubah, `HolidayObserver` memanggil `WorkdayYearCount::recalculate($year)`.

## 06.7 API Specification

| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| GET | `/manajemen-bunga` | ✅ | ALL | Daftar rate bunga aktif |
| GET | `/manajemen-bunga/riwayat` | ✅ | MGR | Riwayat semua perubahan |
| GET | `/manajemen-bunga/create` | ✅ | MGR | Form tambah rate |
| POST | `/manajemen-bunga` | ✅ | MGR | Simpan rate baru |
| GET | `/api/v1/bunga/rate-aktif` | ✅ | INTERNAL | Ambil rate aktif per tipe |

### Response: GET /api/v1/bunga/rate-aktif

```json
{
  "status": "success",
  "data": [
    {
      "type": "tabungan_sukarela",
      "rate_percent": "4.00",
      "effective_date": "2026-01-01",
      "is_active": true
    },
    {
      "type": "tabungan_wajib",
      "rate_percent": "3.50",
      "effective_date": "2026-01-01",
      "is_active": true
    }
  ]
}
```

## 06.7 UI Layout

### Halaman Manajemen Bunga
```
┌──────────────────────────────────────────────────────────┐
│  MANAJEMEN BUNGA             [+ Tambah Rate Baru]        │
├──────────────────────────────────────────────────────────┤
│  RATE BUNGA AKTIF SAAT INI                              │
│  ┌────────────────────┬──────────┬────────────┬────────┐ │
│  │ Jenis              │ Rate p.a.│ Berlaku Sejak│ Status│ │
│  ├────────────────────┼──────────┼────────────┼────────┤ │
│  │ Tabungan Sukarela  │ 4.00%    │ 01 Jan 2026 │ AKTIF │ │
│  │ Tabungan Wajib     │ 3.50%    │ 01 Jan 2026 │ AKTIF │ │
│  │ Deposito 3 Bulan   │ 5.00%    │ 01 Jan 2026 │ AKTIF │ │
│  │ Deposito 6 Bulan   │ 5.50%    │ 01 Jan 2026 │ AKTIF │ │
│  │ Deposito 12 Bulan  │ 6.00%    │ 01 Jan 2026 │ AKTIF │ │
│  └────────────────────┴──────────┴────────────┴────────┘ │
├──────────────────────────────────────────────────────────┤
│  [Lihat Riwayat Perubahan]                              │
└──────────────────────────────────────────────────────────┘
```

## 06.8 Validation Rules

| Field | Rule | Pesan Error |
|-------|------|-------------|
| type | required, in: enum values | Jenis bunga tidak valid |
| rate_percent | required, numeric, between:0.01,100 | Rate harus antara 0.01% hingga 100% |
| effective_date | required, date, after_or_equal:today | Tanggal efektif minimal hari ini |

## 06.9 Acceptance Criteria

| ID | Skenario | Expected Result |
|----|----------|-----------------|
| AC-INT-01 | Manager tambah rate Sukarela 4.5% berlaku 1 Sept 2026 | Rate lama (4%) dinonaktifkan, rate baru aktif |
| AC-INT-02 | Lihat riwayat bunga Sukarela | Tampil semua histori: 4% (nonaktif), 4.5% (aktif) |
| AC-INT-03 | Teller mencoba tambah rate | HTTP 403 |
| AC-INT-04 | Input rate 0% | Validasi gagal: "Rate minimal 0.01%" |

---

# MODUL 07 — ENGINE PERHITUNGAN BUNGA HARIAN

## 07.1 Tujuan Modul

Proses otomatis yang berjalan setiap hari (pukul 00:05 WIB) untuk menghitung akumulasi bunga harian setiap nasabah yang memiliki saldo. Hasil perhitungan **disimpan sebagai akumulasi**, belum menambah saldo tabungan (saldo baru ditambah saat Posting Bunga, Modul 08).

## 07.2 Functional Requirement

| ID | Requirement |
|----|-------------|
| ENG-FR-01 | Berjalan otomatis via Laravel Scheduler setiap hari pukul 00:05 |
| ENG-FR-02 | Cek apakah tanggal kemarin adalah hari kerja (menggunakan data Modul 05) |
| ENG-FR-03 | Jika hari libur, proses dihentikan (tidak ada log bunga) |
| ENG-FR-04 | Jika hari kerja, hitung bunga untuk semua nasabah aktif yang punya saldo > 0 |
| ENG-FR-05 | Bunga dihitung berdasarkan saldo sukarela dan wajib secara terpisah |
| ENG-FR-06 | Hasil disimpan ke tabel `daily_interest_accumulations` |
| ENG-FR-07 | Proses bersifat IDEMPOTENT — satu tanggal hanya boleh ada satu record per nasabah per jenis |
| ENG-FR-08 | Jika proses gagal di tengah jalan, bisa diulang (safe to retry) |
| ENG-FR-09 | Proses berjalan dalam batch (tidak sekaligus) untuk menghindari timeout |
| ENG-FR-10 | Log hasil proses (berhasil, skip, error) dicatat di tabel `interest_engine_logs` |

## 07.3 Business Flow / Activity Diagram

```
[SCHEDULER: Setiap 00:05]
    │
    ▼
[Tentukan tanggal = KEMARIN (date('Y-m-d', strtotime('yesterday')))]
    │
    ▼
[Cek: apakah tanggal kemarin adalah hari kerja?]
    │
    ├── TIDAK (Hari Libur)
    │       │
    │       └── INSERT interest_engine_logs (date, status='skipped', reason='holiday')
    │           STOP
    │
    └── YA (Hari Kerja)
            │
            ▼
        [Ambil rate bunga aktif per tipe pada tanggal kemarin]
            │
            ▼
        [Ambil semua nasabah aktif yang punya saldo > 0]
            │
            ▼
        [BATCH PROCESSING: chunk(100)]
            │
            ▼
        [Untuk setiap nasabah:]
            │
            ├── Hitung saldo sukarela kemarin (SUM sampai tanggal kemarin)
            ├── Hitung saldo wajib kemarin
            │
            ├── Ambil jumlah hari kerja aktual tahun ini dari `workday_year_counts`
            │
            ├── Hitung bunga sukarela = FLOOR(saldo_sukarela × rate_sukarela / hari_kerja_aktual)
            ├── Hitung bunga wajib    = FLOOR(saldo_wajib × rate_wajib / hari_kerja_aktual)
            ├── Jika bunga = 0 → SKIP (tidak simpan record dengan nilai 0)
            │
            ├── Cek IDEMPOTENCY:
            │   EXISTS(daily_interest_accumulations WHERE customer_id=X AND date=kemarin AND type='sukarela')
            │   ├── YA → SKIP (sudah dihitung)
            │   └── TIDAK → INSERT record baru
            │
            └── Lanjut nasabah berikutnya
            │
            ▼
        [INSERT interest_engine_logs (date, status='success', total_customers, total_interest)]
            │
            ▼
        [SELESAI]
```

## 07.4 Database Design

### Tabel: `daily_interest_accumulations`

```sql
CREATE TABLE daily_interest_accumulations (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     BIGINT UNSIGNED NOT NULL,
    savings_type    ENUM('sukarela', 'wajib') NOT NULL,
    calculation_date DATE NOT NULL,       -- tanggal perhitungan (kemarin)
    base_balance    BIGINT UNSIGNED NOT NULL,   -- saldo yang digunakan sebagai dasar
    rate_percent    DECIMAL(5,2) NOT NULL,      -- rate yang digunakan
    interest_amount BIGINT UNSIGNED NOT NULL,   -- hasil bunga (dalam rupiah, pembulatan ke bawah)
    is_posted       TINYINT(1) NOT NULL DEFAULT 0,  -- sudah diposting ke saldo?
    posted_at       TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,

    UNIQUE KEY uq_customer_date_type (customer_id, calculation_date, savings_type),
    INDEX idx_customer (customer_id),
    INDEX idx_calculation_date (calculation_date),
    INDEX idx_is_posted (is_posted),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);
```

### Tabel: `interest_engine_logs`

```sql
CREATE TABLE interest_engine_logs (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    run_date            DATE NOT NULL,
    status              ENUM('success', 'skipped', 'failed') NOT NULL,
    reason              VARCHAR(255) NULL,         -- alasan skip/failed
    total_customers     INT UNSIGNED NULL DEFAULT 0,
    total_interest      BIGINT UNSIGNED NULL DEFAULT 0,
    duration_seconds    INT UNSIGNED NULL,
    error_message       TEXT NULL,
    created_at          TIMESTAMP NULL,

    UNIQUE KEY uq_run_date (run_date),             -- hanya satu log per hari
    INDEX idx_status (status)
);
```

## 07.5 Implementasi Scheduler

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Engine Bunga Harian: setiap hari pukul 00:05 WIB
    $schedule->command('interest:calculate-daily')
             ->dailyAt('00:05')
             ->timezone('Asia/Jakarta')
             ->withoutOverlapping()   // tidak boleh overlap
             ->onFailure(function () {
                 // Kirim notifikasi error (email/log)
                 Log::error('interest:calculate-daily FAILED');
             });

    // Posting Bunga: setiap tanggal 1, pukul 00:30 WIB
    $schedule->command('interest:post-monthly')
             ->monthlyOn(1, '00:30')
             ->timezone('Asia/Jakarta')
             ->withoutOverlapping();
}
```

## 07.6 Idempotency Guarantee

```
UNIQUE KEY uq_customer_date_type (customer_id, calculation_date, savings_type)
```

Jika command dijalankan dua kali untuk tanggal yang sama, MySQL akan throw `Duplicate entry` saat INSERT. Gunakan `INSERT IGNORE` atau `upsert` dengan `ON DUPLICATE KEY UPDATE` (no-op) untuk safe retry.

```sql
INSERT INTO daily_interest_accumulations (customer_id, savings_type, calculation_date, ...)
VALUES (?, ?, ?, ...)
ON DUPLICATE KEY UPDATE id = id;  -- no-op, tidak mengubah data
```

## 07.7 Artisan Command Specification

```
Command  : interest:calculate-daily
Deskripsi: Menghitung akumulasi bunga harian untuk semua nasabah
Options  :
  --date=YYYY-MM-DD   Override tanggal (untuk backfill/testing)
  --dry-run           Simulasi tanpa menyimpan ke database

Contoh:
  php artisan interest:calculate-daily               # auto-pilih kemarin
  php artisan interest:calculate-daily --date=2026-08-01
  php artisan interest:calculate-daily --dry-run
```

## 07.8 Acceptance Criteria

| ID | Skenario | Expected Result |
|----|----------|-----------------|
| AC-ENG-01 | Jalankan pada hari kerja normal | Record tersimpan di `daily_interest_accumulations`, log status=success |
| AC-ENG-02 | Jalankan pada hari libur nasional | Tidak ada record baru, log status=skipped, reason=holiday |
| AC-ENG-03 | Jalankan dua kali pada tanggal yang sama (hari kerja) | Eksekusi kedua tidak membuat record duplikat (idempotent) |
| AC-ENG-04 | Nasabah dengan saldo = 0 | Tidak ada record bunga untuk nasabah tersebut |
| AC-ENG-05 | Jalankan `--dry-run` | Output ke console, tidak ada perubahan database |
| AC-ENG-06 | Rate bunga berubah di tengah bulan | Perhitungan menggunakan rate aktif pada tanggal perhitungan |

## 07.9 Test Case

| TC | Skenario | Expected |
|----|----------|----------|
| TC-ENG-N01 | Hari kerja, saldo Sukarela Rp 10.000.000, rate 4% | Bunga = floor(10000000 × 0.04 / 247) = Rp 1.619 |
| TC-ENG-N02 | Hari Sabtu tanpa override | status=skipped, reason="Weekend" |
| TC-ENG-N03 | Hari Sabtu dengan override workday | status=success, bunga dihitung |
| TC-ENG-E01 | Saldo Rp 100 (sangat kecil), rate 4% | Bunga = floor(100 × 0.04 / 247) = Rp 0 (tidak disimpan jika 0) |
| TC-ENG-NEG01 | Rate bunga tidak ada (tabel kosong) | Log: failed, error="No active interest rate found" |

## 07.10 Risiko Implementasi

| Risiko | Probabilitas | Dampak | Mitigasi |
|--------|-------------|--------|----------|
| Cron tidak berjalan (server issue) | Medium | Tinggi | Monitoring cron, alert email, manual backfill via --date |
| Double run akibat server restart | Tinggi | Tinggi | UNIQUE KEY + `ON DUPLICATE KEY` |
| Timeout untuk ribuan nasabah | Medium | Medium | Chunk(100) + timeout yang cukup |
| Rate bunga tidak terdefinisi | Low | Tinggi | Guard check di awal command, fail fast |

---

# MODUL 08 — POSTING BUNGA BULANAN

## 08.1 Tujuan Modul

Proses otomatis yang berjalan setiap tanggal 1 (pukul 00:30 WIB) untuk mengakumulasi semua bunga harian bulan sebelumnya dan membuat transaksi simpanan tipe 'bunga' ke rekening nasabah.

## 08.2 Functional Requirement

| ID | Requirement |
|----|-------------|
| PST-FR-01 | Berjalan otomatis setiap tanggal 1 pukul 00:30 via Laravel Scheduler |
| PST-FR-02 | Mengambil semua akumulasi bunga bulan sebelumnya yang `is_posted = 0` |
| PST-FR-03 | Menjumlahkan bunga harian per nasabah per jenis menjadi satu nilai bulanan |
| PST-FR-04 | Membuat record di `savings_transactions` dengan type='bunga' |
| PST-FR-05 | Menandai akumulasi bunga sebagai `is_posted = 1` |
| PST-FR-06 | Proses bersifat IDEMPOTENT — periode yang sama hanya bisa diposting sekali |
| PST-FR-07 | Manager dapat melihat riwayat posting bulanan |
| PST-FR-08 | Manager dapat trigger manual posting (untuk koreksi) dengan konfirmasi |
| PST-FR-09 | Posting tidak dapat dilakukan jika periode belum selesai |

## 08.3 Business Flow

```
[SCHEDULER: Setiap tanggal 1 pukul 00:30]
    │
    ▼
[Tentukan periode = bulan lalu (YYYY-MM)]
Contoh: Jika hari ini 2026-09-01, periode = 2026-08

    │
    ▼
[Cek IDEMPOTENCY: Apakah sudah ada record di interest_posting_logs untuk periode ini?]
    │
    ├── YA → STOP (sudah diposting bulan ini)
    │
    └── TIDAK → Lanjut
            │
            ▼
        [Mulai DB Transaction]
            │
            ▼
        [Ambil semua daily_interest_accumulations
         WHERE bulan=periode AND is_posted=0
         GROUP BY customer_id, savings_type
         SUM(interest_amount)]
            │
            ▼
        [Untuk setiap baris GROUP (nasabah+jenis):]
            │
            ├── INSERT savings_transactions:
            │       type='bunga',
            │       amount=SUM(interest_amount),
            │       transaction_date=1 bulan ini,
            │       created_by=NULL (sistem)
            │
            ├── Recalculate current_balance untuk nasabah ini
            │
            └── UPDATE daily_interest_accumulations
                    SET is_posted=1, posted_at=NOW()
                    WHERE customer_id=X AND savings_type=Y AND bulan=periode
            │
            ▼
        [INSERT interest_posting_logs (period, status=success, ...)]
            │
            ▼
        [Commit DB Transaction]
            │
            ▼
        [SELESAI]
```

## 08.4 Database Design

### Tabel: `interest_posting_logs`

```sql
CREATE TABLE interest_posting_logs (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    period              VARCHAR(7) NOT NULL,   -- format: YYYY-MM
    status              ENUM('success', 'failed', 'manual') NOT NULL,
    total_customers     INT UNSIGNED NOT NULL DEFAULT 0,
    total_interest      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    posted_by           BIGINT UNSIGNED NULL,  -- NULL jika otomatis, user_id jika manual
    notes               TEXT NULL,
    created_at          TIMESTAMP NULL,

    UNIQUE KEY uq_period (period),
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
);
```

## 08.5 Artisan Command Specification

```
Command  : interest:post-monthly
Deskripsi: Posting bunga bulanan ke rekening tabungan nasabah
Options  :
  --period=YYYY-MM    Override periode (untuk testing/manual)
  --force             Force posting meski sudah pernah (DANGER, hanya untuk Manager)
  --dry-run           Simulasi tanpa menyimpan

Contoh:
  php artisan interest:post-monthly                     # auto-pilih bulan lalu
  php artisan interest:post-monthly --period=2026-08
  php artisan interest:post-monthly --period=2026-08 --force
```

## 08.6 UI: Halaman Riwayat Posting

```
┌──────────────────────────────────────────────────────────┐
│  RIWAYAT POSTING BUNGA              [▶ Posting Manual]  │
├──────────────────────────────────────────────────────────┤
│ Periode  | Status  | Jml Nasabah | Total Bunga | Diposting │
│ 2026-08  | SUCCESS | 150         | Rp 12.500.000| Otomatis │
│ 2026-07  | SUCCESS | 148         | Rp 11.800.000| Otomatis │
│ 2026-06  | MANUAL  | 148         | Rp 11.200.000| Manager  │
└──────────────────────────────────────────────────────────┘
```

## 08.7 Acceptance Criteria

| ID | Skenario | Expected Result |
|----|----------|-----------------|
| AC-PST-01 | Posting berjalan tanggal 1 September | Semua akumulasi Agustus diposting, transaksi 'bunga' dibuat |
| AC-PST-02 | Jalankan posting dua kali untuk periode sama | Eksekusi kedua di-skip (idempotent) |
| AC-PST-03 | Nasabah tanpa akumulasi bunga (saldo selalu 0) | Tidak ada transaksi bunga dibuat untuk nasabah tersebut |
| AC-PST-04 | Manager trigger manual posting | Konfirmasi dialog, lanjut posting, log `posted_by = user_id` |
| AC-PST-05 | Setelah posting, saldo nasabah bertambah | `savings_transactions` type='bunga' ada, `current_balance` terupdate |

---

# MODUL 09 — MANAJEMEN DEPOSITO

## 09.1 Tujuan Modul

Mengelola deposito berjangka nasabah. Nasabah dapat menempatkan dana dalam deposito dengan tenor tertentu dan mendapatkan bunga tetap yang ditransfer otomatis ke rekening tabungan setiap bulan pada tanggal registrasi.

## 09.2 Functional Requirement

| ID | Requirement |
|----|-------------|
| DEP-FR-01 | Teller/Manager dapat membuka deposito baru untuk nasabah |
| DEP-FR-02 | Deposito memiliki tenor: 3, 6, atau 12 bulan |
| DEP-FR-03 | Nominal deposito minimum: Rp 1.000.000 |
| DEP-FR-04 | Form input deposito TIDAK memiliki field "deposito awal" (sesuai requirement bisnis) |
| DEP-FR-05 | Tanggal mulai = tanggal hari ini, tanggal jatuh tempo = tanggal mulai + tenor |
| DEP-FR-06 | Setiap bulan, pada tanggal yang sama dengan tanggal registrasi, bunga ditransfer ke tabungan sukarela |
| DEP-FR-07 | Perpanjangan deposito dapat dilakukan sebelum atau pada saat jatuh tempo |
| DEP-FR-08 | Pencairan (sebelum jatuh tempo) dapat dilakukan dengan penalti (rate ditentukan oleh Manager) |
| DEP-FR-09 | Status deposito: `active`, `matured`, `extended`, `liquidated` |
| DEP-FR-10 | Nasabah dapat memiliki lebih dari satu deposito aktif |
| DEP-FR-11 | Laporan deposito dapat dicetak |
| DEP-FR-12 | Setelah deposito jatuh tempo tanpa perpanjangan, status menjadi `matured` secara otomatis |

## 09.3 Business Flow

### Pembukaan Deposito
```
[Input Nomor Rekening] → [Auto-fetch data nasabah]
    │
    ├─ Pilih Tenor (3/6/12 bulan)
    ├─ Input Nominal (min Rp 1.000.000)
    ├─ Tanggal Mulai = HARI INI (auto)
    ├─ Tanggal Jatuh Tempo = auto-hitung
    ├─ Rate Bunga = ambil rate aktif dari Modul 06
    │
    └─ Simpan → status=active → Log Activity
```

### Transfer Bunga Bulanan (Otomatis)
```
[SCHEDULER: Setiap hari pukul 01:00]
    │
    ▼
[Ambil semua deposito status='active' yang tanggal_registrasi.DAY = hari ini]
    │
    ▼
[Untuk setiap deposito:]
    │
    ├─ Hitung bunga = (nominal × rate) / 12  (bunga bulanan = rate tahunan / 12)
    │
    ├─ Cek IDEMPOTENCY: apakah sudah ada transfer bulan ini?
    │   ├─ YA → SKIP
    │   └─ TIDAK → Lanjut
    │
    ├─ INSERT savings_transactions:
    │       customer_id = nasabah
    │       type = 'bunga' (atau type baru: 'bunga_deposito')
    │       amount = nominal_bunga
    │       notes = 'Bunga Deposito No. DEP-XXXXX periode Agustus 2026'
    │
    ├─ Recalculate saldo tabungan nasabah
    │
    └─ INSERT deposit_interest_payments (idempotency record)
```

### Perpanjangan Deposito
```
[Pilih Deposito] → [Pilih Tenor Baru] → [Konfirmasi]
    │
    ├─ UPDATE fixed_deposits: status='extended', extended_at=NOW()
    ├─ INSERT fixed_deposits baru (copy nominal, tenor baru, start_date=hari ini)
    └─ Log Activity
```

### Pencairan Deposito
```
[Pilih Deposito] → [Konfirmasi Pencairan]
    │
    ├─ Jika pencairan sebelum jatuh tempo:
    │   ├─ Hitung penalti = nominal × penalty_rate (dari setting)
    │   └─ Tampilkan: "Nominal dicairkan = Nominal - Penalti"
    │
    ├─ UPDATE fixed_deposits: status='liquidated', liquidated_at=NOW()
    │
    ├─ INSERT savings_transactions:
    │       type = 'sukarela' (penambahan ke tabungan)
    │       amount = nominal - penalti (jika ada)
    │       notes = 'Pencairan Deposito No. DEP-XXXXX'
    │
    └─ Recalculate saldo → Log Activity
```

## 09.4 Database Design

### Tabel: `fixed_deposits`

```sql
CREATE TABLE fixed_deposits (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    number          VARCHAR(20) NOT NULL UNIQUE,   -- DEP-YYYYMM-NNNNN
    customer_id     BIGINT UNSIGNED NOT NULL,
    amount          BIGINT UNSIGNED NOT NULL,       -- nominal deposito
    tenor_months    TINYINT UNSIGNED NOT NULL,      -- 3, 6, 12
    rate_percent    DECIMAL(5,2) NOT NULL,          -- rate saat pembukaan (locked)
    start_date      DATE NOT NULL,
    maturity_date   DATE NOT NULL,
    status          ENUM('active','matured','extended','liquidated') NOT NULL DEFAULT 'active',
    extended_from_id BIGINT UNSIGNED NULL,          -- FK ke deposito sebelumnya (saat perpanjangan)
    liquidated_at   TIMESTAMP NULL,
    matured_at      TIMESTAMP NULL,
    notes           TEXT NULL,
    created_by      BIGINT UNSIGNED NULL,
    updated_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL,

    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_maturity_date (maturity_date),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (extended_from_id) REFERENCES fixed_deposits(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### Tabel: `deposit_interest_payments`

```sql
CREATE TABLE deposit_interest_payments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fixed_deposit_id BIGINT UNSIGNED NOT NULL,
    period          VARCHAR(7) NOT NULL,    -- YYYY-MM
    interest_amount BIGINT UNSIGNED NOT NULL,
    savings_txn_id  BIGINT UNSIGNED NULL,  -- FK ke savings_transactions yang dibuat
    paid_at         TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,

    UNIQUE KEY uq_deposit_period (fixed_deposit_id, period),  -- idempotency key
    FOREIGN KEY (fixed_deposit_id) REFERENCES fixed_deposits(id),
    FOREIGN KEY (savings_txn_id) REFERENCES savings_transactions(id) ON DELETE SET NULL
);
```

## 09.5 API Specification

| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| GET | `/deposito` | ✅ | ALL | Daftar deposito |
| GET | `/deposito/create` | ✅ | MGR,TLR | Form buka deposito baru |
| POST | `/deposito` | ✅ | MGR,TLR | Simpan deposito baru |
| GET | `/deposito/{id}` | ✅ | ALL | Detail deposito |
| POST | `/deposito/{id}/perpanjang` | ✅ | MGR,TLR | Perpanjang deposito |
| POST | `/deposito/{id}/cairkan` | ✅ | MGR,TLR | Cairkan deposito |
| POST | `/deposito/cetak` | ✅ | ALL | Download PDF laporan |

## 09.6 Artisan Command: Transfer Bunga Deposito

```
Command  : deposit:pay-interest
Deskripsi: Transfer bunga deposito ke tabungan sukarela
Options  :
  --date=YYYY-MM-DD   Override tanggal (untuk testing)
  --dry-run           Simulasi
```

## 09.7 UI Layout

### Form Buka Deposito
```
┌─────────────────────────────────────────────────────┐
│  PEMBUKAAN DEPOSITO BARU                            │
├─────────────────────────────────────────────────────┤
│  No. Rekening *   │ [____________] [🔍 Cari]        │
│  Nama Nasabah     │ [Auto-filled]                   │
├─────────────────────────────────────────────────────┤
│  Nominal *        │ Rp [__________________]         │
│  Tenor *          │ ( ) 3 Bulan (5.00% p.a.)        │
│                   │ ( ) 6 Bulan (5.50% p.a.)        │
│                   │ (●) 12 Bulan (6.00% p.a.)       │
│  Tanggal Mulai    │ [2026-08-05] (auto)             │
│  Tanggal JT       │ [2027-08-05] (auto-hitung)      │
│  Bunga/Bulan      │ Rp 500.000 (auto-hitung)        │
├─────────────────────────────────────────────────────┤
│                      [Batal]  [Buka Deposito]       │
└─────────────────────────────────────────────────────┘
```

### Halaman Detail Deposito
```
┌────────────────────────────────────────────────────────┐
│  DEPOSITO DEP-202608-00001          Status: [AKTIF]    │
├────────────────────────────────────────────────────────┤
│  Nasabah          : Budi Santoso (202608-00001)        │
│  Nominal          : Rp 10.000.000                      │
│  Tenor            : 12 Bulan                          │
│  Rate             : 6.00% p.a.                        │
│  Tanggal Mulai    : 05 Agustus 2026                   │
│  Tanggal Jatuh Tempo: 05 Agustus 2027                 │
│  Bunga per Bulan  : Rp 50.000                         │
├────────────────────────────────────────────────────────┤
│  RIWAYAT PEMBAYARAN BUNGA                             │
│  Periode | Nominal Bunga | Status | Tanggal Transfer  │
│  2026-08 | Rp 50.000    | Dibayar | 05 Sep 2026       │
├────────────────────────────────────────────────────────┤
│  [🔄 Perpanjang]  [💸 Cairkan]  [🖨 Cetak]           │
└────────────────────────────────────────────────────────┘
```

## 09.8 Validation Rules

| Field | Rule | Pesan Error |
|-------|------|-------------|
| customer_number | required, exists aktif | Nasabah tidak ditemukan |
| amount | required, integer, min:1000000 | Minimal deposito Rp 1.000.000 |
| tenor_months | required, in:3,6,12 | Tenor tidak valid |

## 09.9 Acceptance Criteria

| ID | Skenario | Expected Result |
|----|----------|-----------------|
| AC-DEP-01 | Buka deposito 12 bulan Rp 10jt | Tersimpan, maturity_date = start + 12 bulan, status=active |
| AC-DEP-02 | Transfer bunga bulanan hari ini | Transaksi 'bunga_deposito' dibuat di savings, idempotency terjaga |
| AC-DEP-03 | Transfer bunga dijalankan dua kali | Hanya satu record yang dibuat |
| AC-DEP-04 | Perpanjang deposito yang sudah matured | Deposito baru dibuat, deposito lama status=extended |
| AC-DEP-05 | Cairkan deposito sebelum jatuh tempo | Penalti dihitung, saldo tabungan bertambah (setelah penalti) |
| AC-DEP-06 | Nasabah mencoba buka deposito < Rp 1jt | Validasi gagal |

## 09.10 Risiko Implementasi

| Risiko | Mitigasi |
|--------|----------|
| Scheduler transfer bunga bertabrakan dengan posting bunga | Pisahkan jam: deposit:pay-interest jam 01:00, posting jam 00:30 |
| Rate deposito berubah setelah deposito buka | Rate di-lock pada saat pembukaan di kolom `fixed_deposits.rate_percent` |
| Bunga deposito > saldo bulan sebelumnya | Bunga deposito terpisah dari tabungan, tidak boleh dicampur |

---

# MODUL 10 — DASHBOARD

## 10.1 Functional Requirement

| ID | Requirement |
|----|-------------|
| DSH-FR-01 | Dashboard menampilkan statistik real-time |
| DSH-FR-02 | Widget: Total Nasabah Aktif, Total Blacklist |
| DSH-FR-03 | Widget: Total Simpanan Hari Ini (Masuk) |
| DSH-FR-04 | Widget: Total Penarikan Hari Ini (Keluar) |
| DSH-FR-05 | Widget: Total Saldo Simpanan Seluruh Nasabah |
| DSH-FR-06 | Widget: Total Deposito Aktif |
| DSH-FR-07 | Grafik transaksi 7 hari terakhir (Masuk vs Keluar) |
| DSH-FR-08 | Tabel: 5 Transaksi Terakhir |
| DSH-FR-09 | Tabel: Deposito yang jatuh tempo dalam 30 hari ke depan |
| DSH-FR-10 | Status engine bunga: kapan terakhir berjalan |

## 10.2 UI Layout

```
┌───────────────────────────────────────────────────────────┐
│  DASHBOARD                    📅 Selasa, 5 Agustus 2026  │
├─────────────┬─────────────┬─────────────┬─────────────────┤
│ 👥 Nasabah  │ 💰 Masuk    │ 💸 Keluar   │ 🏦 Total Saldo │
│    Aktif    │   Hari Ini  │   Hari Ini  │    Tabungan     │
│    1.250    │ Rp 25 Jt   │ Rp 8 Jt    │ Rp 2,5 Miliar  │
├─────────────┴─────────────┴─────────────┴─────────────────┤
│ 📊 Grafik Transaksi 7 Hari Terakhir                       │
│ [Bar Chart: Masuk (hijau) vs Keluar (merah)]              │
├──────────────────────────┬────────────────────────────────┤
│ 📋 Transaksi Terakhir    │ ⚠️ Deposito JT 30 Hari        │
│ Tgl | Nasabah | Jenis | Nominal│ No. Dep | Nasabah | JT | Nominal│
│ ... | ...     | ...   | ...    │ ...     | ...     | ...| ...    │
├──────────────────────────┴────────────────────────────────┤
│ ⚙️ Status Engine Bunga: Terakhir berjalan: 5 Agt 2026 00:05│
│ ✅ Sukses | Total nasabah: 1.248 | Total bunga: Rp 45 Jt  │
└───────────────────────────────────────────────────────────┘
```

---

# MODUL 11 — LAPORAN

## 11.1 Jenis Laporan

| Kode | Nama Laporan | Scope | Format |
|------|--------------|-------|--------|
| RPT-01 | Laporan Data Karyawan | Semua/periode | PDF |
| RPT-02 | Laporan Data Nasabah | Semua/periode joined | PDF |
| RPT-03 | Laporan Transaksi Simpanan | Per nasabah/periode | PDF |
| RPT-04 | Laporan Transaksi Penarikan | Semua/per nasabah/periode | PDF |
| RPT-05 | Laporan Rekap Transaksi Bulanan | Bulan/tahun | PDF |
| RPT-06 | Laporan Deposito Aktif | Snapshot saat cetak | PDF |
| RPT-07 | Laporan Riwayat Deposito | Per nasabah | PDF |
| RPT-08 | Laporan Bunga Harian | Per periode | PDF |
| RPT-09 | Laporan Posting Bunga | Per bulan | PDF |

## 11.2 Format Kolom Laporan Transaksi

Setiap laporan transaksi WAJIB memiliki dua kolom terpisah:

| Tanggal | Kode Trx | Jenis | Keterangan | **Masuk** | **Keluar** | Saldo |
|---------|----------|-------|------------|-----------|------------|-------|
| 01/08 | SI-00001 | Sukarela | - | Rp 500.000 | - | Rp 500.000 |
| 02/08 | PE-00001 | Penarikan | - | - | Rp 100.000 | Rp 400.000 |
| 01/09 | BI-00001 | Bunga | Bunga Agt | Rp 1.095 | - | Rp 401.095 |

**Aturan pengisian:**
- Kolom **Masuk**: diisi jika type = `pokok`, `wajib`, `sukarela`, `bunga`, `bunga_deposito`
- Kolom **Keluar**: diisi jika type = `penarikan`

## 11.3 Header Laporan (Standar)

```
┌──────────────────────────────────────────────────────────┐
│  [LOGO KOPERASI]   KOPERASI SIMPAN PINJAM [NAMA]         │
│  Jl. [ALAMAT KOPERASI]                                   │
├──────────────────────────────────────────────────────────┤
│  LAPORAN [NAMA LAPORAN]                                  │
│  Periode: [dari] s/d [sampai]                            │
│  Dicetak: [Nama User] ([Username]) | [Tanggal Cetak]    │
├──────────────────────────────────────────────────────────┤
│  [ISI TABEL LAPORAN]                                     │
├──────────────────────────────────────────────────────────┤
│  Dibuat oleh:              Diketahui oleh:              │
│  _______________           _______________               │
│  [Nama Teller]             [Nama Manager]               │
└──────────────────────────────────────────────────────────┘
```

---

# MODUL 12 — ACTIVITY LOG

## 12.1 Functional Requirement

| ID | Requirement |
|----|-------------|
| ACT-FR-01 | Semua aktivitas CRUD di semua modul tercatat secara otomatis |
| ACT-FR-02 | Log mencatat: user, aksi, model, model ID, data lama, data baru, waktu, IP address |
| ACT-FR-03 | Manager dapat melihat semua activity log |
| ACT-FR-04 | Log dapat difilter by user, aksi, tanggal, modul |
| ACT-FR-05 | Log tidak dapat dihapus oleh siapapun |
| ACT-FR-06 | Log minimal disimpan selama 1 tahun |

## 12.2 Implementasi: Spatie Activity Log

Gunakan package `spatie/laravel-activitylog`. Konfigurasi:

```php
// Setiap model yang ingin dilog:
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SavingsTransaction extends Model {
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Transaksi Simpanan {$eventName}");
    }
}
```

## 12.3 Database Design

Menggunakan tabel bawaan Spatie:

```sql
-- Tabel: activity_log (dibuat oleh package Spatie)
CREATE TABLE activity_log (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_name        VARCHAR(255) NULL,
    description     TEXT NOT NULL,
    subject_type    VARCHAR(255) NULL,
    event           VARCHAR(255) NULL,
    subject_id      BIGINT UNSIGNED NULL,
    causer_type     VARCHAR(255) NULL,
    causer_id       BIGINT UNSIGNED NULL,
    properties      JSON NULL,        -- data lama dan baru
    batch_uuid      CHAR(36) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

## 12.4 Aksi yang Wajib Di-log

| Modul | Aksi | Detail |
|-------|------|--------|
| Auth | Login | IP, user agent |
| Auth | Logout | — |
| Auth | Failed login | IP, username yang dicoba |
| User | Create/Update/Delete | Semua field |
| Nasabah | Create/Update/Delete | Semua field |
| Simpanan | Create/Update/Delete | customer_id, amount, type |
| Penarikan | Create/Update/Delete | customer_id, amount |
| Deposito | Create/Extend/Liquidate | semua field |
| Hari Libur | Create/Update/Delete | date, name, type |
| Bunga | Tambah rate baru | type, rate_percent |
| Reset Data | Execute | WARNING level |

## 12.5 UI Layout

```
┌──────────────────────────────────────────────────────────────┐
│  ACTIVITY LOG                                                │
│  Filter: [User ▼] [Modul ▼] [Dari Tgl] [s/d Tgl] [Cari]   │
├──────────────────────────────────────────────────────────────┤
│ Waktu          | User    | Aksi   | Modul    | Detail       │
│ 05/08 10:30:00 | manager | UPDATE | Nasabah  | ID:5 Nama:...│
│ 05/08 10:25:00 | teller1 | CREATE | Simpanan | Rp 500.000   │
│ 05/08 09:00:00 | teller1 | LOGIN  | Auth     | IP:192.168..│
└──────────────────────────────────────────────────────────────┘
```

---

# 17. URUTAN IMPLEMENTASI GLOBAL

Berdasarkan dependency antar modul, urutan implementasi yang direkomendasikan:

## FASE 0 — Foundation (Hari 1-2)
*Perbaikan critical bugs pada sistem yang sudah ada*

1. Fix schema database: ubah `unsignedInteger` → `BIGINT UNSIGNED` untuk kolom nominal
2. Tambah kolom `deleted_at` ke semua tabel yang belum ada
3. Tambah kolom `created_by`, `updated_by` ke tabel transaksional
4. Implementasikan Middleware RBAC (CheckRole)
5. Fix validasi saldo server-side di `WithdrawalController`
6. Gunakan DB locking pada operasi baca-tulis saldo
7. Implementasikan recalculate balance saat edit/hapus
8. Disable route `/register` publik
9. Tambah auth middleware ke API endpoint saldo
10. Install `spatie/laravel-activitylog`

## FASE 1 — Modul Hari Libur (Hari 3-4)
*Prerequisite untuk Engine Bunga*

11. Migration tabel `holidays`
12. Model `Holiday` + HolidayController + Views (CRUD)
13. API endpoint: cek hari kerja
14. Seed hari libur nasional Indonesia 2026
15. Unit test: penentuan hari kerja

## FASE 2 — Manajemen Bunga (Hari 5)

16. Migration tabel `interest_rates`
17. Model `InterestRate` + Controller + Views
18. Seed rate bunga default

## FASE 3 — Engine Bunga Harian (Hari 6-7)

19. Migration tabel `daily_interest_accumulations`
20. Migration tabel `interest_engine_logs`
21. Artisan Command `interest:calculate-daily`
22. Register scheduler di Kernel
23. Unit test + integration test idempotency

## FASE 4 — Posting Bunga Bulanan (Hari 8-9)

24. Migration tabel `interest_posting_logs`
25. Artisan Command `interest:post-monthly`
26. Tambah type `bunga` ke enum `savings_transactions.type`
27. Register scheduler di Kernel
28. Unit test idempotency posting

## FASE 5 — Manajemen Deposito (Hari 10-14)

29. Migration tabel `fixed_deposits`
30. Migration tabel `deposit_interest_payments`
31. Model `FixedDeposit` + `DepositInterestPayment`
32. Controller: Buka, Detail, Perpanjang, Cairkan
33. Views: Form, Index, Show, Print
34. Artisan Command `deposit:pay-interest`
35. Register scheduler di Kernel
36. Integration test alur lengkap deposito

## FASE 6 — Enhancement Modul yang Ada (Hari 15-17)

37. Ganti dropdown nasabah → input nomor rekening + AJAX lookup di semua form transaksi
38. Pecah kolom "Nominal" → "Masuk" dan "Keluar" di semua DataTable dan laporan PDF
39. Tambah field `joined_at` NOT NULL dengan default `today` di form nasabah
40. Fix null-check di API saldo nasabah (customer tanpa deposit)

## FASE 7 — Dashboard & Laporan (Hari 18-20)

41. Dashboard: widget statistik real-time
42. Dashboard: grafik Chart.js 7 hari terakhir
43. Dashboard: tabel deposito yang akan jatuh tempo
44. Laporan: sesuaikan semua template PDF dengan header standar
45. Laporan: tambah RPT-08 (Bunga Harian) dan RPT-09 (Posting Bunga)

## FASE 8 — Security & Hardening (Hari 21-22)

46. Rate limiting pada login (5 attempts/minute)
47. Password policy di semua Form Request
48. Hapus `Artisan::call('migrate:fresh --seed')` dari HomeController — ganti dengan seeder yang lebih aman di environment development saja
49. Tambah breadcrumb ke semua halaman
50. Review semua `{!! $variable !!}` → ganti dengan `{{ $variable }}` kecuali yang memang butuh raw HTML

---

## Ringkasan Dependency Graph

```
[Hari Libur] ──────────────────────────────┐
                                             ▼
[Manajemen Bunga] ──────────────────► [Engine Bunga Harian]
                                             │
                                             ▼
                                     [Posting Bunga Bulanan]

[Nasabah] ──► [Simpanan] ──┐
               [Penarikan] ──► [Engine Bunga] ──► [Deposito]
                               [Posting Bunga]

[Semua Modul] ──────────────────────────────► [Activity Log]
                                             ► [Dashboard]
                                             ► [Laporan]
```

---

*Dokumen ini adalah living document. Setiap perubahan requirement harus diupdate di FSD ini sebelum implementasi.*

**Versi Dokumen:**
| Versi | Tanggal | Perubahan | Oleh |
|-------|---------|-----------|------|
| 1.0 | 05/08/2026 | Initial release | System Architect |
