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
                    <div class="col-md-2">
                        <label>Filter Status Deposito:</label>
                        <select class="form-control" id="filter_status">
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="matured">Jatuh Tempo</option>
                            <option value="extended">Diperpanjang</option>
                            <option value="liquidated">Dicairkan</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Bulan:</label>
                        <select class="form-control" id="filter_bulan">
                            <option value="">Semua</option>
                            <option value="01">Januari</option>
                            <option value="02">Februari</option>
                            <option value="03">Maret</option>
                            <option value="04">April</option>
                            <option value="05">Mei</option>
                            <option value="06">Juni</option>
                            <option value="07">Juli</option>
                            <option value="08">Agustus</option>
                            <option value="09">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Tahun:</label>
                        <select class="form-control" id="filter_tahun">
                            <option value="">Semua</option>
                            @for($y = 2020; $y <= date('Y') + 1; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
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
                            <input type="hidden" name="bulan" id="print_bulan" value="">
                            <input type="hidden" name="tahun" id="print_tahun" value="">
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
                                <th>Bunga/Bulan</th>
                                <th>Rate</th>
                                <th>Tgl. Mulai</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th width="80">Aksi</th>
                                <th style="display:none;">Days</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="deposit-recap-summary-row">
                                <th colspan="4" class="text-right align-middle">
                                    <strong>Rekap Total (Hasil Filter)</strong>
                                </th>
                                <th class="text-right">
                                    <div class="deposit-recap-summary-label">Total Nominal</div>
                                    <div id="footer_total_nominal" class="deposit-recap-summary-value text-primary font-weight-bold">Rp -</div>
                                </th>
                                <th class="text-right">
                                    <div class="deposit-recap-summary-label">Total Bunga/Bulan</div>
                                    <div id="footer_total_bunga" class="deposit-recap-summary-value text-success font-weight-bold">Rp -</div>
                                </th>
                                <th colspan="5"></th>
                            </tr>
                        </tfoot>
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

        /* Footer styling */
        #deposit-recap-table tfoot th {
            background-color: #f8f9fa;
            border-top: 2px solid #343a40;
            padding: 12px 8px;
            font-size: 0.9rem;
        }

        .deposit-recap-summary-row {
            background-color: #f8f9fa !important;
        }

        .deposit-recap-summary-label {
            font-size: 0.75rem;
            color: #6c757d;
            margin-bottom: 2px;
        }

        .deposit-recap-summary-value {
            font-size: 1rem;
            font-weight: bold;
        }
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
            bulan: '',
            tahun: ''
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
                    d.bulan = activeFilter.bulan;
                    d.tahun = activeFilter.tahun;
                },
                error: function (xhr, error, thrown) {
                    console.warn('DataTables AJAX error:', error, thrown);
                }
            },
            language: {
                processing:     "Sedang memproses...",
                search:         "Cari:",
                lengthMenu:     "Tampilkan _MENU_ data",
                info:           "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
                infoEmpty:      "Menampilkan 0 s/d 0 dari 0 data",
                infoFiltered:   "(disaring dari _MAX_ total data)",
                infoPostFix:    "",
                loadingRecords: "Memuat...",
                zeroRecords:    "Tidak ditemukan data yang sesuai",
                emptyTable:     "Tidak ada data yang tersedia",
                paginate: {
                    first:    "Pertama",
                    previous: "Sebelumnya",
                    next:     "Berikutnya",
                    last:     "Terakhir"
                },
                aria: {
                    sortAscending:  ": aktifkan untuk mengurutkan kolom ke atas",
                    sortDescending: ": aktifkan untuk mengurutkan kolom ke bawah"
                }
            },
            columns: [
                { data: 'DT_RowIndex',          name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'no_deposito',           name: 'number' },
                { data: 'no_nasabah',            name: 'customer.number', searchable: false, orderable: false },
                { data: 'nama_nasabah',          name: 'customer.name', orderable: false },
                { data: 'amount',                name: 'amount', responsivePriority: 1 },
                { data: 'bunga_bulanan',         name: 'bunga_bulanan', orderable: false, searchable: false, responsivePriority: 2 },
                { data: 'rate_percent',          name: 'rate_percent' },
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

        // Function to update footer totals
        function updateDepositRecapFooter(json) {
            if (json && json.filtered_total_nominal_formatted !== undefined) {
                $('#footer_total_nominal').text('Rp ' + json.filtered_total_nominal_formatted);
                $('#footer_total_bunga').text('Rp ' + json.filtered_total_bunga_formatted);
            } else {
                // Fallback to zero if metadata not available
                $('#footer_total_nominal').text('Rp 0');
                $('#footer_total_bunga').text('Rp 0');
            }
        }

        // Update footer when data is received
        table.on('xhr', function () {
            var json = table.ajax.json();
            updateDepositRecapFooter(json);
        });

        $('#btn_filter').click(function () {
            activeFilter.status = $('#filter_status').val();
            activeFilter.bulan = $('#filter_bulan').val();
            activeFilter.tahun = $('#filter_tahun').val();

            $('#print_status').val(activeFilter.status);
            $('#print_bulan').val(activeFilter.bulan);
            $('#print_tahun').val(activeFilter.tahun);

            table.draw();
        });

        $('#btn_reset').click(function () {
            $('#filter_status').val('');
            $('#filter_bulan').val('');
            $('#filter_tahun').val('');

            activeFilter.status = '';
            activeFilter.bulan = '';
            activeFilter.tahun = '';

            $('#print_status').val('');
            $('#print_bulan').val('');
            $('#print_tahun').val('');

            table.draw();
        });
    });
    </script>
@endpush
