@extends('layouts.pdf')


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
