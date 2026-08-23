@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">

        {{-- Summary Cards --}}
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-piggy-bank"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Dana Simpanan</span>
                        <span class="info-box-number">Rp {{ number_format($totalSaldo, 0, ',', '.') }}</span>
                        <span class="progress-description">dari seluruh nasabah</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box bg-primary">
                    <span class="info-box-icon"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Nasabah</span>
                        <span class="info-box-number">{{ number_format($totalNasabah, 0, ',', '.') }}</span>
                        <span class="progress-description">terdaftar di sistem</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-user-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Nasabah Aktif</span>
                        <span class="info-box-number">{{ number_format($nasabahAktif, 0, ',', '.') }}</span>
                        <span class="progress-description">status aktif</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-piggy-bank mr-2"></i>Rekap Saldo Simpanan Nasabah</h3>
                <small class="text-muted">Data per: {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y HH:mm') }}</small>
            </div>
            <div class="card-body">
                {{-- Filter & Action --}}
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Filter Status Nasabah:</label>
                        <select class="form-control" id="filter_status">
                            <option value="">Semua</option>
                            <option value="active">Aktif</option>
                            <option value="blacklist">Blacklist</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <form action="{{ route('report.savings-recap.print') }}" method="POST" target="_blank" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" id="print_status" value="">
                            <button type="submit" class="btn btn-secondary"><i class="fas fa-print"></i> Cetak PDF</button>
                        </form>
                    </div>
                </div>

                <table class="table table-bordered table-striped table-hover" id="savings-recap-table">
                    <thead class="thead-dark">
                        <tr>
                            <th width="40">No</th>
                            <th>No. Nasabah</th>
                            <th>Nama Nasabah</th>
                            <th>Status</th>
                            <th>Jml. Transaksi</th>
                            <th>Saldo Simpanan</th>
                            <th width="80">Aksi</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-light font-weight-bold">
                            <td colspan="5" class="text-right">Total Dana Simpanan (Seluruh Nasabah):</td>
                            <td class="text-success">Rp {{ number_format($totalSaldo, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.23/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.bootstrap4.min.css">
    <style>
        #savings-recap-table td { vertical-align: middle; }
        .info-box-number { font-size: 1.3rem; }
    </style>
@endpush

@push('script')
    <script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.23/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/responsive.bootstrap4.min.js"></script>

    <script>
    $(document).ready(function () {
        var table = $('#savings-recap-table').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            responsive: true,
            ajax: {
                url: "{{ route('report.savings-recap') }}",
                data: function (d) {
                    d.status = $('#filter_status').val();
                }
            },
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'no_nasabah',  name: 'number' },
                { data: 'nama_nasabah', name: 'name' },
                { data: 'status_nasabah', name: 'status', orderable: false },
                { data: 'total_transaksi', name: 'deposits_count', searchable: false },
                { data: 'saldo_simpanan', name: 'saldo_simpanan', orderable: false, searchable: false },
                { data: 'aksi', name: 'aksi', orderable: false, searchable: false },
            ],
            order: [[2, 'asc']],
            createdRow: function (row, data) {
                // Highlight baris nasabah dengan saldo 0
                if (parseInt(data.saldo_raw) === 0) {
                    $(row).addClass('table-warning');
                }
            }
        });

        $('#filter_status').change(function () {
            $('#print_status').val($(this).val());
            table.draw();
        });
    });
    </script>
@endpush
