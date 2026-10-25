<?php

namespace App\Services;

class PassbookPr2
{
    // Konstanta Perintah PR2 (dengan komentar byte hex)
    public const CMD_RESET              = "\x1B\x30";      // ESC 0 (1B 30) Software reset
    public const CMD_BOOK_MODE          = "\x1B\x53\x35";  // ESC S 5 (1B 53 35) Pilih mode passbook
    public const CMD_BINDING_HORIZ      = "\x1B\x27\x46";  // ESC ' F (1B 27 46) Seam horizontal (verifikasi bit manual PLQ-20)
    public const CMD_BINDING_VERT       = "\x1B\x27\x44";  // ESC ' D (1B 27 44) Seam vertikal (verifikasi bit manual PLQ-20)
    public const CMD_PITCH_10CPI        = "\x1B\x3C";      // ESC < (1B 3C) Pitch 10 CPI
    public const CMD_PITCH_12CPI        = "\x1B\x3D";      // ESC = (1B 3D) Pitch 12 CPI
    public const CMD_FONT_PREFIX        = "\x1B\x52";      // ESC R nnn (1B 52 + 3 ASCII digit) Pemilihan font
    public const CMD_PAGE_LENGTH_PREFIX = "\x1B\x43";      // ESC C nnn / ESC C 0 nn (1B 43) Perintah panjang halaman
    public const CMD_VERT_REF_EDGE      = "\x1B\x23\x30";  // ESC # 0 (1B 23 30) Referensi vertikal tepi atas kertas
    public const CMD_VERT_REF_TOF       = "\x1B\x23\x31";  // ESC # 1 (1B 23 31) Referensi vertikal Top Of Form (TOF)
    public const CMD_FINE_LEFT_OFFSET   = "\x1B\x7C\x41";  // ESC | A nnn (1B 7C 41 + 3 digit ASCII) Unit 1/120 inch
    public const CMD_VERT_POS_EDGE      = "\x1B\x2F\x31";  // ESC / 1 nnnn (1B 2F 31 + 4 digit ASCII) Posisi vertikal dari tepi atas
    public const CMD_HORIZ_COL_POS      = "\x1B\x48";      // ESC H nnn (1B 48 + 3 digit ASCII) Posisi kolom karakter
    public const CMD_EJECT_FRONT        = "\x1B\x4F";      // ESC O (1B 4F) Eject buku ke depan
    public const CMD_EJECT_REAR         = "\x0C";          // FF (0C) Form feed / Eject buku ke belakang
    public const CMD_CR                 = "\x0D";          // CR (0D) Carriage return
    public const CMD_LF                 = "\x0A";          // LF (0A) Line feed

    /**
     * Konfigurasi 7 kolom default standar buku tabungan.
     *
     * @return array<int, array{name: string, x: float, width: float, align: string}>
     */
    public static function getDefaultColumns(): array
    {
        return [
            ['name' => 'TANGGAL',    'x' => 0.0,   'width' => 15.0, 'align' => 'left'],
            ['name' => 'SANDI',      'x' => 16.0,  'width' => 10.0, 'align' => 'left'],
            ['name' => 'DEBET',      'x' => 27.0,  'width' => 24.0, 'align' => 'right'],
            ['name' => 'KREDIT',     'x' => 52.0,  'width' => 24.0, 'align' => 'right'],
            ['name' => 'PC',         'x' => 77.0,  'width' => 6.0,  'align' => 'left'],
            ['name' => 'SALDO',      'x' => 84.0,  'width' => 25.0, 'align' => 'right'],
            ['name' => 'KETERANGAN', 'x' => 110.0, 'width' => 28.0, 'align' => 'left'],
        ];
    }

