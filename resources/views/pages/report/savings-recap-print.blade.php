<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 12mm 12mm 20mm;
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
            font-size: 8pt;
            line-height: 1.25;
            color: #222;
            background-color: #fff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1a5632;
            padding-bottom: 5px;
            margin-bottom: 8px;
            border-collapse: collapse;
        }
        .header-logo {
            font-size: 11pt;
            font-weight: bold;
            color: #1a5632;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .header-sub {
            font-size: 7.5pt;
            color: #555;
            margin-top: 2px;
            line-height: 1.2;
        }
        .report-title-box {
            text-align: right;
            vertical-align: middle;
        }
        .report-title {
            font-size: 10.5pt;
            font-weight: bold;
            color: #1a5632;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .report-period {
            font-size: 8pt;
            font-weight: bold;
            color: #444;
            margin-top: 2px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 8px;
            font-size: 7.5pt;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 1px 3px;
            vertical-align: top;
            border: none;
        }
        .meta-label {
            width: 12%;
            font-weight: bold;
            color: #444;
        }
        .meta-colon {
            width: 1%;
            text-align: center;
        }
        .meta-value {
            width: 37%;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 7.5pt;
        }
        .data-table th, .data-table td {
            border: 1px solid #1a5632;
            padding: 4px 6px;
            vertical-align: middle;
        }
        .data-table th {
            background-color: #f4f8f5;
            color: #1a5632;
            font-weight: bold;
            text-align: center;
            font-size: 7.5pt;
            letter-spacing: 0.2px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 6.8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background-color: #e6f9ee; color: #1a5632; border: 0.5px solid #1a5632; }
        .badge-danger { background-color: #ffe5e5; color: #c82333; border: 0.5px solid #c82333; }
        .summary-row th, .summary-row td {
            font-weight: bold;
            background-color: #f4f8f5;
            color: #1a5632;
            border-top: 2px solid #1a5632;
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
            font-size: 7.5pt;
            line-height: 1.2;
            border: none !important;
        }
        .sign-space {
            height: 45px;
        }
        .sign-name {
            font-weight: bold;
            text-decoration: underline;
            font-size: 8pt;
            line-height: 1.2;
            color: #222;
        }
        .sign-title {
            font-size: 7pt;
            color: #555;
            margin-top: 2px;
        }
        .footer-note {
            margin-top: 15px;
            font-size: 6.5pt;
            color: #777;
            border-top: 0.5px dotted #ccc;
            padding-top: 2px;
            line-height: 1.1;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: middle;">
                <table style="border-collapse: collapse; width: 100%;">
                    <tr>
                        <td style="width: 48px; vertical-align: middle; padding-right: 8px;">
                            <img src="{{ public_path('image/LOGO KOPERASI.png') }}" alt="Logo Koperasi" style="width: 44px; height: 44px;">
                        </td>
                        <td style="vertical-align: middle;">
                            <div class="header-logo">{{ config('app.name') }}</div>
                            <div class="header-sub">Jl. Sesama No. 47 RT. 16 &bull; Telp: 0851-4306-4088 &bull; Badan Hukum KSP</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 45%;" class="report-title-box">
                <div class="report-title">REKAP SIMPANAN NASABAH</div>
                <div class="report-period">Per: {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</div>
            </td>
        </tr>
    </table>

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
            <td class="meta-label">Filter Status</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">
                @if($statusFilter == 'active')
                    Nasabah Aktif
                @elseif($statusFilter == 'blacklist')
                    Nasabah Blacklist
                @else
                    Semua Status
                @endif
            </td>
            <td class="meta-label">Total Nasabah</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">{{ count($data) }} Orang</td>
        </tr>
    </table>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">No. Rekening</th>
                <th style="width: 16%;">Nama Nasabah</th>
                <th style="width: 28%;">Alamat</th>
                <th style="width: 9%;">Jml Transaksi</th>
                <th style="width: 22%;">Saldo Simpanan (Rp)</th>
                <th style="width: 11%;">Bunga/Bulan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $i => $customer)
            @php
                $lastDeposit = $customer->deposits->first();
                $saldo = $lastDeposit ? ($lastDeposit->current_balance ?? 0) : 0;
                $rate = $customer->interestRate->rate_percent ?? 0;
                $bungaBulan = floor($saldo * ($rate / 100) / 12);
            @endphp
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td class="text-center">{{ $customer->number ?? '-' }}</td>
                <td><strong>{{ $customer->name ?? '-' }}</strong></td>
                <td>{{ $customer->address ?? '-' }}</td>
                <td class="text-center">{{ $customer->deposits_count ?? 0 }} kali</td>
                <td class="text-right"><strong>Rp {{ number_format($saldo, 0, ',', '.') }}</strong></td>
                <td class="text-right">Rp {{ number_format($bungaBulan, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 15px; color: #888;">Tidak ada data nasabah yang sesuai.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="summary-row">
                <th colspan="6" class="text-right">TOTAL DANA SIMPANAN NASABAH</th>
                <th class="text-right">Rp {{ number_format($totalSaldo, 0, ',', '.') }}</th>
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
