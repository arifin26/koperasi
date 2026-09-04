<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
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
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 8pt; }
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
    <!-- Header Kop Resmi -->
    <table style="width: 100%; border-collapse: collapse; border-bottom: 3px double #000; padding-bottom: 4px; margin-bottom: 10px;">
        <tr>
            <td style="width: 65px; vertical-align: middle; text-align: left; padding: 0;">
                <img src="{{ public_path('image/LOGO KOPERASI.png') }}" alt="Logo Koperasi" style="width: 58px; height: 58px;">
            </td>
            <td style="vertical-align: middle; text-align: center; padding: 0 10px;">
                <div style="font-size: 13pt; font-weight: bold; letter-spacing: 0.5px; line-height: 1.15; color: #000; font-family: 'Times New Roman', Times, serif;">KOPERASI UNIT DESA &ldquo; TANI JAYA &rdquo;</div>
                <div style="font-size: 10.5pt; font-weight: bold; letter-spacing: 0.5px; line-height: 1.2; color: #000; margin-top: 2px; font-family: 'Times New Roman', Times, serif;">UNIT SIMPAN PINJAM</div>
                <div style="font-size: 8.5pt; font-weight: bold; line-height: 1.2; color: #000; margin-top: 2px;">Di Gadungan - Kec. Puncu - Kediri Propinsi Jawa Timur</div>
                <div style="font-size: 7.5pt; line-height: 1.2; color: #222; margin-top: 1px;">Badan Hukum No. 4428/BH/II/80. Tanggal 23 September 1996</div>
                <div style="font-size: 7.5pt; line-height: 1.2; color: #222; margin-top: 1px;">Telp. (0354) 393063</div>
            </td>
            <td style="width: 65px; vertical-align: middle; padding: 0;"></td>
        </tr>
    </table>

    <div style="text-align: center; margin-bottom: 8px;">
        <h4 style="font-size: 11pt; font-weight: bold; text-transform: uppercase; margin: 0; color: #000;">LAPORAN DEPOSITO</h4>
    </div>

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
