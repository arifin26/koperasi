@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">

        {{-- Summary Cards --}}
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="info-box bg-primary">
                    <span class="info-box-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Dana Deposito</span>
                        <span class="info-box-number" style="font-size:1.1rem;">Rp {{ number_format($totalDeposito, 0, ',', '.') }}</span>
                        <span class="progress-description">seluruh deposito</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Deposito Aktif</span>
                        <span class="info-box-number">{{ number_format($totalAktif, 0, ',', '.') }}</span>
                        <span class="progress-description">Rp {{ number_format($nominalAktif, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Jatuh Tempo</span>
                        <span class="info-box-number">{{ number_format($totalJatuhTempo, 0, ',', '.') }}</span>
                        <span class="progress-description">menunggu keputusan</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-secondary">
                    <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Dicairkan</span>
                        <span class="info-box-number">{{ number_format($totalDicairkan, 0, ',', '.') }}</span>
                        <span class="progress-description">sudah dicairkan</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-landmark mr-2"></i>Rekap Deposito</h3>
                <small class="text-muted">Data per: {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y HH:mm') }}</small>
            </div>
            <div class="card-body">
                {{-- Filter & Action --}}
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Filter Status Deposito:</label>
                        <select class="form-control" id="filter_status">
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="matured">Jatuh Tempo</option>
                            <option value="extended">Diperpanjang</option>
                            <option value="liquidated">Dicairkan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Tanggal Per:</label>
                        <input type="date" class="form-control" id="filter_tanggal" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" class="btn btn-primary mr-1" id="btn_filter">
                            <i class="fas fa-filter"></i> Terapkan Filter
                        </button>
                        <button type="button" class="btn btn-outline-secondary mr-2" id="btn_reset">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        <form action="{{ route('report.deposit-recap.print') }}" method="POST" target="_blank" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" id="print_status" value="">
                            <input type="hidden" name="tanggal" id="print_tanggal" value="">
                            <button type="submit" class="btn btn-secondary"><i class="fas fa-print"></i> Cetak PDF</button>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    {{-- Legenda warna --}}
                    <div class="mb-2 d-flex align-items-center" style="gap:18px;">
                        <span><span class="legend-dot" style="background:#ffb3b3;"></span> Sudah jatuh tempo</span>
                        <span><span class="legend-dot" style="background:#84d9a4;"></span> Mendekati jatuh tempo (≤ 30 hari)</span>
                    </div>
                    <table class="table table-bordered table-striped table-hover" id="deposit-recap-table">
                        <thead class="thead-dark">
                            <tr>
                                <th width="40">No</th>
                                <th>No. Deposito</th>
                                <th>No. Nasabah</th>
                                <th>Nama Nasabah</th>
                                <th>Nominal</th>
                                <th>Rate</th>
                                <th>Bunga/Bulan</th>
                                <th>Tgl. Mulai</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th width="80">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.23/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.bootstrap4.min.css">
    <style>
        #deposit-recap-table td { vertical-align: middle; }
        .info-box-number { font-size: 1.3rem; }
        /* Warna baris berdasarkan jatuh tempo */
        #deposit-recap-table tbody tr.row-overdue td  { background-color: #ffe5e5 !important; }
        #deposit-recap-table tbody tr.row-soon    td  { background-color: #e6f9ee !important; }
        #deposit-recap-table tbody tr.row-overdue:hover td { background-color: #ffc9c9 !important; }
        #deposit-recap-table tbody tr.row-soon:hover    td { background-color: #c3f0d4 !important; }
        .legend-dot { display:inline-block; width:14px; height:14px; border-radius:3px; margin-right:5px; vertical-align:middle; }
    </style>
@endpush

@push('script')
    <script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.23/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/responsive.bootstrap4.min.js"></script>

    <script>
    $(document).ready(function () {
        var activeFilter = {
            status: '',
            tanggal: ''
        };

        var table = $('#deposit-recap-table').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            responsive: true,
            ajax: {
                url: "{{ route('report.deposit-recap') }}",
                data: function (d) {
                    d.status = activeFilter.status;
                    d.tanggal = activeFilter.tanggal;
                }
            },
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
            },
            columns: [
                { data: 'DT_RowIndex',          name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'no_deposito',           name: 'number' },
                { data: 'no_nasabah',            name: 'customer.number', searchable: false, orderable: false },
                { data: 'nama_nasabah',          name: 'customer.name', orderable: false },
                { data: 'amount',                name: 'amount' },
                { data: 'rate_percent',          name: 'rate_percent' },
                { data: 'bunga_bulanan',         name: 'bunga_bulanan', orderable: false, searchable: false },
                { data: 'start_date',            name: 'start_date' },
                { data: 'maturity_date',         name: 'maturity_date' },
                { data: 'status_label',          name: 'status', orderable: false },
                { data: 'aksi',                  name: 'aksi', orderable: false, searchable: false },
                // Kolom helper (tidak ditampilkan)
                { data: 'days_until_maturity',   name: 'days_until_maturity', visible: false, searchable: false, orderable: false },
            ],
            order: [[7, 'asc']],
            createdRow: function (row, data) {
                var days  = parseInt(data.days_until_maturity);
                var status = data.status_label; // misal mengandung kata 'liquidated'

                // Hanya beri warna untuk deposito yang belum dicairkan / diperpanjang
                if (status && (status.indexOf('Dicairkan') !== -1 || status.indexOf('Diperpanjang') !== -1)) {
                    return;
                }

                if (days < 0) {
                    // Sudah melewati jatuh tempo → merah
                    $(row).addClass('row-overdue');
                } else if (days <= 30) {
                    // Maksimal H-30 → hijau
                    $(row).addClass('row-soon');
                }
            }
        });

        $('#btn_filter').click(function () {
            activeFilter.status = $('#filter_status').val();
            activeFilter.tanggal = $('#filter_tanggal').val();

            $('#print_status').val(activeFilter.status);
            $('#print_tanggal').val(activeFilter.tanggal);

            table.draw();
        });

        $('#btn_reset').click(function () {
            $('#filter_status').val('');
            $('#filter_tanggal').val('');

            activeFilter.status = '';
            activeFilter.tanggal = '';

            $('#print_status').val('');
            $('#print_tanggal').val('');

            table.draw();
        });
    });
    </script>
@endpush
