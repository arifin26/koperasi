# Panduan Konfigurasi Driver & Spooler Windows untuk Epson PLQ-20 (Langkah 2)

Dokumen ini memandu staf IT Support dan Teller dalam mengonfigurasi driver Windows, pemetaan port USB, pengecekan Windows Print Spooler, serta penyelarasan hardware passbook printer **Epson PLQ-20 (Emulasi Olivetti PR2)**.

Dokumen ini merupakan kelanjutan resmi dari **[Langkah 1: Panduan Instalasi & Verifikasi QZ Tray](PANDUAN-LANGKAH-1-QZ-TRAY.md)**.

---

## 1. Memahami Penyebab "Notifikasi Sukses tapi Antrean Spooler Kosong"

Saat Anda mengklik tombol **Cetak Data Dummy** atau **Kalibrasi** pada halaman `/test-cetak`, browser memunculkan notifikasi sukses: *"Perintah cetak berhasil dikirim"*. Namun, antrean cetak (*Print Queue*) Windows tampak kosong dan printer tidak bergerak sama sekali.

Hal ini terjadi karena salah satu dari penyebab teknis berikut:

| Penyebab | Penjelasan Teknis | Solusi |
|---|---|---|
| **Spooler Selesai Terlalu Cepat (<0.1 dtk)** | Payload cetak RAW Olivetti PR2 berukuran sangat kecil (~300–500 byte). Windows Spooler menyelesaikannya dalam beberapa milidetik sehingga antrean langsung terhapus sebelum mata sempat melihatnya. | Lakukan **Pause Printing** untuk menahan antrean cetak. |
| **Driver Grafis Epson Membuang Data RAW** | Driver resmi Epson Windows (GDI) mengharapkan format grafis EMF/GDI. Ketika menerima byte kontrol ASCII/Escape RAW PR2 (`ESC 0`, `ESC S 5`), driver grafis menganggapnya korup dan **membuangnya tanpa mencetak**. | Pasang driver **Generic / Text Only**. |
| **Salah Pilih Port USB di Windows** | Windows memetakan printer ke port `USB001`, padahal printer fisik dicolok ke port `USB002`, atau terhubung ke port virtual `FILE:`. Data berhasil dispool tapi tidak sampai ke fisik printer. | Cocokkan port USB aktif di tab *Ports*. |
| **Sensor Kertas PLQ-20 Belum Mendeteksi Media** | Printer passbook Epson PLQ-20 dilengkapi fitur *Auto Sheet Alignment*. Jika saat data tiba buku/kertas belum dimasukkan, printer akan membuang buffer data (*Paper Out*). | Masukkan buku tabungan hingga ditarik motor dan lampu **Ready** menyala sebelum klik cetak. |
| **Emulasi Hardware Masih ESC/P2** | Bawaan default pabrik Epson PLQ-20 sering kali beroperasi dalam mode ESC/P2 atau IBM PPDS, sedangkan web mengirim perintah Olivetti PR2. | Ubah emulasi hardware menjadi **PR2** via tombol F1+F2. |
| **Salah Memilih Printer Virtual** | Web otomatis mengirim perintah ke printer default Windows seperti *Microsoft Print to PDF* atau *OneNote*. | Pilih nama printer fisik yang tepat pada dropdown web. |

---

## 2. Uji Diagnostik: Membuktikan Aliran Data Spooler (Pause Printing)

Untuk membuktikan apakah data dari web QZ Tray sebenarnya sudah masuk ke Windows Spooler atau belum:

1. Buka **Control Panel > Hardware and Sound > Devices and Printers** (atau di Windows 11: *Settings > Bluetooth & devices > Printers & scanners*).
2. Temukan printer yang Anda tuju (misal `EPSON PLQ-20` atau `EPSON-PLQ20-RAW`).
3. Klik kanan printer tersebut > pilih **See what's printing** (Buka antrean pencetakan).
4. Pada jendela antrean pencetakan, klik menu bar bagian atas: **Printer** > centang **Pause Printing** (Jeda Pencetakan).
5. Buka browser web `/test-cetak`, pastikan dropdown memilih printer tersebut, lalu klik **Cetak Data Dummy**.
6. **Amati Jendela Antrean Pencetakan Windows:**
   - **Jika muncul dokumen tertahan** dengan status *Paused* berukuran ~300 s.d. 600 bytes:
     > **Kesimpulan:** Aliran data dari Browser &rarr; QZ Tray &rarr; Windows Spooler **100% BERHASIL dan NORMAL**. Selama ini antrean tidak terlihat karena selesai diproses spooler dalam hitungan milidetik.
   - **Jika tidak muncul dokumen sama sekali:**
     > **Kesimpulan:** Periksa nama printer pada dropdown web `/test-cetak`. Kemungkinan Anda mengirim data ke printer virtual lain (*Microsoft Print to PDF*, *OneNote*), atau QZ Tray mengirim ke nama printer yang salah.
