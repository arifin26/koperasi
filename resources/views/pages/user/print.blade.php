@extends('layouts.pdf')



@section('content')
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th scope="col">No</th>
            <th scope="col">Nama</th>
            <th scope="col">JK</th>
            <th scope="col">Tgl Lahir</th>
            <th scope="col">Pendidikan</th>
            <th scope="col">Alamat</th>
            <th scope="col">No Telepon</th>
            <th scope="col">Mulai Bekerja</th>
            <th scope="col">Divisi</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $item)
            <tr>
                <th scope="row">{{ $loop->iteration }}</th>
                <td>{{ $item->name }}</td>
                <td>{{ $item->gender }}</td>
                <td>{{ \Carbon\Carbon::parse($item->birth)->isoFormat('DD-MM-Y') }}</td>
                <td>{{ $item->last_education }}</td>
                <td>{{ $item->address }}</td>
                <td>{{ $item->phone }}</td>
                <td>{{ \Carbon\Carbon::parse($item->joined_at)->isoFormat('DD-MM-Y') }}</td>
                <td>{{ ucfirst($item->role) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
