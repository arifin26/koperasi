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
            <th scope="col">No</th>
            <th scope="col">Tgl Simpan</th>
            <th scope="col">Setoran Simpanan</th>
            <th scope="col">Bunga</th>
            <th scope="col">Nominal Total</th>
        </tr>
    </thead>
    <tbody>
        @php($total = 0)
        @foreach ($data as $item)
            @php($total += $item->saldo)
            <tr>
                <th scope="row">{{ $loop->iteration }}</th>
                <td>{{ \Carbon\Carbon::parse($item->tanggal)->isoFormat('DD-MM-Y') }}</td>
                <td class="text-right">Rp{{ number_format($item->simpanan, 2, ',', '.') }}</td>
                <td class="text-right">Rp{{ number_format($item->bunga, 2, ',', '.') }}</td>
                <td class="text-right">Rp{{ number_format($item->saldo, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr>
            <th colspan="4">Total Nominal</th>
            <td class="text-right">Rp{{ number_format($total, 2, ',', '.') }}</td>
        </tr>
    </tbody>
</table>
@endsection