    /**
     * Membangun payload PR2, hex dump, daftar peringatan, dan metadata grid.
     *
     * @param array<string, mixed> $params
     * @return array{
     *     binary: string,
     *     base64: string,
     *     hex_preview: string,
     *     peringatan: array<int, string>,
     *     grid_info: array<string, mixed>
     * }
     */
    public static function buildPayload(array $params): array
    {
        $mode               = $params['mode'] ?? 'dummy';
        $barisMulai         = (int) ($params['baris_mulai'] ?? 0);
        $jumlahBaris        = (int) ($params['jumlah_baris'] ?? 1);
        $seed               = isset($params['seed']) && $params['seed'] !== '' ? (int) $params['seed'] : null;
        $marginAtas         = (float) ($params['margin_atas'] ?? 20.0);
        $marginBawah        = (float) ($params['margin_bawah'] ?? 15.0);
        $marginKiri         = (float) ($params['margin_kiri'] ?? 6.0);
        $marginKanan        = (float) ($params['margin_kanan'] ?? 6.0);
        $tinggiBaris        = (float) ($params['tinggi_baris'] ?? 4.23);
        $lebarHalaman       = (float) ($params['lebar_halaman'] ?? 150.0);
        $tinggiHalaman      = (float) ($params['tinggi_halaman'] ?? 210.0);
        $pitch              = (int) ($params['pitch'] ?? 10);
        $font               = str_pad((string) ($params['font'] ?? '003'), 3, '0', STR_PAD_LEFT);
        $unitVertikal       = (int) ($params['unit_vertikal'] ?? 240);
        $referensiVertikal  = $params['referensi_vertikal'] ?? 'edge';
        $tipeJilid          = $params['tipe_jilid'] ?? 'horizontal';
        $modeKeluar         = $params['mode_keluar'] ?? 'none';
        $columns            = $params['columns'] ?? self::getDefaultColumns();

        $warnings = [];

        // 1. Perhitungan pitch dan lebar karakter (mm)
        $charWidthMm = 25.4 / $pitch; // 10 CPI = 2.54 mm, 12 CPI = ~2.11667 mm

        // 2. Batas area cetak
        $printableWidthMm  = max(0.0, $lebarHalaman - $marginKanan - $marginKiri);
        $printableHeightMm = max(0.0, $tinggiHalaman - $marginBawah - $marginAtas);
        $maxY              = $tinggiHalaman - $marginBawah;
        $maxChars          = (int) floor($printableWidthMm / $charWidthMm);

        // 3. Susun Header Perintah PR2
        $payload = '';
        $payload .= self::CMD_RESET;     // ESC 0
        $payload .= self::CMD_BOOK_MODE; // ESC S 5

        // Tipe jilid / seam
        if ($tipeJilid === 'vertical') {
            $payload .= self::CMD_BINDING_VERT;  // ESC ' D
        } else {
            $payload .= self::CMD_BINDING_HORIZ; // ESC ' F
        }

        // Pitch
        if ($pitch === 12) {
            $payload .= self::CMD_PITCH_12CPI; // ESC =
        } else {
            $payload .= self::CMD_PITCH_10CPI; // ESC <
        }

        // Font selection
        $payload .= self::CMD_FONT_PREFIX . $font; // ESC R nnn

        // Referensi vertikal
        if ($referensiVertikal === 'tof') {
            $payload .= self::CMD_VERT_REF_TOF;  // ESC # 1
        } else {
            $payload .= self::CMD_VERT_REF_EDGE; // ESC # 0
        }

        // Offset kiri halus (unit 1/120 inch)
        $fineLeftOffset = max(0, min(999, (int) round(($marginKiri / 25.4) * 120)));
        $payload .= self::CMD_FINE_LEFT_OFFSET . sprintf('%03d', $fineLeftOffset); // ESC | A nnn

        // 4. Bangun data baris (dummy atau kalibrasi)
        $rowsData = $mode === 'calibration'
            ? self::generateCalibrationRows($jumlahBaris, $columns, $charWidthMm, $maxChars)
            : self::generateDummyRows($barisMulai, $jumlahBaris, $seed);

        // 5. Susun isi tiap baris cetak
        $gridLines = [];
        for ($i = 0; $i < $jumlahBaris; $i++) {
            $lineIndex = $barisMulai + $i;
            $lineDisplay = $lineIndex + 1;
            $yMm = $marginAtas + ($lineIndex * $tinggiBaris);

            $lineInfo = [
                'line_index' => $lineIndex,
                'line_display' => $lineDisplay,
                'y_mm' => round($yMm, 2),
                'is_printed' => true,
                'data' => $rowsData[$i] ?? [],
            ];

            // Cek apakah baris melebihi batas bawah kertas
            if ($yMm > $maxY) {
                $lineInfo['is_printed'] = false;
                $warnings[] = sprintf(
                    'Baris %d melewati batas bawah (%.2f mm > %.2f mm)',
                    $lineDisplay,
                    $yMm,
                    $maxY
                );
                $gridLines[] = $lineInfo;
                continue;
            }

            // Perintah posisi vertikal dari tepi atas
            $vertUnits = max(0, min(9999, (int) round(($yMm / 25.4) * $unitVertikal)));
            $payload .= self::CMD_VERT_POS_EDGE . sprintf('%04d', $vertUnits); // ESC / 1 nnnn

            $rowData = $rowsData[$i] ?? [];

            if ($mode === 'calibration') {
                // Kalibrasi: cetak garis penggaris underscore di seluruh baris
                $rulerUnderscores = str_repeat('_', max(1, $maxChars));
                $payload .= self::CMD_HORIZ_COL_POS . sprintf('%03d', 0);
                $payload .= $rulerUnderscores;
                $payload .= self::CMD_CR; // Balikkan head ke margin kiri tanpa feed

                // Cetak angka digit di setiap kolom
                foreach ($columns as $col) {
                    $colName = $col['name'];
                    $text = (string) ($rowData[$colName] ?? '0123456789');
                    $colCharPos = max(0, (int) round($col['x'] / $charWidthMm));
                    $colWidthChars = max(1, (int) round($col['width'] / $charWidthMm));

                    // Potong teks jika melebihi lebar kolom atau batas kanan
                    if ($colCharPos + strlen($text) > $maxChars) {
                        $allowed = max(0, $maxChars - $colCharPos);
                        $text = substr($text, 0, $allowed);
                        $warnings[] = sprintf(
                            'Kolom %s pada baris %d dipotong karena melewati batas kanan',
                            $colName,
                            $lineDisplay
                        );
                    }

                    if ($text !== '') {
                        $payload .= self::CMD_HORIZ_COL_POS . sprintf('%03d', min(999, $colCharPos));
                        $payload .= $text;
                    }
                }
                $payload .= self::CMD_CR;
            } else {
                // Mode Dummy Data: cetak setiap kolom dengan posisi dan perataan
                foreach ($columns as $col) {
                    $colName = $col['name'];
                    $text = (string) ($rowData[$colName] ?? '');
                    if ($text === '') {
                        continue;
                    }

                    $colCharPos = max(0, (int) round($col['x'] / $charWidthMm));
                    $colWidthChars = max(1, (int) round($col['width'] / $charWidthMm));
                    $isRightAlign = in_array(strtoupper($colName), ['DEBET', 'KREDIT', 'SALDO'], true)
                        || (($col['align'] ?? 'left') === 'right');

                    if ($isRightAlign) {
                        $startChar = $colCharPos + $colWidthChars - strlen($text);
                        if ($startChar < $colCharPos) {
                            $startChar = $colCharPos;
                        }
                    } else {
                        $startChar = $colCharPos;
                    }

                    // Cek batas kanan halaman
                    if ($startChar + strlen($text) > $maxChars) {
                        $allowed = max(0, $maxChars - $startChar);
                        $text = substr($text, 0, $allowed);
                        $warnings[] = sprintf(
                            'Kolom %s pada baris %d dipotong karena melewati batas kanan',
                            $colName,
                            $lineDisplay
                        );
                    }

                    if ($text !== '') {
                        $payload .= self::CMD_HORIZ_COL_POS . sprintf('%03d', min(999, $startChar));
                        $payload .= $text;
                    }
                }
                $payload .= self::CMD_CR;
            }

            $gridLines[] = $lineInfo;
        }

        // 6. Mode keluar buku tabungan (Eject)
        if ($modeKeluar === 'front') {
            $payload .= self::CMD_EJECT_FRONT; // ESC O
        } elseif ($modeKeluar === 'rear') {
            $payload .= self::CMD_EJECT_REAR;  // FF
        }

        // 7. Grid metadata untuk pratinjau browser
        $processedColumns = [];
        foreach ($columns as $col) {
            $colCharPos = max(0, (int) round($col['x'] / $charWidthMm));
            $colWidthChars = max(1, (int) round($col['width'] / $charWidthMm));
            $processedColumns[] = [
                'name' => $col['name'],
                'x_mm' => (float) $col['x'],
                'width_mm' => (float) $col['width'],
                'align' => $col['align'] ?? 'left',
                'char_pos' => $colCharPos,
                'width_chars' => $colWidthChars,
                'end_char' => $colCharPos + $colWidthChars,
            ];
        }

        $gridInfo = [
            'mode' => $mode,
            'pitch' => $pitch,
            'char_width_mm' => round($charWidthMm, 4),
            'lebar_halaman' => $lebarHalaman,
            'tinggi_halaman' => $tinggiHalaman,
            'margin_atas' => $marginAtas,
            'margin_bawah' => $marginBawah,
            'margin_kiri' => $marginKiri,
            'margin_kanan' => $marginKanan,
            'tinggi_baris' => $tinggiBaris,
            'baris_mulai' => $barisMulai,
            'jumlah_baris' => $jumlahBaris,
            'max_chars' => $maxChars,
            'printable_width_mm' => round($printableWidthMm, 2),
            'printable_height_mm' => round($printableHeightMm, 2),
            'columns' => $processedColumns,
            'lines' => $gridLines,
        ];

        return [
            'binary' => $payload,
            'base64' => base64_encode($payload),
            'hex_preview' => self::formatHexDump($payload),
            'peringatan' => $warnings,
            'grid_info' => $gridInfo,
        ];
    }

