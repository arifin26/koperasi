<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .header p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { border: 1px solid #333; padding: 5px 8px; text-align: left; }
        table th { background-color: #eee; }
        .text-right { text-align: right; }
        .footer { margin-top: 40px; }
        .footer table { border: none; }
        .footer td { border: none !important; text-align: center; padding-top: 60px; }
        .meta { font-size: 10px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ config('app.name') }}</h2>
        <h3>LAPORAN DEPOSITO</h3>
        <p>Dicetak pada: {{ $date }}</p>
    </div>

    <div class="meta">
        <p>Dicetak oleh: {{ $user->name }} ({{ $user->username }})</p>
    </div>

    <table>
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
