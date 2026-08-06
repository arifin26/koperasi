# Tutorial Running Engine Bunga & Dokumentasi Libur Otomatis

Dokumen ini berisi panduan penggunaan Engine Bunga pada sistem Koperasi Swamitra serta penjelasannya terkait aturan libur otomatis pada hari Minggu/Weekend.

---

## 1. Tutorial Running Engine Bunga

Engine perhitungan bunga pada sistem Koperasi Swamitra terdiri dari 3 perintah utama berbasis Laravel Artisan CLI. Perintah ini dapat dijalankan secara manual melalui terminal/command prompt atau secara otomatis melalui Laravel Scheduler (Cron Job / Windows Task Scheduler).

### A. Menjalankan Perintah Secara Manual (CLI)

#### 1. Perhitungan Akumulasi Bunga Harian (Simpanan)
Perintah ini menghitung akumulasi bunga harian bagi seluruh nasabah aktif (secara default menghitung untuk saldo kemarin).
```bash
php artisan interest:calculate-daily
```
* **Opsi Parameter Tambahan:**
  * `--date=YYYY-MM-DD` : Menghitung untuk tanggal tertentu secara spesifik (Contoh: `php artisan interest:calculate-daily --date=2026-08-05`).
  * `--dry-run` : Simulasi perhitungan tanpa menyimpan hasil ke database.

#### 2. Posting Bunga Bulanan ke Rekening Simpanan
Perintah ini mentransfer/memposting total akumulasi bunga bulanan yang telah terkumpul ke rekening simpanan nasabah.
```bash
php artisan interest:post-monthly
```
* **Opsi Parameter Tambahan:**
  * `--period=YYYY-MM` : Memposting bunga untuk periode bulan tertentu (Contoh: `php artisan interest:post-monthly --period=2026-07`).
  * `--force` : Memaksa re-posting jika sebelumnya sudah pernah diposting.
  * `--dry-run` : Simulasi posting tanpa mengubah saldo.

#### 3. Pembayaran Bunga Deposito Berjangka
Perintah ini mengkalkulasi dan mentransfer bunga bulanan deposito yang sudah masuk tanggal jatuh tempo bulanan langsung ke simpanan sukarela nasabah.
```bash
php artisan deposit:pay-interest
```
* **Opsi Parameter Tambahan:**
  * `--date=YYYY-MM-DD` : Memproses pembayaran bunga deposito untuk tanggal spesifik.
  * `--dry-run` : Simulasi pembayaran bunga deposito.

---

### B. Menjalankan Perintah secara Otomatis (Scheduler / Cron Job)

Semua engine bunga di atas telah terdaftar pada scheduler Laravel di `app/Console/Kernel.php` dengan jadwal sebagai berikut:
1. **`interest:calculate-daily`**: Berjalan **setiap hari pukul 00:05 WIB**.
2. **`interest:post-monthly`**: Berjalan **setiap tanggal 1 awal bulan pukul 00:30 WIB**.
3. **`deposit:pay-interest`**: Berjalan **setiap hari pukul 01:00 WIB**.

#### Menjalankan Worker Scheduler di Server/Komputer:
Untuk menjalankan scheduler secara otomatis, Anda cukup menjalankan perintah runner:
```bash
php artisan schedule:run
```

#### Pengaturan di Windows Task Scheduler:
1. Buka **Task Scheduler** di Windows.
2. Buat **Basic Task** baru (misal: "Koperasi Engine Scheduler").
3. Set Trigger: **Daily** (setiap hari).
4. Set Action: **Start a program**.
5. Program/script: Isi dengan path `php.exe` (Contoh: `C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe`).
6. Add arguments: `artisan schedule:run`
7. Start in: Path direktori project koperasi (Contoh: `e:\Kerja\laragon\www\koperasi`).

---

## 2. Dokumentasi Aturan Hari Libur Otomatis (Hari Minggu / Weekend)

### Apakah Hari Minggu Dihitung Libur Otomatis pada Sistem?
> **YA, Hari Minggu (dan Hari Sabtu / Weekend) secara OTOMATIS dihitung sebagai HARI LIBUR oleh sistem.**

### Penjelasan Logika Teknis:
Pada file [InterestCalculateDaily.php](file:///e:/Kerja/laragon/www/koperasi/app/Console/Commands/InterestCalculateDaily.php#L166-L173), fungsi pemeriksa hari libur `isHoliday()` diimplementasikan sebagai berikut:

```php
private function isHoliday(Carbon $date)
{
    // 1. Cek apakah tanggal terdaftar di tabel hari libur khusus (holidays)
    $holiday = Holiday::where('date', $date->format('Y-m-d'))->first();
    if ($holiday) {
        return $holiday->type == 'holiday';
    }
    
    // 2. Jika tidak terdaftar khusus, cek apakah tanggal adalah Weekend (Sabtu / Minggu)
    return $date->isWeekend();
}
```

### Dampak dan Mekanisme Kerja:
1. Ketika `interest:calculate-daily` berjalan pada hari Minggu (atau Sabtu):
   * Sistem mendeteksi bahwa tanggal tersebut adalah weekend via Carbon `$date->isWeekend()`.
   * Perhitungan bunga harian untuk tanggal tersebut **otomatis dilewati (skipped)**.
   * Log eksekusi tercatat pada tabel `interest_engine_logs` dengan status `skipped` dan alasan `'Hari Libur / Weekend'`.
2. **Pengecualian / Hari Libur Nasional Khusus**:
   * Selain hari Sabtu/Minggu yang libur otomatis, pengurus/admin juga dapat menginputkan Tanggal Libur Nasional (misal: Hari Kemerdekaan, Idul Fitri) pada menu Pengaturan Hari Libur (`holidays`). Sistem akan memperlakukannya sama seperti weekend.
