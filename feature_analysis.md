# Analisis Fitur Utama - Sistem Koperasi Swamitra Karya Bersama

## Daftar Fitur Utama dan Deskripsi

### 1. **Manajemen Pengguna (Karyawan)**

- **Deskripsi**: Modul untuk mengelola seluruh data karyawan koperasi beserta informasi akun dan profilnya.
- **Fitur**:
    - CRUD data karyawan
    - Melihat detail data karyawan
    - Mengubah data karyawan
    - Menghapus data karyawan
    - Cetak laporan data karyawan
    - Manajemen profil pengguna
    - Manajemen informasi pribadi
    - Reset data sistem
- **Controller**: `UserController.php`
- **Model**: `User.php`
- **Views**: `resources/views/pages/user/`

### 2. **Manajemen Nasabah**

- **Deskripsi**: Modul untuk mengelola seluruh data nasabah koperasi.
- **Fitur**:
    - CRUD data nasabah
    - Melihat detail data nasabah
    - Mengubah data nasabah
    - Menghapus data nasabah
    - Manajemen status nasabah
        - Aktif
        - Blacklist
    - Cetak laporan data nasabah
    - Tracking tanggal bergabung nasabah
- **Controller**: `CustomerController.php`
- **Model**: `Customer.php`
- **Views**: `resources/views/pages/customer/`

### 3. **Manajemen Hari Libur**

- **Deskripsi**: Modul untuk mengatur hari kerja dan hari libur dalam satu bulan pada setiap tahun.
- **Fitur**:
    - CRUD data hari libur
    - Penentuan hari kerja
    - Penentuan hari libur
    - Pengaturan kalender kerja tahunan
    - Pengaturan kalender kerja bulanan

### 4. **Validasi Transaksi**

- **Deskripsi**: Modul untuk melakukan validasi transaksi nasabah.
- **Fitur**:
    - Validasi transaksi
    - Cetak validasi transaksi
    - Cetak slip validasi transaksi
    - Riwayat validasi transaksi

### 5. **Transaksi Simpanan**

- **Deskripsi**: Modul untuk mengelola seluruh transaksi simpanan nasabah.
- **Fitur**:
    - Input simpanan baru
    - Manajemen jenis simpanan
    - Update transaksi simpanan
    - Riwayat transaksi simpanan
    - Tracking simpanan nasabah
    - Cetak laporan simpanan
- **Controller**: `DepositController.php`
- **Model**: `Deposit.php`
- **Views**: `resources/views/pages/transaction/deposit/`

### 6. **Transaksi Penarikan**

- **Deskripsi**: Modul untuk mengelola transaksi penarikan dana nasabah.
- **Fitur**:
    - Penarikan simpanan
    - Validasi saldo sebelum penarikan
    - Riwayat penarikan
    - Tracking transaksi penarikan
    - Cetak laporan penarikan
- **Controller**: `WithdrawalController.php`
- **Model**: `Withdrawal.php`
- **Views**: `resources/views/pages/transaction/withdrawal/`

### 7. **Manajemen Bunga Simpanan**

- **Deskripsi**: Modul untuk menghitung bunga simpanan harian.
- **Fitur**:
    - Pencatatan bunga harian
    - Perhitungan bunga berdasarkan hari kerja
    - Perhitungan nominal bunga
    - Rekap bunga bulanan
    - Cetak transaksi bunga setiap tanggal 1

### 8. **Manajemen Bunga Deposito**

- **Deskripsi**: Modul untuk mengelola bunga deposito.
- **Fitur**:
    - Pencatatan bunga deposito bulanan
    - Perhitungan nominal bunga deposito
    - Transfer bunga ke rekening tabungan setiap tanggal registrasi
    - Riwayat transfer bunga
    - Cetak transaksi bunga deposito

### 9. **Dashboard dan Laporan**

- **Deskripsi**: Halaman utama yang menampilkan ringkasan informasi koperasi.
- **Fitur**:
    - Dashboard informasi umum
    - Ringkasan transaksi
    - Ringkasan nasabah
    - Ringkasan simpanan
    - Ringkasan penarikan
    - Navigasi menu utama
    - Profil pengguna
    - Pengaturan akun
- **Controller**: `HomeController.php`
- **Views**: `resources/views/pages/dashboard.blade.php`

### 10. **Autentikasi dan Keamanan**

- **Deskripsi**: Modul autentikasi dan keamanan sistem.
- **Fitur**:
    - Login
    - Logout
    - Middleware Authentication
    - Manajemen Session
    - Proteksi Route
    - Proteksi Menu
    - Manajemen Hak Akses (Role & Permission)
- **Views**: `resources/views/auth/login.blade.php`

### 11. **Ketentuan Bisnis (Business Rules)**

Berikut merupakan aturan bisnis yang harus diterapkan pada seluruh sistem.

- Tidak ada potongan administrasi pada seluruh transaksi.
- Saat registrasi nasabah deposito, input deposito awal dihilangkan.
- Pada transaksi nasabah, pencarian dilakukan menggunakan Nomor Rekening.
- Pada seluruh laporan transaksi, kolom "Nominal" diubah menjadi:
    - Nominal Masuk
    - Nominal Keluar

## Arsitektur Sistem

### Struktur Model (Relasi Database):

- **Customer** memiliki relasi dengan: Deposits, Loans, Collaterals, Foreclosures, Visits
- **Loan** memiliki relasi dengan: Customer, Collateral, Deposits
- **User** (Karyawan) sebagai pengelola sistem

### Teknologi yang Digunakan:

- **Framework**: Laravel
- **Database**: MySQL (berdasarkan konfigurasi standar Laravel)
- **UI Framework**: AdminLTE
- **Fitur Tambahan**:
    - DataTables untuk tampilan tabel interaktif
    - DomPDF untuk generasi laporan PDF
    - Carbon untuk manajemen tanggal

## Kesimpulan

Sistem Koperasi Swamitra Karya Bersama adalah aplikasi manajemen koperasi yang komprehensif dengan fokus pada:

1. **Manajemen Data Master** (Karyawan dan Nasabah)
2. **Transaksi Keuangan** (Simpanan, Penarikan, Validasi Transaksi, Bunga Simpanan, Bunga Deposito)
3. **Manajemen Risiko dan Operasional** (Hari Libur, Kolektor, Nasabah Bermasalah, Penarikan Jaminan)
4. **Pelaporan dan Cetak Dokumen**
5. **Autentikasi, Keamanan, dan Hak Akses**

Sistem ini dirancang untuk mendukung operasi harian koperasi dengan fitur-fitur yang mencakup pengelolaan data master, transaksi nasabah, validasi transaksi, pengaturan kalender kerja, perhitungan bunga, manajemen keamanan, serta pelaporan dan pencetakan dokumen.