7. Setelah selesai tes jeda:
   - Klik kanan dokumen tertahan > pilih **Cancel** (Batalkan).
   - Klik menu **Printer** > hilangkan centang **Pause Printing** agar antrean kembali normal.

---

## 3. Menambahkan Driver "Generic / Text Only" (Wajib untuk RAW PR2)

Agar Windows mengirimkan byte kontrol PR2 apa adanya ke port USB tanpa diubah atau dibuang oleh driver grafis Epson, tambahkan instance printer dengan driver Generic/Text Only:

### Langkah di Windows 10 & 11:
1. Tekan tombol **Windows + R**, ketik `control printers`, lalu tekan **Enter**.
2. Klik tombol **Add a printer** (Tambah printer).
3. Jika Windows melakukan pencarian otomatis, klik tautan di bawah: **"The printer that I want isn't listed"** (Printer yang saya inginkan tidak terdaftar).
4. Pilih opsi paling bawah: **"Add a local printer or network printer with manual settings"**, lalu klik **Next**.
5. Pada pilihan port (**Use an existing port**):
   - Pilih port USB tempat kabel printer Epson PLQ-20 dicolokkan (contoh: **`USB001`**, **`USB002`**, atau port USB bernomor tertinggi yang tersedia).
   - Klik **Next**.
6. Pada daftar pilihan driver:
   - Kolom kiri (*Manufacturer*): Pilih **Generic**.
   - Kolom kanan (*Printers*): Pilih **Generic / Text Only**.
   - Klik **Next**.
7. Jika ditanya versi driver: pilih **Use the driver that is currently installed (recommended)**, klik **Next**.
8. Beri nama printer yang mudah dikenali:
   - Nama Printer: `EPSON-PLQ20-RAW`
   - Klik **Next**.
9. Pada pengaturan sharing printer: pilih **Do not share this printer**, klik **Next**.
10. Hilangkan centang *Set as the default printer*, lalu klik **Finish**.
11. Buka kembali halaman `/test-cetak`, klik tombol **Muat Ulang Printer**, lalu pilih `EPSON-PLQ20-RAW` pada dropdown.

---

## 4. Memverifikasi Pemetaan Port USB Fisik Printer

Jika printer memiliki beberapa port USB di Windows (`USB001`, `USB002`, `USB003`), pastikan port yang digunakan `EPSON-PLQ20-RAW` adalah port yang benar-benar aktif:

1. Buka **Control Panel > Devices and Printers**.
2. Klik kanan printer `EPSON-PLQ20-RAW` > pilih **Printer properties** (Bukan *Properties* biasa).
3. Buka tab **Ports**.
4. Periksa baris port yang tercentang:
   - Wajib tercentang pada salah satu baris bertipe **Virtual printer port for USB** (misal `USB001` atau `USB002`).
   - Jangan pernah memilih port `LPT1:`, `COM1:`, atau `FILE:`.
5. **Cara Mengetahui USB Mana yang Aktif:**
   - Colok dan cabut kabel USB printer sambil mengamati jendela *Device Manager* bagian *Universal Serial Bus controllers* (*USB Printing Support*).
   - Atau centang `USB001`, lakukan uji jeda antrean (Langkah 2), jika setelah di-unpause printer tidak merespons, pindahkan centang ke `USB002` dan coba kembali.

---

## 5. Prosedur Sensor Kertas Otomatis Epson PLQ-20 (Auto Sheet Alignment)

Epson PLQ-20 adalah printer buku tabungan pintar dengan sensor optik otomatis:

1. Pastikan kabel daya terpasang dan tombol **Power** printer dinyalakan.
2. Tunggu hingga proses inisialisasi mekanis selesai (jarum print head bergerak ke kiri dan kanan).
3. Lampu indikator:
   - Lampu **Power** (Hijau) harus menyala stabil.
   - Lampu **Ready** (Hijau) harus menyala.
4. **Wajib Memasukkan Buku Tabungan / Kertas:**
   - Masukkan kertas uji (atau buku tabungan terbuka pada halaman yang ingin dicetak) ke mulut depan printer secara lurus.
   - Motor printer akan secara otomatis menyedot kertas ke dalam sekitar 2–3 cm dan meluruskan posisinya (*Auto Alignment*).
   - Pastikan kertas sudah dijepit stabil oleh roll internal printer.
5. **Kirim Perintah Cetak dari Web:**
   - Setelah kertas dijepit dan printer dalam posisi standby, baru klik tombol **Cetak Data Dummy** atau **Kalibrasi** pada halaman web.
   - Printer akan segera mencetak baris demi baris dan menggeser kertas sesuai konfigurasi layout PR2.

