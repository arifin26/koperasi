<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Cetak Buku Tabungan' }}</title>
    <style>
        @page {
            size: auto;
            margin: 0mm !important;
        }
        * {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100%;
            background: #fff;
            font-family: 'Courier New', Courier, monospace, 'Lucida Console', Monaco;
            font-size: 9.5pt;
            line-height: 1;
            color: #000;
        }
        .passbook-page {
            width: 150mm;
            position: relative;
            padding-top: {{ $topMargin ?? '0mm' }};
            padding-left: {{ $leftMargin ?? '6mm' }};
            padding-right: {{ $rightMargin ?? '6mm' }};
        }
        /* Tabel Passbook */
        .passbook-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .passbook-table tr {
            height: 6.8mm;
            max-height: 6.8mm;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .passbook-table td {
            padding: 0 2px;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
            font-size: 9pt;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        /* Kolom Passbook */
        .col-date {
            width: 19mm;
            text-align: left;
        }
        .col-code {
            width: 16mm;
            text-align: center;
        }
        .col-debit {
            width: 26mm;
            text-align: right;
        }
        .col-credit {
            width: 26mm;
            text-align: right;
        }
        .col-balance {
            width: 33mm;
            text-align: right;
        }
        .col-teller {
            width: 14mm;
            text-align: center;
        }
        /* Baris kosong penyesuai posisi awal */
        .empty-row {
            height: 6.8mm;
            visibility: hidden;
        }
        @media print {
            @page {
                size: auto;
                margin: 0mm !important;
            }
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                width: 100%;
                background: transparent;
            }
            .no-print {
                display: none !important;
            }
        }
        /* Toolbar bantu untuk operator */
        .print-toolbar {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #333;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            font-family: sans-serif;
            font-size: 12px;
            z-index: 9999;
        }
        .print-toolbar button {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="print-toolbar no-print">
        @if (($startRow ?? 1) == 1 && count($transactions) > 1)
            <span>Mode: <strong>Bulk Print (Baris 1 s/d {{ count($transactions) }})</strong> | Total: <strong>{{ count($transactions) }} Baris</strong></span>
        @else
            <span>Baris Awal: <strong>Baris {{ $startRow ?? 1 }}</strong> | Total Baris: <strong>{{ count($transactions) }}</strong></span>
        @endif
        <button onclick="window.print()"><i class="fas fa-print"></i> Cetak Ulang</button>
        <button onclick="window.close()" style="background:#6c757d;">Tutup</button>
    </div>

    <div class="passbook-page">
        <table class="passbook-table">
            <tbody>
                {{-- Baris kosong sebelum baris awal --}}
                @for ($i = 1; $i < ($startRow ?? 1); $i++)
                    <tr class="empty-row">
                        <td class="col-date">&nbsp;</td>
                        <td class="col-code">&nbsp;</td>
                        <td class="col-debit">&nbsp;</td>
                        <td class="col-credit">&nbsp;</td>
                        <td class="col-balance">&nbsp;</td>
                        <td class="col-teller">&nbsp;</td>
                    </tr>
                @endfor

                {{-- Baris transaksi --}}
                @foreach ($transactions as $txn)
                    @php
                        $isDebit = ($txn->type === 'penarikan');
                        $debitVal = $isDebit ? number_format($txn->amount, 0, ',', '.') : '-';
                        $creditVal = !$isDebit ? number_format($txn->amount, 0, ',', '.') : '-';
                        $balanceVal = number_format($txn->current_balance, 0, ',', '.');

                        $notesLower = strtolower($txn->notes ?? '');

                        // Kode sandi transaksi buku tabungan:
                        // 1: Setor (simpanan reguler/pokok/wajib/sukarela)
                        // 2: Penarikan
                        // 3: Bunga Deposito
                        // 4: Bunga Simpanan
                        if ($isDebit) {
                            $code = '2'; // Penarikan
                        } elseif ($txn->type === 'bunga') {
                            if (str_contains($notesLower, 'deposito')) {
                                $code = '3'; // Bunga Deposito
                            } else {
                                $code = '4'; // Bunga Simpanan
                            }
                        } else {
                            $code = '1'; // Setor
                        }

                        $teller = $txn->creator->name ?? 'ADM';
                        $tellerInitial = strtoupper(substr(trim($teller), 0, 3));
                    @endphp
                    <tr>
                        <td class="col-date">{{ \Carbon\Carbon::parse($txn->created_at)->format('d/m/y') }}</td>
                        <td class="col-code">{{ $code }}</td>
                        <td class="col-debit">{{ $debitVal }}</td>
                        <td class="col-credit">{{ $creditVal }}</td>
                        <td class="col-balance">{{ $balanceVal }}</td>
                        <td class="col-teller">{{ $tellerInitial }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
        window.onload = function() {
            // Auto trigger print saat halaman dibuka
            setTimeout(function() {
                window.print();
            }, 300);
        };
    </script>
</body>
</html>
