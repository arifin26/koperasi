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
            margin: 12mm 12mm 12mm 20mm;
        }
        * {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }
        body { font-size: 8pt; color: #222; }
        .table { font-size: 7.5pt; width: 100%; border-collapse: collapse; margin-top: 6px; }
        .table th, .table td { padding: 4px 4px; vertical-align: middle; }
        .table thead th { background-color: #f2f2f2; color: #000; font-weight: bold; border-color: #666; text-align: center; }
        .signature-table { width: 100%; margin-top: 30px; text-align: center; border: none; page-break-inside: avoid; }
        .signature-table td { border: none !important; width: 50%; font-size: 8pt; }
        .signature-table .name { margin-top: 45px; font-weight: bold; text-decoration: underline; }
    </style>
</head>

<body style="font-family: sans-serif;">
    <header class="mb-3">
        <table style="width: 100%; border: none; border-collapse: collapse;">
            <tr>
                <td style="width: 60px; vertical-align: middle; border: none; padding: 0;">
                    <img src="{{ public_path('image/LOGO KOPERASI.png') }}" alt="Logo Koperasi" style="width: 50px; height: 50px;">
                </td>
                <td style="vertical-align: middle; text-align: center; border: none; padding: 0;">
                    <h3 class="mb-0 font-weight-bold" style="font-size: 14pt; margin-bottom: 2px;">{{ strtoupper(config('app.name')) }}</h3>
                    <p class="mb-0" style="font-size: 8.5pt; color: #333;">Jl. Gadungan-Kepung, RT.04/RW.04, Sumber Bahagia, Kepung, Kec. Puncu, Kabupaten Kediri, Jawa Timur 64293</p>
                </td>
                <td style="width: 60px; border: none; padding: 0;"></td>
            </tr>
        </table>
        <hr style="border-top: 2px solid #000; margin-top: 8px; margin-bottom: 12px;">
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
