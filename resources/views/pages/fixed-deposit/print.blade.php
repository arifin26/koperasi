<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
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
        body { font-family: Arial, sans-serif; font-size: 7.5pt; color: #222; margin: 0; padding: 0; }
        .header-table { width: 100%; border-bottom: 2px solid #1a5632; padding-bottom: 5px; margin-bottom: 8px; border-collapse: collapse; }
        .header-logo { font-size: 11pt; font-weight: bold; color: #1a5632; letter-spacing: 0.5px; text-transform: uppercase; line-height: 1.1; }
        .header-sub { font-size: 7.5pt; color: #555; margin-top: 2px; line-height: 1.2; }
        .report-title-box { text-align: right; vertical-align: middle; }
        .report-title { font-size: 10.5pt; font-weight: bold; color: #1a5632; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.1; }
        .report-period { font-size: 8pt; font-weight: bold; color: #444; margin-top: 2px; }
        .meta-table { width: 100%; margin-bottom: 8px; font-size: 7.5pt; border-collapse: collapse; }
        .meta-table td { padding: 1px 3px; vertical-align: top; border: none; }
        .meta-label { width: 12%; font-weight: bold; color: #444; }
        .meta-colon { width: 1%; text-align: center; }
        .meta-value { width: 37%; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 7pt; }
        table.data-table th, table.data-table td { border: 1px solid #333; padding: 3px 4px; vertical-align: middle; }
        table.data-table th { background-color: #eee; font-weight: bold; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 25px; page-break-inside: avoid; }
        .footer table { border: none; width: 100%; }
        .footer td { border: none !important; text-align: center; padding-top: 40px; font-size: 7.5pt; width: 50%; }
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
                <div class="report-title">LAPORAN DEPOSITO</div>
                <div class="report-period">Dicetak pada: {{ $date }}</div>
            </td>
        </tr>
    </table>

    <!-- Meta Info -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Dicetak Oleh</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">{{ $user->name }} ({{ $user->username }})</td>
            <td class="meta-label">Tanggal Cetak</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">{{ $date }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>No. Deposito</th>
                <th>No. Rekening</th>
                <th>Nama</th>
                <th>Alamat</th>
                <th>Bunga (%)</th>
                <th>Jangka Waktu</th>
                <th>Tgl Masuk</th>
                <th>Nominal</th>
                <th>Jumlah Bunga</th>
            </tr>
        </thead>
        <tbody>
            @php $totalNominal = 0; $totalBunga = 0; @endphp
            @foreach($data as $i => $row)
            @php
                $monthlyInterest = floor($row->amount * ($row->rate_percent / 100) / 12);
                $totalBungaDeposit = $monthlyInterest * $row->tenor_months;
                $totalNominal += $row->amount;
                $totalBunga += $totalBungaDeposit;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row->number }}</td>
                <td>{{ $row->customer->number ?? '-' }}</td>
                <td>{{ $row->customer->name ?? '-' }}</td>
                <td>{{ $row->customer->address ?? '-' }}</td>
                <td class="text-right">{{ $row->rate_percent }}%</td>
                <td>{{ $row->tenor_months }} Bulan</td>
                <td>{{ $row->start_date->isoFormat('DD/MM/Y') }}</td>
                <td class="text-right">{{ number_format($row->amount, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalBungaDeposit, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="8" class="text-right">TOTAL</th>
                <th class="text-right">{{ number_format($totalNominal, 0, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalBunga, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td>Dibuat oleh:<br><br><br><br>{{ $user->name }}</td>
                <td>Diketahui oleh:<br><br><br><br>{{ $manager->name ?? '...' }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
