<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'Kwitansi Transaksi' }}</title>
    <style>
        @page {
            size: {{ $pageSizeCss ?? '215mm 75mm' }};
            margin: 2.5mm 4mm 2.5mm 4mm;
        }
        * {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            color: #000 !important;
        }
        body, table, th, td, div, p, span, h1, h2, h3, h4, h5, h6 {
            color: #000 !important;
        }
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 7.5pt;
            line-height: 1.2;
            color: #000;
            background-color: #fff;
        }
        .receipt-container {
            border: 1.5px solid #000;
            padding: 3px 5px;
            position: relative;
            background-color: #fff;
            page-break-inside: avoid;
        }
        .receipt-inner {
            border: 0.5px solid #000;
            padding: 3px 6px;
        }
        .header-table {
            width: 100%;
            border-bottom: 1.5px solid #000;
            padding-bottom: 2px;
            margin-bottom: 3px;
            border-collapse: collapse;
        }
        .header-logo {
            font-size: 9.5pt;
            font-weight: bold;
            color: #000;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .header-sub {
            font-size: 6.5pt;
            color: #000;
            margin-top: 1px;
            line-height: 1.1;
        }
        .receipt-title-box {
            text-align: right;
        }
        .receipt-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.1;
        }
        .receipt-no {
            font-size: 7.5pt;
            font-weight: bold;
            color: #000;
            margin-top: 1px;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1px;
        }
        .content-table td {
            padding: 1px 2px;
            vertical-align: top;
            font-size: 7pt;
            line-height: 1.15;
            color: #000;
        }
        .label {
            width: 22%;
            color: #000;
            font-weight: bold;
        }
        .colon {
            width: 2%;
            text-align: center;
        }
        .value {
            width: 76%;
        }
        .amount-box {
            background-color: #fff;
            border: 1px dashed #000;
            padding: 3px 6px;
            margin: 3px 0;
        }
        .amount-val {
            font-size: 9.5pt;
            font-weight: bold;
            color: #000;
            line-height: 1.1;
        }
        .terbilang-text {
            font-size: 6.5pt;
            font-style: italic;
            color: #000;
            margin-top: 1px;
            line-height: 1.1;
        }
        .signature-table {
            width: 100%;
            margin-top: 2px;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 6.8pt;
            line-height: 1.1;
            color: #000;
        }
        .sign-space {
            height: 20px;
        }
        .sign-name {
            font-weight: bold;
            text-decoration: underline;
            font-size: 7pt;
            line-height: 1.1;
            color: #000;
        }
        .sign-title {
            font-size: 6.2pt;
            color: #000;
        }
        .footer-note {
            margin-top: 2px;
            font-size: 5.8pt;
            color: #000;
            border-top: 0.5px dotted #000;
            padding-top: 1px;
            line-height: 1.1;
        }
        .validation-strip {
            margin-bottom: 3px;
            padding-bottom: 2px;
            border-bottom: 0.5px dashed #000;
            font-family: 'Courier New', Courier, monospace;
        }
        .validation-table {
            width: 100%;
            border-collapse: collapse;
        }
        .validation-table td {
            font-size: 6.5pt;
            line-height: 1.15;
            color: #000;
            font-family: 'Courier New', Courier, monospace;
            padding: 0 1px;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-inner">
            <!-- Validation Strip -->
            @yield('validation-strip')

            <!-- Header -->
            <table class="header-table">
                <tr>
                    <td style="width: 58%; vertical-align: middle;">
                        <div class="header-logo">{{ config('app.name') }}</div>
                        <div class="header-sub">Jl. Sesama No. 47 RT. 16 &bull; Telp: 0851-4306-4088 &bull; Badan Hukum KSP</div>
                    </td>
                    <td style="width: 42%; vertical-align: middle;" class="receipt-title-box">
                        <div class="receipt-title">@yield('receipt-title', 'KWITANSI TRANSAKSI')</div>
                        <div class="receipt-no">No: @yield('receipt-no', '-')</div>
                    </td>
                </tr>
            </table>

            <!-- Main Content -->
            @yield('content')

            <!-- Footer / TTD -->
            <table class="signature-table">
                <tr>
                    <td>
                        <div>@yield('sign-left-label', 'Nasabah / Penyetor,')</div>
                        <div class="sign-space"></div>
                        <div class="sign-name">@yield('sign-left-name', '....................................')</div>
                        <div class="sign-title">Nasabah</div>
                    </td>
                    <td>
                        <div>@yield('sign-city', 'Kota Terkait'), @yield('sign-date', date('d/m/Y'))</div>
                        <div style="margin-top: 1px;">@yield('sign-right-label', 'Petugas / Teller,')</div>
                        <div class="sign-space"></div>
                        <div class="sign-name">@yield('sign-right-name', auth()->user()->name ?? 'Kasir')</div>
                        <div class="sign-title">@yield('sign-right-role', 'Teller / Kasir')</div>
                    </td>
                </tr>
            </table>

            <div class="footer-note">
                * Simpan kwitansi ini sebagai bukti transaksi yang sah. Dicetak otomatis pada {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y HH:mm:ss') }}.
            </div>
        </div>
    </div>
</body>
</html>
