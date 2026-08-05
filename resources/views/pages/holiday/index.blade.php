@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Manajemen Hari Libur</h3>
                @if(auth()->user()->role == 'manager')
                <a href="{{ route('holiday.create') }}" class="btn btn-primary ml-auto">Tambah Hari Libur</a>
                @endif
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Filter Tahun:</label>
                        <select class="form-control" id="filter_year">
                            <option value="">Semua Tahun</option>
                            @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                                <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <table class="table table-bordered table-striped" id="holiday-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Nama Libur</th>
                            <th>Tipe</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
    <!-- Datatable -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.23/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.bootstrap4.min.css">
@endpush

@push('script')
    <!--Datatable-->
    <script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.23/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/responsive.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#holiday-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('holiday.index') }}",
            data: function (d) {
                d.year = $('#filter_year').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'date', name: 'date'},
            {data: 'name', name: 'name'},
            {data: 'type', name: 'type'},
            {data: 'description', name: 'description'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    $('#filter_year').change(function(){
        table.draw();
    });

    $('body').on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let form = $(this).closest('form');
        if (confirm('Yakin ingin menghapus hari libur ini?')) {
            form.submit();
        }
    });
});
</script>
@endpush
