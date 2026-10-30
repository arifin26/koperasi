<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Cetak Biodata Buku Tabungan' }}</title>
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
        /*
         * Top offset: 20.4mm base (same as passbook row=1) + 17 empty rows × 6.8mm = 136mm total.
         * Left offset: aligns with DEBIT column = col-no(8mm) + col-date(14.9mm) + col-code(8mm) = 30.9mm.
         */
        .passbook-page {
            width: 150mm;
            position: relative;
            padding-top: calc(136mm + {{ request('top_margin', $topMargin ?? '0mm') }});
            padding-left: {{ request('left_margin', $leftMargin ?? '30.9mm') }};
            padding-right: {{ request('right_margin', $rightMargin ?? '0mm') }};
        }
        .biodata-table {
            border-collapse: collapse;
            table-layout: auto;
        }
        .biodata-table tr {
            height: 6.8mm;
            max-height: 6.8mm;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .biodata-table td {
            padding: 0 2px;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
            font-size: 9pt;
            font-weight: 600;
            letter-spacing: 0.2px;
            color: #000;
        }
        .col-label {
            text-align: left;
            padding-right: 2px;
        }
        .col-sep {
            text-align: center;
            width: 6px;
            padding: 0 1px;
        }
        .col-value {
            text-align: left;
            padding-left: 3px;
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
        <span>Biodata: <strong>{{ $customer->name }}</strong></span>
        <button onclick="window.print()">&#128424; Cetak Ulang</button>
        <button onclick="window.close()" style="background:#6c757d;">Tutup</button>
    </div>

    <div class="passbook-page">
        <table class="biodata-table">
            <tbody>
                <tr>
                    <td class="col-label">No. Rekening</td>
                    <td class="col-sep">:</td>
                    <td class="col-value">{{ $customer->number }}</td>
                </tr>
                <tr>
                    <td class="col-label">Tanggal Buka</td>
                    <td class="col-sep">:</td>
                    <td class="col-value">{{ \Carbon\Carbon::parse($customer->joined_at)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="col-label">Nama</td>
                    <td class="col-sep">:</td>
                    <td class="col-value">{{ $customer->name }}</td>
                </tr>
                <tr>
                    <td class="col-label">No. KTP/SIM</td>
                    <td class="col-sep">:</td>
                    <td class="col-value">{{ $customer->nik }}</td>
                </tr>
                <tr>
                    <td class="col-label">Alamat</td>
                    <td class="col-sep">:</td>
                    <td class="col-value">{{ $customer->address }}</td>
                </tr>
            </tbody>
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
