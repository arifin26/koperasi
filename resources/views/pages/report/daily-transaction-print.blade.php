<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 15mm 15mm 25mm;
        }
        * {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.25;
            color: #222;
            background-color: #fff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 8px;
            border-collapse: collapse;
        }
        .header-logo {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .header-sub {
            font-size: 14px;
            color: #000;
            margin-top: 2px;
            line-height: 1.2;
        }
        .report-title-box {
            text-align: right;
            vertical-align: middle;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .report-period {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            margin-top: 2px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 8px;
            font-size: 14px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 1px 3px;
            vertical-align: top;
            border: none;
        }
        .meta-label {
            width: 10%;
            font-weight: bold;
            color: #000;
        }
        .meta-colon {
            width: 1%;
            text-align: center;
        }
        .meta-value {
            width: 39%;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 14px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: middle;
        }
        .data-table th {
            background-color: #f4f8f5;
            color: #000;
            font-weight: bold;
            text-align: center;
            font-size: 14px;
            letter-spacing: 0.2px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-row th, .summary-row td {
            font-weight: bold;
            background-color: #f4f8f5;
            color: #000;
            border-top: 2px solid #000;
        }
        .signature-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 14px;
            line-height: 1.2;
            border: none !important;
        }
        .sign-space {
            height: 45px;
        }
        .sign-name {
            font-weight: bold;
            text-decoration: underline;
            font-size: 14px;
            line-height: 1.2;
            color: #000;
        }
        .sign-title {
            font-size: 14px;
            color: #000;
            margin-top: 2px;
        }
        .footer-note {
            margin-top: 15px;
            font-size: 14px;
            color: #000;
            border-top: 0.5px dotted #ccc;
            padding-top: 2px;
            line-height: 1.1;
        }
    </style>
</head>
<body>
    <!-- Header Kop Resmi -->
    <table style="width: 100%; border-collapse: collapse; border-bottom: 3px double #000; padding-bottom: 4px; margin-bottom: 10px;">
        <tr>
            <td style="width: 65px; vertical-align: middle; text-align: left; padding: 0;">
                <img src="{{ public_path('image/LOGO KOPERASI.png') }}" alt="Logo Koperasi" style="width: 58px; height: 58px;">
            </td>
            <td style="vertical-align: middle; text-align: center; padding: 0 10px;">
                <div style="font-size: 14px; font-weight: bold; letter-spacing: 0.5px; line-height: 1.15; color: #000; font-family: 'Times New Roman', Times, serif;">KOPERASI UNIT DESA &ldquo; TANI JAYA &rdquo;</div>
                <div style="font-size: 14px; font-weight: bold; letter-spacing: 0.5px; line-height: 1.2; color: #000; margin-top: 2px; font-family: 'Times New Roman', Times, serif;">UNIT SIMPAN PINJAM</div>
                <div style="font-size: 14px; font-weight: bold; line-height: 1.2; color: #000; margin-top: 2px;">Di Gadungan - Kec. Puncu - Kediri Propinsi Jawa Timur</div>
                <div style="font-size: 14px; line-height: 1.2; color: #000; margin-top: 1px;">Badan Hukum No. 4428/BH/II/80. Tanggal 23 September 1996</div>
                <div style="font-size: 14px; line-height: 1.2; color: #000; margin-top: 1px;">Telp. (0354) 393063</div>
            </td>
            <td style="width: 65px; vertical-align: middle; padding: 0;"></td>
        </tr>
    </table>

    <div style="text-align: center; margin-bottom: 8px;">
        <div style="font-size: 14px; font-weight: bold; text-transform: uppercase; color: #000;">LAPORAN TRANSAKSI HARIAN</div>
        <div style="font-size: 14px; font-weight: bold; color: #000; margin-top: 2px;">Tanggal: {{ $date }}</div>
    </div>

    <!-- Meta Info -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Dicetak Oleh</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">{{ $user->name ?? '-' }} ({{ ucfirst($user->role ?? 'Teller') }})</td>
            <td class="meta-label">Tanggal Cetak</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y HH:mm') }} WIB</td>
        </tr>
        <tr>
            <td class="meta-label">Total Transaksi</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">{{ count($data) }} Transaksi</td>
            <td class="meta-label">Status Laporan</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">Final</td>
        </tr>
    </table>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 5%;">Waktu</th>
                <th style="width: 10%;">No. Rekening</th>
                <th style="width: 14%;">Nama Nasabah</th>
                <th style="width: 18%;">Alamat</th>
                <th style="width: 7%;">Jenis</th>
                <th>Keterangan</th>
                <th style="width: 11%;">Masuk (Rp)</th>
                <th style="width: 11%;">Keluar (Rp)</th>
                <th style="width: 12%;">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $i => $row)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td class="text-center">{{ (\Carbon\Carbon::parse($row->created_at)->format('H:i:s') === '00:00:00' && $row->updated_at) ? \Carbon\Carbon::parse($row->updated_at)->format('H:i') : \Carbon\Carbon::parse($row->created_at)->format('H:i') }}</td>
                <td>{{ $row->customer->number ?? '-' }}</td>
                <td><strong>{{ $row->customer->name ?? '-' }}</strong></td>
                <td>{{ $row->customer->address ?? '-' }}</td>
                <td class="text-center">{{ ucfirst($row->type) }}</td>
                <td>{{ $row->notes ?? '-' }}</td>
                <td class="text-right">{{ $row->type !== 'penarikan' ? number_format($row->amount, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $row->type === 'penarikan' ? number_format($row->amount, 0, ',', '.') : '-' }}</td>
                <td class="text-right"><strong>{{ number_format($row->current_balance, 0, ',', '.') }}</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 15px; color: #000;">Tidak ada transaksi pada tanggal ini.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="summary-row">
                <th colspan="7" class="text-right">TOTAL TRANSAKSI</th>
                <th class="text-right">{{ number_format($totalMasuk, 0, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalKeluar, 0, ',', '.') }}</th>
                <th class="text-right">Selisih: {{ number_format($totalMasuk - $totalKeluar, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <!-- Signatures -->
    <table class="signature-table">
        <tr>
            <td>
                <div>Dibuat oleh,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $user->name ?? '....................................' }}</div>
                <div class="sign-title">{{ ucfirst($user->role ?? 'Teller') }}</div>
            </td>
            <td>
                <div>Kediri, {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</div>
                <div style="margin-top: 1px;">Diketahui oleh,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $manager->name ?? '....................................' }}</div>
                <div class="sign-title">Manajer</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        * Dokumen ini dicetak otomatis dari Sistem Informasi Koperasi pada {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y HH:mm:ss') }} WIB dan merupakan dokumen laporan yang sah.
    </div>
</body>
</html>
