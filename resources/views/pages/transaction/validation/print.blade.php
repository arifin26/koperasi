<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Cetak Validasi Transaksi' }}</title>
    <style>
        @page {
            size: auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            font-family: 'Courier New', Courier, monospace, 'Lucida Console', Monaco;
            font-size: 10pt;
            line-height: 1.25;
            color: #000;
        }
        .validation-slip {
            width: 140mm;
            padding-top: {{ $topMargin ?? '6mm' }};
            padding-left: {{ $leftMargin ?? '10mm' }};
            padding-right: {{ $rightMargin ?? '10mm' }};
            padding-bottom: 3mm;
            border-bottom: 1px solid #000;
        }
        .validation-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            line-height: 1;
        }
        .validation-table tr {
            line-height: 1;
        }
        .validation-table td {
            padding: 0;
            vertical-align: top;
            font-weight: 700;
            letter-spacing: 0.3px;
            font-size: 10pt;
            line-height: 1.05;
            white-space: nowrap;
        }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        
        @media print {
            .no-print {
                display: none !important;
            }
        }
        
        /* Toolbar operator */
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
        <span>Cetak Validasi: <strong>{{ $typeLabel }}</strong></span>
        <button onclick="window.print()"><i class="fas fa-print"></i> Cetak Ulang</button>
        <button onclick="window.close()" style="background:#6c757d;">Tutup</button>
    </div>

    <div class="validation-slip">
        <table class="validation-table">
            <tr>
                <td class="text-left" style="width: 58%;">{{ strtoupper($typeLabel) }}</td>
                <td class="text-right" style="width: 42%;">{{ strtoupper($validatorName) }}</td>
            </tr>
            <tr>
                <td class="text-left" style="width: 58%;">Rek.{{ $accountNumber }} &nbsp; {{ $validatedAt }}</td>
                <td class="text-right" style="width: 42%;">Rp. {{ number_format($amount, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
        };
    </script>
</body>
</html>
