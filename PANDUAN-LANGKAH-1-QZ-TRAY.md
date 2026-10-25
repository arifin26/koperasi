# Panduan Instalasi & Verifikasi QZ Tray di PC Teller (Langkah 1)

Dokumen ini memandu teknisi atau staf teller untuk menginstal, mengonfigurasi, dan memverifikasi aplikasi desktop **QZ Tray** di PC teller sebelum melakukan pengujian cetak passbook Epson PLQ-20.

---

## 1. Persyaratan Sistem PC Teller

- **Sistem Operasi:** Windows 10 / 11 (64-bit disarankan), Linux, atau macOS.
- **Java Runtime Environment (JRE):** Diperlukan Java versi 8 atau yang lebih baru. 
  *(Catatan: Installer resmi QZ Tray versi modern sudah menyertakan paket Java bawaan, sehingga tidak perlu menginstal Java terpisah).*
- **Hak Akses:** Akun Administrator Windows (dibutuhkan saat pertama kali memasang sertifikat root lokal QZ Tray).

---

## 2. Langkah Instalasi Langkah demi Langkah

### A. Unduh Installer
1. Kunjungi situs resmi: [https://qz.io/download/](https://qz.io/download/)
2. Unduh versi stabil terbaru (contoh: **QZ Tray 2.2.x** untuk Windows bertipe `.exe`).

### B. Proses Pemasangan (Setup Wizard)
1. Klik kanan file installer `.exe` yang telah diunduh, lalu pilih **Run as administrator**.
2. Klik **Next** pada jendela sambutan.
3. Setujui perjanjian lisensi (License Agreement).
4. Pilih lokasi direktori instalasi default (biasanya `C:\Program Files\QZ Tray`).
5. Pada tahap komponen tambahan:
   - Pastikan opsi **Install Root Certificate** tercentang (opsional tapi disarankan agar sertifikat lokal WebSocket terpasang otomatis).
6. Klik **Install** dan tunggu hingga proses selesai.
7. Klik **Finish**.

---

## 3. Menjalankan & Konfigurasi Awal QZ Tray

1. Buka menu Start Windows, cari dan jalankan aplikasi **QZ Tray**.
2. Periksa area **System Tray** di pojok kanan bawah layar (dekat indikator jam/kalender Windows, klik tanda panah atas `^` jika tersembunyi):
   - Pastikan terdapat ikon huruf **"Q"**.
   - Warna ikon:
     - 🟢 **Hijau:** QZ Tray aktif normal dan siap melayani koneksi WebSocket.
     - 🟡 **Kuning:** QZ Tray sedang memuat atau menunggu inisialisasi socket.
     - 🔴 **Merah / Abu-abu:** Service mati atau terjadi konflik port.

### C. Pengaturan Wajib: Otomatis Berjalan saat PC Nyala
Agar teller tidak perlu membuka aplikasi QZ Tray secara manual setiap pagi:
1. Klik kanan pada ikon **QZ Tray** di System Tray.
2. Centang menu **"Automatically start"** (atau *Start on login*).

---

## 4. Verifikasi Koneksi Lokal (Uji Mandiri)

Untuk memastikan service QZ Tray benar-benar berjalan dan port WebSocket tidak terblokir firewall:

### Uji via Browser
Buka tab baru di browser PC teller dan akses salah satu alamat diagnostik resmi QZ Tray:
- `http://localhost:8182` atau `https://localhost:8182`
- Jika muncul halaman teks informasi QZ Tray atau status sertifikat SSL, berarti WebSocket server QZ Tray di PC teller berfungsi 100%.

### Cek Port di Command Prompt (Opsional untuk Teknisi)
Buka CMD / PowerShell dan jalankan:
```cmd
netstat -ano | findstr 8182
```
Jika muncul status `LISTENING`, berarti service QZ Tray aktif dan siap menerima perintah cetak dari web aplikasi.

---

## 5. Mengatasi Kendala Umum (Troubleshooting)

| Gejala Kendala | Penyebab | Solusi |
|---|---|---|
| Ikon QZ Tray tidak muncul di System Tray | Aplikasi belum terbuka atau crash | Buka kembali dari Start Menu: *QZ Tray*. Jika gagal, jalankan sebagai Administrator. |
| Port 8182 terblokir firewall | Antivirus / Windows Defender Firewall memblokir socket lokal | Tambahkan exception izin aplikasi `qz-tray.exe` pada Windows Defender Firewall untuk jaringan *Private/Domain*. |
| Browser menampilkan pesan "QZ Tray: Tidak Terhubung" | WebSocket lokal belum diizinkan oleh browser | Klik tombol **"Sambungkan QZ Tray"** di web `/test-cetak`. Jika muncul popup izin dialog browser / QZ, pilih **"Always Allow"** atau **"Allow"**. |

---

Setelah Langkah 1 ini terverifikasi (ikon QZ Tray berwarna hijau di System Tray), Anda dapat melanjutkan ke **[Langkah 2: Konfigurasi Driver & Spooler Windows untuk Epson PLQ-20](PANDUAN-LANGKAH-2-DRIVER-DAN-SPOOLER-WINDOWS.md)** dan **Langkah 3: Buka Halaman `/test-cetak`**.
