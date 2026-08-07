@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Manajemen Deposito</h3>
                <div class="ml-auto">
                    <button type="button" class="btn btn-secondary mr-2" data-toggle="modal" data-target="#printModal">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                    <a href="{{ route('fixed-deposit.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Buka Deposito Baru
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Filter Status:</label>
                        <select class="form-control" id="filter_status">
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="matured">Jatuh Tempo</option>
                            <option value="extended">Diperpanjang</option>
                            <option value="liquidated">Dicairkan</option>
                        </select>
                    </div>
                </div>
                <table class="table table-bordered table-striped" id="deposito-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Deposito</th>
                            <th>Nasabah</th>
                            <th>Nominal</th>
                            <th>Tenor</th>
                            <th>Rate</th>
                            <th>Mulai</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Print Modal -->
<div class="modal fade" id="printModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('fixed-deposit.print') }}" method="POST" target="_blank">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Cetak Laporan Deposito</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Filter Status:</label>
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="matured">Jatuh Tempo</option>
                            <option value="liquidated">Dicairkan</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-print"></i> Cetak PDF</button>
                </div>
            </form>
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
    var table = $('#deposito-table').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        responsive: true,
        ajax: {
            url: "{{ route('fixed-deposit.index') }}",
            data: function(d) {
                d.status = $('#filter_status').val();
            }
        },
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'number', name: 'number'},
            {data: 'customer', name: 'customer.name'},
            {data: 'amount', name: 'amount'},
            {data: 'tenor_months', name: 'tenor_months'},
            {data: 'rate_percent', name: 'rate_percent'},
            {data: 'start_date', name: 'start_date'},
            {data: 'maturity_date', name: 'maturity_date'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    $('#filter_status').change(function() { table.draw(); });
});
</script>
@endpush
