@extends('layouts.pdf')

@section('header')
@if(isset($customer))
<table style="width: 100%; margin-bottom: 15px; border: none;">
    <tr>
        <td style="width: 15%; font-weight: bold; border: none; padding: 2px;">No. Rekening</td>
        <td style="width: 35%; border: none; padding: 2px;">: {{ $customer->number ?? '-' }}</td>
        <td style="width: 15%; font-weight: bold; border: none; padding: 2px;">Alamat</td>
        <td style="width: 35%; border: none; padding: 2px;">: {{ $customer->address ?? '-' }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; border: none; padding: 2px;">Nama Nasabah</td>
        <td style="border: none; padding: 2px;">: {{ $customer->name ?? '-' }}</td>
        <td style="border: none; padding: 2px;"></td>
        <td style="border: none; padding: 2px;"></td>
    </tr>
</table>
@endif
@endsection

@section('content')
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th scope="col" style="width: 5%;">No</th>
            <th scope="col" style="width: 13%;">Tanggal</th>
            <th scope="col" style="width: 8%;">Sandi</th>
            <th scope="col" style="width: 19%;">Debet</th>
            <th scope="col" style="width: 19%;">Kredit</th>
            <th scope="col" style="width: 24%;">Saldo</th>
            <th scope="col" style="width: 12%;">Petugas</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($data as $item)
            @php
                $isDebit = ($item->type === 'penarikan');
                $notesLower = strtolower($item->notes ?? '');

                // Kode sandi transaksi buku tabungan:
                // 1: Setor (simpanan reguler/pokok/wajib/sukarela)
                // 2: Penarikan
                // 3: Bunga Deposito
                // 4: Bunga Simpanan
                if ($isDebit) {
                    $code = '2';
                } elseif ($item->type === 'bunga') {
                    $code = str_contains($notesLower, 'deposito') ? '3' : '4';
                } else {
                    $code = '1';
                }

                $teller = $item->creator->name ?? 'ADM';
                $tellerInitial = strtoupper(substr(trim($teller), 0, 3));
            @endphp
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/y') }}</td>
                <td class="text-center">{{ $code }}</td>
                <td class="text-right">{{ $isDebit ? number_format($item->amount, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ !$isDebit ? number_format($item->amount, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ number_format($item->current_balance, 0, ',', '.') }}</td>
                <td class="text-center">{{ $tellerInitial }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">Tidak ada transaksi</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <th colspan="5" class="text-right">Saldo Akhir</th>
            <th class="text-right">{{ number_format($lastBalance ?? ($data->last()->current_balance ?? 0), 0, ',', '.') }}</th>
            <th></th>
        </tr>
    </tfoot>
</table>
@endsection
