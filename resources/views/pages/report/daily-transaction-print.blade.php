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
        .summary { margin-top: 10px; }
        .summary td { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ config('app.name') }}</h2>
        <h3>LAPORAN TRANSAKSI HARIAN</h3>
        <p>Tanggal: {{ $date }}</p>
    </div>

    <div class="meta">
        <p>Dicetak oleh: {{ $user->name }} ({{ $user->username }})</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Waktu</th>
                <th>No. Rekening</th>
                <th>Nasabah</th>
                <th>Jenis</th>
                <th>Keterangan</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ (\Carbon\Carbon::parse($row->created_at)->format('H:i:s') === '00:00:00' && $row->updated_at) ? \Carbon\Carbon::parse($row->updated_at)->format('H:i') : \Carbon\Carbon::parse($row->created_at)->format('H:i') }}</td>
                <td>{{ $row->customer->number ?? '-' }}</td>
                <td>{{ $row->customer->name ?? '-' }}</td>
                <td>{{ ucfirst($row->type) }}</td>
                <td>{{ $row->notes ?? '-' }}</td>
                <td class="text-right">{{ $row->type !== 'penarikan' ? number_format($row->amount, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $row->type === 'penarikan' ? number_format($row->amount, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ number_format($row->current_balance, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="summary">
                <th colspan="6" class="text-right">TOTAL</th>
                <th class="text-right">{{ number_format($totalMasuk, 0, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalKeluar, 0, ',', '.') }}</th>
                <th></th>
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
