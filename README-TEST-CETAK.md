# Panduan Pengujian & Kalibrasi Cetak Passbook Epson PLQ-20 (Emulasi PR2) via QZ Tray

Halaman ini menyediakan sarana pengujian dan kalibrasi cetak langsung (RAW command) ke printer dot-matrix passbook **Epson PLQ-20** yang dikonfigurasi dalam mode **Emulasi Olivetti PR2**. Halaman berjalan tanpa autentikasi (`guest`) dan tanpa koneksi database sehingga dapat digunakan secara independen oleh teknisi dan teller.

---

## 1. Rute dan URL Aplikasi

| Metode | URL | Nama Route | Keterangan |
|---|---|---|---|
| `GET` | `/test-cetak` | `test-cetak.index` | Halaman UI interaktif, form kalibrasi, pratinjau SVG, dan hexdump |
| `POST` | `/test-cetak/payload` | `test-cetak.payload` | Endpoint API pembuatan byte payload PR2, hexdump, dan metadata grid |
| `POST` | `/test-cetak/sign` | `test-cetak.sign` | Endpoint opsional penandatanganan sertifikat QZ Tray (SHA512) |

---

## 2. Persyaratan & Karakteristik Keras Epson PLQ-20

### ⚠️ PERINGATAN PENTING: Sensor Kertas Otomatis (Auto Sheet Alignment)
> **Printer Epson PLQ-20 memiliki sensor optik otomatis di mulut pemasukan kertas.**
>
> Jika kertas/buku tabungan **belum dimasukkan ke dalam slot printer saat data RAW diterima**, printer akan mengabaikan perintah pemosisian absolut (`ESC H`, `ESC / 1`, `CR`, `FF`) dan mencetak secara berantakan atau menolak data.
>
> **Aturan Wajib:**
> 1. Masukkan buku tabungan ke slot printer terlebih dahulu.
> 2. Tunggu hingga motor printer menarik dan meratakan buku (lampu *Ready* hijau menyala stabil).
> 3. Baru tekan tombol **Cetak Data Dummy** atau **Cetak Tes Kalibrasi** di browser.

### Pengaturan Emulasi di Panel Printer (Hardware Setup)
Printer Epson PLQ-20 mendukung emulasi ESC/P2, IBM PPDS, dan Olivetti PR2. Untuk menggunakan fitur cetak passbook ini, printer **wajib disetel ke emulasi PR2**:

1. Matikan printer (Power OFF).
2. Siapkan beberapa lembar kertas A4 atau kertas buku bekas di samping printer.
3. Tekan dan tahan tombol **F1** dan **F2** secara bersamaan, lalu nyalakan printer (Power ON). Tahan tombol hingga printer mengeluarkan suara bip dan lampu berkedip.
4. Masukkan kertas A4 ke slot depan. Printer akan mencetak menu konfigurasi saat ini (*Default Setting Mode*).
5. Pada menu **Emulation**, pilih **PR2** (bukan ESC/P2 atau IBM). Gunakan tombol **F1** / **F2** untuk navigasi menu dan konfirmasi sesuai petunjuk yang dicetak oleh printer.
6. Matikan dan nyalakan kembali printer setelah selesai mengubah pengaturan.

---

## 3. Konfigurasi Driver & Windows di PC Teller

1. **Koneksi Kabel:** Hubungkan printer ke PC teller via kabel USB atau Serial (RS-232).
2. **Pilihan Driver Printer:**
   - **Opsi A (Sangat Direkomendasikan u/ QZ Tray RAW):** Tambahkan printer dengan driver bawaan Windows **"Generic / Text Only"**. Beri nama misalnya `EPSON-PLQ20-RAW`.
   - **Opsi B:** Menggunakan driver resmi **Epson PLQ-20 Driver** (pastikan opsi *Print directly to the printer* / Spool RAW diaktifkan).
3. **Sharing Printer (Opsional):** Jika printer diakses dari beberapa PC di jaringan teller yang sama, bagikan printer dengan nama share pendek tanpa spasi (misal `PLQ20`).

---

## 4. Instalasi & Konfigurasi QZ Tray di PC Teller

