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
        body { font-size: 12px; }
        .signature-table { width: 100%; margin-top: 50px; text-align: center; border: none; }
        .signature-table td { border: none !important; width: 50%; }
        .signature-table .name { margin-top: 70px; font-weight: bold; text-decoration: underline; }
    </style>
</head>

<body style="font-family: sans-serif;">
    <header class="text-center mb-4">
        <h3 class="mb-0 font-weight-bold">{{ strtoupper(config('app.name')) }}</h3>
        <p class="mb-0">Jl. Gadungan-Kepung, RT.04/RW.04, Sumber Bahagia, Kepung, Kec. Puncu, Kabupaten Kediri, Jawa Timur 64293</p>
        <hr style="border-top: 2px solid #000;">
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
