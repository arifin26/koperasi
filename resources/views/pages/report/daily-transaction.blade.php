@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Laporan Transaksi Harian</h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Pilih Tanggal:</label>
                        <input type="date" class="form-control" id="filter_date" value="{{ $date }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <form action="{{ route('report.daily.print') }}" method="POST" target="_blank" class="d-inline">
                            @csrf
                            <input type="hidden" name="date" id="print_date" value="{{ $date }}">
                            <button type="submit" class="btn btn-secondary"><i class="fas fa-print"></i> Cetak PDF</button>
                        </form>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Masuk Hari Ini</span>
                                <span class="info-box-number">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Keluar Hari Ini</span>
                                <span class="info-box-number">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fas fa-balance-scale"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Selisih</span>
                                <span class="info-box-number">Rp {{ number_format($totalMasuk - $totalKeluar, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <table class="table table-bordered table-striped" id="daily-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Waktu</th>
                            <th>Nasabah</th>
                            <th>Jenis</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th width="90">Aksi</th>
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
    var table = $('#daily-table').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        responsive: true,
        ajax: {
            url: "{{ route('report.daily') }}",
            data: function(d) {
                d.date = $('#filter_date').val();
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error, thrown);
                alert('Terjadi kesalahan saat memuat data. Silakan refresh halaman.');
            }
        },
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json',
            emptyTable: 'Tidak ada transaksi pada tanggal ini',
            zeroRecords: 'Tidak ada transaksi yang ditemukan'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'created_at', name: 'created_at'},
            {data: 'customer', name: 'customer.name'},
            {data: 'type', name: 'type'},
            {data: 'masuk', name: 'masuk', orderable: false, searchable: false},
            {data: 'keluar', name: 'keluar', orderable: false, searchable: false}
        ],
        drawCallback: function(settings) {
            // Update summary cards via AJAX when table is drawn
            var api = this.api();
            var json = api.ajax.json();
            // Note: Summary cards still require page reload for full update
        }
    });

    $('#filter_date').change(function() {
        var selectedDate = $(this).val();
        $('#print_date').val(selectedDate);
        table.draw();
        // Reload page for summary cards update
        window.location.href = "{{ route('report.daily') }}?date=" + selectedDate;
    });
});
</script>
@endpush
