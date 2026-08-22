@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Manajemen Deposito</h3>
                <div class="ml-auto">
                    @if(auth()->user()->role == 'manager')
                    <button type="button" class="btn btn-warning mr-2" data-toggle="modal" data-target="#updateDepositBungaModal">
                        <i class="fas fa-coins"></i> Update Bunga
                    </button>
                    @endif
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

<!-- Modal Update Bunga Deposito -->
<div class="modal fade" id="updateDepositBungaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formUpdateDepositBunga">
                @csrf
                <div class="modal-header bg-warning">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-coins mr-1"></i> Update Bunga Deposito</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i> Apakah Anda yakin ingin melakukan update bunga deposito?
                    </div>
                    <div class="form-group">
                        <label for="deposit_process_date">Tanggal Proses Bunga:</label>
                        <input type="date" class="form-control" id="deposit_process_date" name="process_date" value="{{ date('Y-m-d') }}" required>
                        <small class="form-text text-muted">Sistem akan memeriksa seluruh deposito aktif/jatuh tempo dan memproses transfer bunga sesuai periode bulan ini.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning font-weight-bold" id="btnSubmitDepositBunga">
                        <i class="fas fa-play mr-1"></i> Jalankan Proses
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Summary Hasil Deposito -->
<div class="modal fade" id="summaryDepositBungaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-check-circle mr-1"></i> Hasil Update Bunga Deposito</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="font-weight-bold text-success" id="summaryDepMessage"></p>
                <table class="table table-sm table-bordered">
                    <tr>
                        <td class="font-weight-bold">Tanggal Proses:</td>
                        <td id="resDepProcessDate">-</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Total Deposito Diperiksa:</td>
                        <td id="resDepTotalChecked">0</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Sudah Jatuh Tempo:</td>
                        <td id="resDepMatured">0</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Berhasil Diproses:</td>
                        <td class="text-success font-weight-bold" id="resDepProcessed">0</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Dilewati / Belum Waktunya:</td>
                        <td id="resDepSkipped">0</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Duplikat / Sudah Diperoleh:</td>
                        <td id="resDepDuplicate">0</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Error:</td>
                        <td id="resDepErrors">0</td>
                    </tr>
                    <tr class="table-success">
                        <td class="font-weight-bold">Total Nominal Bunga:</td>
                        <td class="font-weight-bold text-success" id="resDepTotalInterest">Rp 0</td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Tutup</button>
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

    $('#formUpdateDepositBunga').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSubmitDepositBunga');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...');

        $.ajax({
            url: "{{ route('fixed-deposit.update-bunga') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                $('#updateDepositBungaModal').modal('hide');
                btn.prop('disabled', false).html('<i class="fas fa-play mr-1"></i> Jalankan Proses');

                if (response.success) {
                    $('#summaryDepMessage').text(response.message);
                    $('#resDepProcessDate').text(response.process_date);
                    $('#resDepTotalChecked').text(response.total_checked);
                    $('#resDepMatured').text(response.matured_count);
                    $('#resDepProcessed').text(response.processed_count);
                    $('#resDepSkipped').text(response.skipped_count);
                    $('#resDepDuplicate').text(response.duplicate_count);
                    $('#resDepErrors').text(response.error_count);
                    $('#resDepTotalInterest').text('Rp ' + new Intl.NumberFormat('id-ID').format(response.total_interest));

                    $('#summaryDepositBungaModal').modal('show');
                    table.draw();
                } else {
                    alert('Gagal: ' + response.message);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-play mr-1"></i> Jalankan Proses');
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.';
                alert('Error: ' + msg);
            }
        });
    });
});
</script>
@endpush
