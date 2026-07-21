# Implementation Plan - Gap Analysis Koperasi Swamitra Karya Bersama

Urutan perubahan disusun dari **risiko paling kecil ke paling besar**.  
Prinsip yang digunakan:

- minimal changes
- tanpa refactor
- tanpa mengubah arsitektur
- tanpa mengubah coding style
- lewati perubahan yang ternyata tidak diperlukan berdasarkan implementasi aktual

---

## 1) Penyesuaian label dan format laporan transaksi

### Nama perubahan

Standarisasi label nominal pada laporan transaksi menjadi format yang sesuai requirement baru.

### Prioritas

Low

### Modul yang terdampak

- Transaksi Simpanan
- Transaksi Penarikan
- Transaksi Pinjaman
- Transaksi Pembayaran Pinjaman
- Kolektor / laporan terkait jika ada tabel nominal

### Database yang terdampak

- Tidak ada

### Backend yang terdampak

- Kemungkinan kecil: controller print jika ada label/title yang dibentuk di backend
- Jika data agregasi masih memakai kolom lama, query report disesuaikan seperlunya

### Frontend yang terdampak

- View PDF laporan
- Tabel index transaksi bila menampilkan label nominal
- Label kolom pada detail/print report

### Laporan yang terdampak

- Laporan simpanan
- Laporan penarikan
- Laporan pinjaman
- Laporan pembayaran pinjaman
- Laporan kolektor jika ada nominal yang ditampilkan

### Daftar file yang akan diubah

- `resources/views/pages/transaction/deposit/print.blade.php`
- `resources/views/pages/transaction/withdrawal/print.blade.php`
- `resources/views/pages/transaction/loan/print.blade.php`
- `resources/views/pages/transaction/installment/print.blade.php`
- file report lain yang masih memakai label `Nominal`

### Estimasi tingkat risiko perubahan

Low

---

## 2) Konsistensi tampilan nomor rekening pada transaksi

### Nama perubahan

Menstandarkan tampilan dan lookup nasabah berbasis Nomor Rekening pada form dan list transaksi.

### Prioritas

Low

### Modul yang terdampak

- Transaksi Simpanan
- Transaksi Penarikan
- Transaksi Pinjaman
- Transaksi Pembayaran Pinjaman
- Kolektor

### Database yang terdampak

- Tidak ada, jika `customers.number` sudah digunakan sebagai field utama

### Backend yang terdampak

- Controller transaksi jika label/lookup customer masih tidak konsisten
- Query filter customer bila masih memakai pencarian lain

### Frontend yang terdampak

- Dropdown/select customer
- Tabel index transaksi
- Detail transaksi
- View print transaksi

### Laporan yang terdampak

- Laporan transaksi yang menampilkan identitas nasabah

### Daftar file yang akan diubah

- `app/Http/Controllers/DepositController.php`
- `app/Http/Controllers/WithdrawalController.php`
- `app/Http/Controllers/LoanController.php`
- `app/Http/Controllers/InstallmentController.php`
- `resources/views/pages/transaction/deposit/*`
- `resources/views/pages/transaction/withdrawal/*`
- `resources/views/pages/transaction/loan/*`
- `resources/views/pages/transaction/installment/*`
- `resources/views/pages/collection/*` bila masih menampilkan identitas nasabah tidak konsisten

### Estimasi tingkat risiko perubahan

Low

---

## 3) Penyesuaian tampilan dan validasi data nasabah

### Nama perubahan

Menyesuaikan form dan validasi data nasabah agar selaras dengan requirement status dan tracking nasabah.

### Prioritas

Medium

### Modul yang terdampak

- Manajemen Nasabah

### Database yang terdampak

- Tidak ada perubahan skema bila field yang ada sudah cukup
- Jika ditemukan mismatch field wajib, hanya validasi yang disesuaikan

### Backend yang terdampak

- `CustomerController`
- `StoreCustomerRequest`
- `UpdateCustomerRequest`

### Frontend yang terdampak

- Form create nasabah
- Form edit nasabah
- Detail nasabah bila ada field yang perlu ditampilkan ulang

