@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Riwayat Perubahan Rate Bunga</h3>
                <a href="{{ route('interest.index') }}" class="btn btn-secondary ml-auto">Kembali</a>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped" id="interest-history-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Jenis Simpanan/Deposito</th>
                            <th>Rate (% p.a.)</th>
                            <th>Tanggal Berlaku</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#interest-history-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('interest.history') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'type', name: 'type'},
            {data: 'rate_percent', name: 'rate_percent'},
            {data: 'effective_date', name: 'effective_date'},
            {data: 'is_active', name: 'is_active'},
            {data: 'notes', name: 'notes'}
        ]
    });
});
</script>
@endsection
