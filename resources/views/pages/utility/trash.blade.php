@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pemindahan & Pengembalian Data Tak Terpakai</h3>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="trashTabs">
                    <li class="nav-item">
                        <a class="nav-link {{ $module == 'customers' ? 'active' : '' }}" href="{{ route('trash.index', ['module' => 'customers']) }}">
                            <i class="fas fa-users"></i> Nasabah
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $module == 'deposits' ? 'active' : '' }}" href="{{ route('trash.index', ['module' => 'deposits']) }}">
                            <i class="fas fa-piggy-bank"></i> Simpanan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $module == 'fixed_deposits' ? 'active' : '' }}" href="{{ route('trash.index', ['module' => 'fixed_deposits']) }}">
                            <i class="fas fa-landmark"></i> Deposito
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $module == 'users' ? 'active' : '' }}" href="{{ route('trash.index', ['module' => 'users']) }}">
                            <i class="fas fa-briefcase"></i> Karyawan
                        </a>
                    </li>
                </ul>

                <div class="tab-content mt-3">
                    @if($module == 'customers')
                    <table class="table table-bordered table-striped" id="trash-table">
                        <thead>
                            <tr><th>No</th><th>Nama</th><th>NIK</th><th>No. Rekening</th><th>Dihapus Pada</th><th>Aksi</th></tr>
                        </thead>
                    </table>
                    @elseif($module == 'deposits')
                    <table class="table table-bordered table-striped" id="trash-table">
                        <thead>
                            <tr><th>No</th><th>Nasabah</th><th>Jenis</th><th>Nominal</th><th>Dihapus Pada</th><th>Aksi</th></tr>
                        </thead>
                    </table>
                    @elseif($module == 'fixed_deposits')
                    <table class="table table-bordered table-striped" id="trash-table">
                        <thead>
                            <tr><th>No</th><th>No. Deposito</th><th>Nasabah</th><th>Nominal</th><th>Dihapus Pada</th><th>Aksi</th></tr>
                        </thead>
                    </table>
                    @elseif($module == 'users')
                    <table class="table table-bordered table-striped" id="trash-table">
                        <thead>
                            <tr><th>No</th><th>Nama</th><th>Username</th><th>Role</th><th>Dihapus Pada</th><th>Aksi</th></tr>
                        </thead>
                    </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    var module = '{{ $module }}';
    var columns;

    switch(module) {
        case 'customers':
            columns = [
                {data: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'nik', name: 'nik'},
                {data: 'number', name: 'number'},
                {data: 'deleted_at', name: 'deleted_at'},
                {data: 'action', orderable: false, searchable: false}
            ];
            break;
        case 'deposits':
            columns = [
                {data: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'customer', name: 'customer.name'},
                {data: 'type', name: 'type'},
                {data: 'amount', name: 'amount'},
                {data: 'deleted_at', name: 'deleted_at'},
                {data: 'action', orderable: false, searchable: false}
            ];
            break;
        case 'fixed_deposits':
            columns = [
                {data: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'number', name: 'number'},
                {data: 'customer', name: 'customer.name'},
                {data: 'amount', name: 'amount'},
                {data: 'deleted_at', name: 'deleted_at'},
                {data: 'action', orderable: false, searchable: false}
            ];
            break;
        case 'users':
            columns = [
                {data: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'username', name: 'username'},
                {data: 'role', name: 'role'},
                {data: 'deleted_at', name: 'deleted_at'},
                {data: 'action', orderable: false, searchable: false}
            ];
            break;
    }

    $('#trash-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('trash.index') }}",
            data: { module: module }
        },
        columns: columns
    });
});
</script>
@endsection
