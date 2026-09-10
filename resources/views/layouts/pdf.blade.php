<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css"
        integrity="sha384-zCbKRCUGaJDkqS1kPbPd7TveP5iyJE0EjAuZQTgFLD2ylzuqKfdKlfG/eSrtxUkn" crossorigin="anonymous">
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 15mm 15mm 25mm;
        }
        * {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            color: #000 !important;
        }
        body, table, th, td, div, p, span, h1, h2, h3, h4, h5, h6 {
            color: #000 !important;
        }
        body { font-size: 8pt; color: #000; }
        .table { font-size: 7.5pt; width: 100%; border-collapse: collapse; margin-top: 6px; color: #000; }
        .table th, .table td { padding: 4px 4px; vertical-align: middle; border-color: #000 !important; color: #000 !important; }
        .table thead th { background-color: #f2f2f2; color: #000 !important; font-weight: bold; border-color: #000 !important; text-align: center; }
        .signature-table { width: 100%; margin-top: 30px; text-align: center; border: none; page-break-inside: avoid; }
        .signature-table td { border: none !important; width: 50%; font-size: 8pt; color: #000 !important; }
        .signature-table .name { margin-top: 45px; font-weight: bold; text-decoration: underline; color: #000 !important; }
    </style>
</head>

<body style="font-family: sans-serif; color: #000;">
    <header class="mb-3">
        <table style="width: 100%; border-collapse: collapse; border-bottom: 3px double #000; padding-bottom: 4px; margin-bottom: 10px;">
            <tr>
                <td style="width: 65px; vertical-align: middle; text-align: left; padding: 0;">
                    <img src="{{ public_path('image/LOGO KOPERASI.png') }}" alt="Logo Koperasi" style="width: 58px; height: 58px;">
                </td>
                <td style="vertical-align: middle; text-align: center; padding: 0 10px;">
                    <div style="font-size: 13pt; font-weight: bold; letter-spacing: 0.5px; line-height: 1.15; color: #000; font-family: 'Times New Roman', Times, serif;">KOPERASI UNIT DESA &ldquo; TANI JAYA &rdquo;</div>
                    <div style="font-size: 10.5pt; font-weight: bold; letter-spacing: 0.5px; line-height: 1.2; color: #000; margin-top: 2px; font-family: 'Times New Roman', Times, serif;">UNIT SIMPAN PINJAM</div>
                    <div style="font-size: 8.5pt; font-weight: bold; line-height: 1.2; color: #000; margin-top: 2px;">Di Gadungan - Kec. Puncu - Kediri Propinsi Jawa Timur</div>
                    <div style="font-size: 7.5pt; line-height: 1.2; color: #000; margin-top: 1px;">Badan Hukum No. 4428/BH/II/80. Tanggal 23 September 1996</div>
                    <div style="font-size: 7.5pt; line-height: 1.2; color: #000; margin-top: 1px;">Telp. (0354) 393063</div>
                </td>
                <td style="width: 65px; vertical-align: middle; padding: 0;"></td>
            </tr>
        </table>
    </header>

    <section class="mb-3">
        <h4 class="text-center text-uppercase font-weight-bold mb-3">{{ $title }}</h4>
        <table style="width: 100%; margin-bottom: 15px; border: none;">
            <tr>
                <td style="width: 15%; font-weight: bold; border: none; padding: 2px;">Periode</td>
                <td style="width: 35%; border: none; padding: 2px;">: {{ $filter ?? '-' }}</td>
                <td style="width: 15%; font-weight: bold; border: none; padding: 2px;">Dicetak Oleh</td>
                <td style="width: 35%; border: none; padding: 2px;">: {{ $user->name ?? '-' }} ({{ $user->username ?? '-' }})</td>
            </tr>
            <tr>
                <td style="font-weight: bold; border: none; padding: 2px;">Tanggal Cetak</td>
                <td style="border: none; padding: 2px;">: {{ $date ?? \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</td>
                <td style="border: none; padding: 2px;"></td>
                <td style="border: none; padding: 2px;"></td>
            </tr>
        </table>
        @yield('header')
    </section>

    <section>
        @yield('content')
    </section>

    <section>
        <table class="signature-table">
            <tr>
                <td>
                    <span>Dibuat oleh:</span>
                    <div class="name">{{ $user->name ?? '....................................' }}</div>
                    <div>{{ ucfirst($user->role ?? 'Teller') }}</div>
                </td>
                <td>
                    <span>Diketahui oleh:</span>
                    <div class="name">{{ $manager->name ?? '....................................' }}</div>
                    <div>Manager</div>
                </td>
            </tr>
        </table>
    </section>
</body>
</html>