> ⚠️ **Catatan Penting:** Jika Anda menekan tombol cetak di web saat **kertas belum dimasukkan**, printer PLQ-20 akan menolak job cetak, membunyikan beep error, atau membuang data begitu status *Paper Out* terdeteksi.

---

## 6. Mengatur Emulasi Hardware Printer ke Mode PR2

Epson PLQ-20 mendukung multi-emulasi (ESC/P2, IBM 4722, Olivetti PR2). Web Koperasi Swamitra dirancang menggunakan protokol standar perbankan **Olivetti PR2**.

Jika printer sebelumnya disetel ke emulasi ESC/P2 atau IBM, printer tidak akan merespons kode kendala PR2.

### Langkah Mengubah Emulasi ke PR2 via Panel Depan:
1. Matikan printer (tombol Power ke posisi **OFF**).
2. Siapkan 1 lembar kertas HVS ukuran A4.
3. Tekan dan tahan tombol **F1** dan **F2** secara bersamaan pada panel depan printer, lalu nyalakan printer (Power **ON**).
4. Tahan kedua tombol tersebut hingga jarum printer bergerak dan printer berbunyi *beep*.
5. Masukkan kertas A4 ke mulut depan printer.
6. Printer akan mencetak lembar menu konfigurasi (*Default Setting Mode*) dalam bahasa Inggris.
7. Amati pilihan menu **Emulation**:
   - Jika saat ini tertulis `ESC/P2` atau `IBM`, tekan tombol panel sesuai petunjuk cetakan (biasanya tombol **Offline** atau **F1/F2**) untuk berpindah pilihan hingga terpilih **PR2**.
8. Simpan pengaturan dengan menekan tombol **Offline** sesuai instruksi pada lembar cetak hingga terdengar bunyi konfirmasi beep.
9. Matikan printer, lalu nyalakan kembali secara normal. Printer kini siap menerima perintah PR2.

---

## 7. Memeriksa Log Diagnostik QZ Tray di Windows

Jika Anda ingin memastikan apakah QZ Tray menerima perintah dari browser dan berhasil menyerahkannya ke Windows Spooler API:

1. Di PC Windows teller, klik ikon panah `^` di System Tray (pojok kanan bawah layar).
2. Klik kanan ikon **QZ Tray** (huruf Q hijau).
3. Pilih menu **Diagnostic Console** (atau buka file log langsung di penjelajah file).
4. Lokasi file log di Windows:
   ```text
   %APPDATA%\qz\logs\qz-tray.log
   ```
   *(Tekan Win+R, tempel path di atas, lalu Enter).*
5. Periksa baris log pada waktu pencetakan terakhir. Contoh log normal berhasil:
   ```text
   [DEBUG] 2025-05-18 10:15:32 - Received print request for EPSON-PLQ20-RAW
   [DEBUG] 2025-05-18 10:15:32 - Processing raw command data (412 bytes)
   [INFO]  2025-05-18 10:15:32 - Spooling to EPSON-PLQ20-RAW via Win32 Spooler API
   [INFO]  2025-05-18 10:15:32 - Print job 42 queued successfully
   ```
6. Jika pada log muncul `Print job queued successfully` namun fisik tidak bergerak, 100% kendala berada pada **Driver (butuh Generic/Text Only)**, **Port USB**, atau **Kertas belum dimasukkan ke sensor printer**.

---

## 8. Ringkasan Checklist Cepat IT Support (Troubleshooting Sheet)

| No | Poin Pemeriksaan | Kondisi Ideal | Cara Menguji |
|---|---|---|---|
| 1 | **Status Service QZ Tray** | Ikon QZ hijau di system tray | Buka `http://localhost:8182` di browser |
| 2 | **Dropdown Web `/test-cetak`** | Memilih printer fisik / `EPSON-PLQ20-RAW` | Jangan pilih *Microsoft Print to PDF* |
| 3 | **Aliran Spooler Windows** | Dokumen muncul saat di-pause | Centang *Pause Printing*, klik cetak di web |
| 4 | **Tipe Driver Windows** | Driver bertipe `Generic / Text Only` | Cek di *Printer properties > tab Advanced* |
| 5 | **Port USB Windows** | Port aktif terhubung ke printer (`USB00x`) | Cek di *Printer properties > tab Ports* |
| 6 | **Kertas / Buku Tabungan** | Kertas sudah disedot motor & lampu Ready ON | Masukkan buku tabungan sebelum klik cetak |
| 7 | **Emulasi Hardware** | Mode hardware disetel ke **PR2** | Nyalakan printer sambil tahan F1+F2 |