    /**
     * Menghasilkan data dummy passbook perbankan 7 kolom dengan saldo berjalan non-negatif.
     *
     * @param int $barisMulai
     * @param int $jumlahBaris
     * @param int|null $seed
     * @return array<int, array<string, string>>
     */
    public static function generateDummyRows(int $barisMulai, int $jumlahBaris, ?int $seed = null): array
    {
        if ($seed !== null) {
            mt_srand($seed);
        }

        $keteranganList = [
            'CCM 0009999',
            'DCM 0009999',
            'OVB 0374636',
            'ADM 0000000',
            'STR 0012847',
            'TRF 0092817',
            'BUNGA BULANAN',
            'PAJAK BUNGA',
        ];

        $sandiList = ['1001', '2001', '3001', '4001', '5001'];

        // Saldo awal dasar (selalu positif)
        $currentSaldo = 50000000.00; // Rp 50.000.000,00
        $currentDate = new \DateTime('2025-04-12');

        $rows = [];
        for ($i = 0; $i < $jumlahBaris; $i++) {
            $currentDate->modify('+ ' . mt_rand(1, 4) . ' days');
            $tanggal = $currentDate->format('dmy'); // ddmmyy, misal 120425
            $sandi = $sandiList[array_rand($sandiList)];
            $pc = sprintf('%02d', ($barisMulai + $i + 1) % 100);

            // Tentukan apakah transaksi Debet atau Kredit
            $isKredit = (mt_rand(1, 100) > 40) || ($currentSaldo < 2000000.00);

            $debetStr = '';
            $kreditStr = '';

            if ($isKredit) {
                // Setoran / Kredit
                $amount = round(mt_rand(10, 250) * 50000, 2); // 500rb - 12.5jt
                $currentSaldo += $amount;
                $kreditStr = number_format($amount, 2, '.', ',');
            } else {
                // Penarikan / Debet (tidak boleh membuat saldo negatif)
                $maxDebet = max(100000.00, $currentSaldo - 500000.00);
                $amount = round(min($maxDebet, mt_rand(5, 100) * 50000), 2);
                $currentSaldo = max(0.00, $currentSaldo - $amount);
                $debetStr = number_format($amount, 2, '.', ',');
            }

            $saldoStr = number_format($currentSaldo, 2, '.', ',');
            $keterangan = $keteranganList[array_rand($keteranganList)];

            $rows[] = [
                'TANGGAL' => $tanggal,
                'SANDI' => $sandi,
                'DEBET' => $debetStr,
                'KREDIT' => $kreditStr,
                'PC' => $pc,
                'SALDO' => $saldoStr,
                'KETERANGAN' => $keterangan,
            ];
        }

        return $rows;
    }