1. **Unduh QZ Tray:**
   - Kunjungi situs resmi: [https://qz.io/download/](https://qz.io/download/)
   - Unduh versi 2.1 atau 2.2+ untuk Windows/Linux/macOS teller.
2. **Jalankan QZ Tray:**
   - Pastikan ikon QZ Tray berwarna hijau muncul di system tray (pojok kanan bawah Windows).
   - QZ Tray berjalan sebagai WebSocket server lokal di port default `8182` (WSS) / `8181` (WS).
3. **Sertifikat Digital (Opsional):**
   - Jika dijalankan tanpa sertifikat, QZ Tray akan menampilkan kotak dialog pop-up konfirmasi "*Allow this website to print?*". Pilih **"Always allow"** atau centang **"Remember this decision"**.
   - Jika Anda memiliki sertifikat komersial/self-signed QZ Tray, letakkan private key di server dan konfigurasikan di `.env`:
     ```env
     QZ_PRIVATE_KEY_PATH=/jalur/ke/private_key.pem
     # atau isi string langsung:
     QZ_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----\n..."
     ```
     Endpoint `/test-cetak/sign` otomatis menandatangani request dengan SHA512 sehingga dialog pop-up tidak muncul lagi.

---

## 5. Langkah demi Langkah Pengujian & Kalibrasi

### Langkah 1: Buka Halaman Pengujian
Akses URL `/test-cetak` di peramban web (Google Chrome / Microsoft Edge / Mozilla Firefox).

### Langkah 2: Hubungkan QZ Tray & Pilih Printer
1. Klik tombol **"Sambungkan QZ Tray"** di pojok kanan atas.
2. Setelah status berubah hijau (*QZ Tray: Terhubung*), dropdown **Pilih Printer** akan terisi seluruh printer yang terdeteksi di PC teller.
3. Pilih nama printer PLQ-20 Anda (pilihan akan otomatis tersimpan di `localStorage` peramban).

### Langkah 3: Siapkan Buku Tabungan Uji
Gunakan **buku tabungan bekas** yang sudah tidak terpakai atau lipatan kertas seukuran buku tabungan asli nasabah (standar: lebar 150 mm, tinggi 210 mm).

### Langkah 4: Cetak Tes Kalibrasi (Visual Ruler)
1. Masukkan buku tabungan ke printer hingga ditarik oleh sensor printer.
2. Tentukan **Baris Mulai** (misal baris `0` untuk baris paling atas atau baris `5`).
3. Tentukan **Jumlah Baris** (misal `2` baris).
4. Klik tombol **"Cetak Tes Kalibrasi"** (Kuning).
5. Printer akan mencetak garis *underscore* `_` sepanjang baris dan deretan angka `0123456789` tepat pada setiap posisi kolom:
   - Ambil penggaris fisik milimeter, tempelkan pada cetakan buku.
   - Periksa apakah angka digit jatuh tepat di dalam kotak kolom buku tabungan Anda.

### Langkah 5: Penyesuaian Presisi (Fine Tuning)
- Jika cetakan terlalu ke kiri / kanan:
  - Sesuaikan nilai **Margin Kiri (mm)** atau geser **Posisi X (mm)** masing-masing kolom.
- Jika cetakan terlalu ke atas / bawah:
  - Sesuaikan nilai **Margin Atas (mm)** atau **Tinggi Baris (mm)** (standar 4.23 mm = 6 LPI).
- Perubahan form otomatis disimpan ke `localStorage` dan pratinjau SVG langsung terbarui.

### Langkah 6: Cetak Data Dummy Realistis
1. Masukkan kembali buku tabungan ke printer.
2. Klik tombol **"Cetak Data Dummy ke PLQ-20"** (Biru).
3. Printer akan mencetak 7 kolom standar buku tabungan:
   - `TANGGAL` (ddmmyy, misal `160425`)
   - `SANDI` (4 digit, misal `2001`)
   - `DEBET` (rata kanan, format `2,900,000.00`)
   - `KREDIT` (rata kanan, format `5,000,000.00`)
   - `PC` (2 digit urut, misal `01`, `02`)
   - `SALDO` (saldo berjalan non-negatif, misal `52,900,000.00`)
   - `KETERANGAN` (kode transaksi, misal `CCM 0009999`)
4. Verifikasi bahwa angka rata kanan tidak menabrak garis kolom dan saldo berjalan sesuai.

### Langkah 7: Unduh File Biner (.bin) untuk Debugging
Klik tombol **"Unduh Payload (.bin)"** jika Anda ingin menyimpan byte mentah hasil generate untuk dianalisis di hex editor (HxD, VS Code Hex Editor) atau dikirim manual via command line / PuTTY.

---

## 6. Tabel Referensi Perintah Byte PR2 (Epson PLQ-20)

| Perintah PR2 | Kode Hex | Fungsi & Keterangan |
|---|---|---|
| `ESC 0` | `1B 30` | Software Reset (mengembalikan buffer & pointer ke kondisi awal) |
| `ESC S 5` | `1B 53 35` | Memilih Passbook Mode (mengaktifkan sensor ketebalan & batas buku) |
| `ESC ' F` | `1B 27 46` | Seam Jilid Horizontal (buku lipat atas-bawah) |
| `ESC ' D` | `1B 27 44` | Seam Jilid Vertikal (buku lipat kiri-kanan) |
| `ESC <` | `1B 3C` | Pitch Karakter 10 CPI (lebar 2.54 mm per karakter) |
| `ESC =` | `1B 3D` | Pitch Karakter 12 CPI (lebar 2.12 mm per karakter) |
| `ESC R nnn` | `1B 52 30 30 33` | Pemilihan Font PR2 (`000` = Draft, `003` = NLQ1, `014` = NLQ2, `017` = LQ2) |
| `ESC # 0` | `1B 23 30` | Referensi vertikal dari Tepi Atas Kertas (Paper Edge) |
| `ESC # 1` | `1B 23 31` | Referensi vertikal dari Top of Form (TOF) |
| `ESC \| A nnn` | `1B 7C 41 nnn` | Offset halus margin kiri dalam kelipatan 1/120 inci (3 digit ASCII `000`–`999`) |
| `ESC / 1 nnnn` | `1B 2F 31 nnnn` | Pemosisian vertikal baris dari tepi atas (4 digit ASCII `0000`–`9999` unit 1/240" atau 1/216") |
| `ESC H nnn` | `1B 48 nnn` | Pemosisian kolom karakter horizontal dari margin kiri (3 digit ASCII `000`–`255`) |
| `ESC O` | `1B 4F` | Eject Buku ke Depan (keluar ke arah teller) |
| `FF` | `0C` | Form Feed / Eject Buku ke Belakang |
| `CR` | `0D` | Carriage Return |

---

## 7. Arsitektur File & Kode Laravel

```
koperasi/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── TestCetakController.php        # Controller index, payload generator, QZ sign
│   │   └── Requests/
│   │       └── TestCetakPayloadRequest.php    # Validasi margin, dimensi, dan batas digit PR2
│   └── Services/
│       └── PassbookPr2.php                    # Engine penyusun byte PR2, hexdump, & dummy generator
├── resources/
│   └── views/
│       └── pages/
│           └── test-cetak/
│               └── index.blade.php            # Standalone Blade view + SVG preview + QZ Tray JS
├── routes/
│   └── web.php                                # Pendaftaran 3 rute publik test-cetak
├── tests/
│   └── Feature/
│       └── TestCetakPr2Test.php               # Suite pengujian 9 skenario lengkap
└── README-TEST-CETAK.md                       # Panduan teknis operasional ini
```

---

## 8. Menjalankan Pengujian Otomatis

Untuk memastikan seluruh fungsionalitas service, validasi form, deteksi pemotongan margin, dan endpoint berjalan dengan sempurna:

```bash
php artisan test --filter=TestCetakPr2Test
```

Seluruh 9 skenario uji (akses publik, mode dummy, mode kalibrasi, validasi batas form, peringatan batas bawah, peringatan batas kanan, integritas byte header PR2, eject mode, dan fallback signing) dipastikan lulus (PASS).