### Laporan yang terdampak

- Laporan nasabah bila ada field status/joined_at yang perlu disesuaikan

### Daftar file yang akan diubah

- `app/Http/Controllers/CustomerController.php`
- `app/Http/Requests/StoreCustomerRequest.php`
- `app/Http/Requests/UpdateCustomerRequest.php`
- `resources/views/pages/customer/create.blade.php`
- `resources/views/pages/customer/edit.blade.php`
- `resources/views/pages/customer/show.blade.php` bila perlu

### Estimasi tingkat risiko perubahan

Medium

---

## 4) Penghapusan input deposito awal saat registrasi nasabah

### Nama perubahan

Menghapus input deposito awal dari registrasi nasabah baru dan menghentikan pembuatan deposit awal otomatis.

### Prioritas

High

### Modul yang terdampak

- Manajemen Nasabah
- Transaksi Simpanan
- Arus pembuatan nasabah baru

### Database yang terdampak

- Potensi dampak data awal karena record deposit awal tidak lagi dibuat otomatis
- Tidak perlu migration bila field sudah cukup
- Jika ada penyesuaian default/null pada field terkait, bisa berdampak pada skema kecil

### Backend yang terdampak

- `CustomerController@store`
- Request customer store
- Kemungkinan logic terkait `Deposit::create()` saat customer dibuat

### Frontend yang terdampak

- `resources/views/pages/customer/create.blade.php`
- Jika ada view/komponen lain yang masih meminta `amount` saat create nasabah

### Laporan yang terdampak

- Laporan simpanan awal nasabah
- Laporan saldo awal bila sebelumnya tergantung deposit awal otomatis

### Daftar file yang akan diubah

- `app/Http/Controllers/CustomerController.php`
- `app/Http/Requests/StoreCustomerRequest.php`
- `resources/views/pages/customer/create.blade.php`

### Estimasi tingkat risiko perubahan

High

---

## 5) Standarisasi pencarian transaksi menggunakan Nomor Rekening

### Nama perubahan

Memastikan semua pencarian dan filter transaksi menggunakan Nomor Rekening sebagai kunci identitas nasabah.

### Prioritas

High

### Modul yang terdampak

- Transaksi Simpanan
- Transaksi Penarikan
- Transaksi Pinjaman
- Transaksi Pembayaran Pinjaman
- Kolektor

### Database yang terdampak

- Tidak ada perubahan skema langsung
- Mungkin perlu indeks bila pencarian nomor rekening menjadi intensif, namun hanya jika benar-benar diperlukan

### Backend yang terdampak

- Controller transaksi pada query filter customer
- Kemungkinan helper/trait query customer
- Potensi penyesuaian parameter request filter

### Frontend yang terdampak

- Select/filter customer di halaman transaksi
- Placeholder dan label pencarian customer
- Tabel hasil transaksi

### Laporan yang terdampak

- Semua laporan transaksi yang menampilkan atau memfilter customer

### Daftar file yang akan diubah

- `app/Http/Controllers/DepositController.php`
- `app/Http/Controllers/WithdrawalController.php`
- `app/Http/Controllers/LoanController.php`
- `app/Http/Controllers/InstallmentController.php`
- `resources/views/pages/transaction/deposit/index.blade.php`
- `resources/views/pages/transaction/withdrawal/index.blade.php`
- `resources/views/pages/transaction/loan/index.blade.php`
- `resources/views/pages/transaction/installment/index.blade.php`
- file view filter customer lain yang relevan

### Estimasi tingkat risiko perubahan

High

---

## 6) Audit dan penyesuaian aturan “tanpa potongan administrasi”

### Nama perubahan

Memastikan seluruh transaksi tidak menggunakan potongan administrasi.

### Prioritas

High

### Modul yang terdampak

- Transaksi Simpanan
- Transaksi Penarikan
- Transaksi Pinjaman
- Transaksi Pembayaran Pinjaman
- Laporan transaksi terkait

### Database yang terdampak

- Potensi kecil jika ada field admin fee tersimpan di tabel transaksi
- Jika field tersebut ada, perlu evaluasi apakah dihapus atau hanya tidak dipakai

