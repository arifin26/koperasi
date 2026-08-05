@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Manajemen Rate Bunga (Aktif)</h3>
                <div class="ml-auto">
                    <a href="{{ route('interest.history') }}" class="btn btn-secondary mr-2">Lihat Riwayat Perubahan</a>
                    @if(auth()->user()->role == 'manager')
                    <a href="{{ route('interest.create') }}" class="btn btn-primary">Tambah Rate Baru</a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Ini adalah rate bunga yang <strong>sedang aktif</strong> dan digunakan oleh sistem untuk menghitung bunga harian.
                </div>
                <table class="table table-bordered table-striped" id="interest-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Jenis Simpanan/Deposito</th>
                            <th>Rate (% p.a.)</th>
                            <th>Berlaku Sejak</th>
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
    $('#interest-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('interest.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'type', name: 'type'},
            {data: 'rate_percent', name: 'rate_percent'},
            {data: 'effective_date', name: 'effective_date'}
        ]
    });
});
</script>
@endsection