    /**
     * Menghasilkan data baris kalibrasi (penggaris dan digit per kolom).
     *
     * @param int $jumlahBaris
     * @param array<int, array{name: string, x: float, width: float}> $columns
     * @param float $charWidthMm
     * @param int $maxChars
     * @return array<int, array<string, string>>
     */
    public static function generateCalibrationRows(int $jumlahBaris, array $columns, float $charWidthMm, int $maxChars): array
    {
        $rows = [];
        for ($i = 0; $i < $jumlahBaris; $i++) {
            $row = [];
            foreach ($columns as $col) {
                $colName = $col['name'];
                $colWidthChars = max(1, (int) round($col['width'] / $charWidthMm));
                // Bentuk deretan digit 0123456789 berulang sepanjang lebar kolom
                $digits = str_repeat('0123456789', (int) ceil($colWidthChars / 10) + 1);
                $row[$colName] = substr($digits, 0, $colWidthChars);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Format hexdump canonical (offset, hex, ASCII printable view).
     *
     * @param string $binary
     * @return string
     */
    public static function formatHexDump(string $binary): string
    {
        if ($binary === '') {
            return '';
        }

        $length = strlen($binary);
        $lines = [];

        for ($offset = 0; $offset < $length; $offset += 16) {
            $chunk = substr($binary, $offset, 16);
            $chunkLen = strlen($chunk);

            $hexBytes1 = [];
            $hexBytes2 = [];
            $asciiChars = '';

            for ($b = 0; $b < 16; $b++) {
                if ($b < $chunkLen) {
                    $byte = ord($chunk[$b]);
                    $hex = sprintf('%02x', $byte);
                    if ($b < 8) {
                        $hexBytes1[] = $hex;
                    } else {
                        $hexBytes2[] = $hex;
                    }
                    $asciiChars .= ($byte >= 32 && $byte <= 126) ? chr($byte) : '.';
                } else {
                    if ($b < 8) {
                        $hexBytes1[] = '  ';
                    } else {
                        $hexBytes2[] = '  ';
                    }
                }
            }

            $hexPart = implode(' ', $hexBytes1) . '  ' . implode(' ', $hexBytes2);
            $lines[] = sprintf('%08x  %-49s  |%s|', $offset, $hexPart, $asciiChars);
        }

        return implode("\n", $lines);
    }
}