### Backend yang terdampak

- Controller transaksi
- Model transaksi bila ada accessor/calc admin fee
- Logic perhitungan saldo dan total transaksi
- Request validation bila ada field admin fee

### Frontend yang terdampak

- Form transaksi bila ada input admin fee
- Detail transaksi bila menampilkan potongan administrasi
- PDF laporan bila menampilkan komponen admin fee

### Laporan yang terdampak

- Semua laporan transaksi yang menampilkan nominal/biaya administrasi

### Daftar file yang akan diubah

- `app/Http/Controllers/DepositController.php`
- `app/Http/Controllers/WithdrawalController.php`
- `app/Http/Controllers/LoanController.php`
- `app/Http/Controllers/InstallmentController.php`
- request transaksi terkait
- view transaksi terkait

### Estimasi tingkat risiko perubahan

High

---

## 7) Audit modul yang belum teridentifikasi jelas: Hari Libur, Validasi Transaksi, Bunga Simpanan, Bunga Deposito

### Nama perubahan

Menentukan apakah modul-modul berikut memang sudah ada atau perlu ditambahkan:

- Hari Libur
- Validasi Transaksi
- Bunga Simpanan
- Bunga Deposito

### Prioritas

Medium

### Modul yang terdampak

- Modul baru jika belum ada
- Transaksi dan laporan jika ada relasi bisnis

### Database yang terdampak

- Mungkin ada migration baru bila modul belum ada
- Jika sudah ada, mungkin hanya penyesuaian kecil

### Backend yang terdampak

- Controller baru atau controller existing bila fitur sudah ada
- Model baru atau existing
- Route baru

### Frontend yang terdampak

- Menu navigasi
- halaman CRUD / laporan baru

### Laporan yang terdampak

- Laporan validasi transaksi
- Laporan bunga simpanan
- Laporan bunga deposito

### Daftar file yang akan diubah

- Belum final, tergantung hasil inventaris lanjutan
- Kemungkinan pada:
    - `routes/web.php`
    - `app/Http/Controllers/*`
    - `app/Models/*`
    - `database/migrations/*`
    - `resources/views/pages/*`

### Estimasi tingkat risiko perubahan

Medium to High, tergantung apakah modul sudah ada atau belum

---

## 8) Penyesuaian profil/pengaturan pengguna jika memang ada gap

### Nama perubahan

Menyesuaikan pengaturan profil pengguna dan reset data hanya jika implementasi aktual memang tidak sesuai requirement.

### Prioritas

Low

### Modul yang terdampak

- Manajemen Pengguna
- Dashboard / layout menu

### Database yang terdampak

- Tidak ada, kecuali ditemukan kebutuhan field profil tambahan

### Backend yang terdampak

- `HomeController`
- `UserController`
- route profile/reset

### Frontend yang terdampak

- layout menu
- halaman profil

### Laporan yang terdampak

- Tidak ada langsung

### Daftar file yang akan diubah

- `resources/views/layouts/app.blade.php`
- `app/Http/Controllers/HomeController.php`
- `app/Http/Controllers/UserController.php`
- view profile terkait

### Estimasi tingkat risiko perubahan

Low

---

## Urutan implementasi yang akan dipakai

1. Penyesuaian label dan format laporan transaksi
2. Konsistensi tampilan nomor rekening pada transaksi
3. Penyesuaian tampilan dan validasi data nasabah
4. Penghapusan input deposito awal saat registrasi nasabah
5. Standarisasi pencarian transaksi menggunakan Nomor Rekening
6. Audit aturan tanpa potongan administrasi
7. Audit modul yang belum teridentifikasi jelas
8. Penyesuaian profil/pengaturan pengguna jika diperlukan

---

## Catatan implementasi

- Perubahan yang tidak benar-benar diperlukan berdasarkan kode aktual akan dilewati.
- Tidak akan dilakukan refactor.
- Tidak akan ada perubahan arsitektur project.
- Tidak akan ada perubahan coding style.
- Implementasi dilakukan bertahap mengikuti urutan di atas.
