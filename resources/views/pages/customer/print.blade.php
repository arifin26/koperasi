@extends('layouts.pdf')



@section('content')
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th scope="col">No</th>
            <th scope="col">Tgl Daftar</th>
            <th scope="col">NIK</th>
            <th scope="col">No Rek</th>
            <th scope="col">Nama</th>
            <th scope="col">Tgl Lahir</th>
            <th scope="col">Alamat</th>
            <th scope="col">No Telepon</th>
            <th scope="col">Pendidikan</th>
            <th scope="col">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $item)
            <tr>
                <th scope="row">{{ $loop->iteration }}</th>
                <td>{{ \Carbon\Carbon::parse($item->joined_at)->isoFormat('DD-MM-Y') }}</td>
                <td>{{ $item->nik }}</td>
                <td>{{ $item->number }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ \Carbon\Carbon::parse($item->birth)->isoFormat('DD-MM-Y') }}</td>
                <td>{{ $item->address }}</td>
                <td>{{ $item->phone }}</td>
                <td>{{ $item->last_education }}</td>
                <td>{{ ucfirst($item->status) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
