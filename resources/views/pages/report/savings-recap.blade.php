@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">

        {{-- Screen-only UI --}}
        <div class="savings-recap-screen-only">

        {{-- Summary Cards --}}
        <div class="row mb-3">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-piggy-bank"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Dana Simpanan</span>
                        <span class="info-box-number">Rp {{ number_format($totalSaldo, 0, ',', '.') }}</span>
                        <span class="progress-description">dari seluruh nasabah</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-coins text-white"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-white">Dana Simpanan</span>
                        <span class="info-box-number text-white" id="card_dana_simpanan">Rp {{ number_format($totalSaldo, 0, ',', '.') }}</span>
                        <span class="progress-description text-white">hasil filter aktif</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box bg-primary">
                    <span class="info-box-icon"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Nasabah</span>
                        <span class="info-box-number">{{ number_format($totalNasabah, 0, ',', '.') }}</span>
                        <span class="progress-description">terdaftar di sistem</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
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
                    <div class="col-md-2">
                        <label>Status Nasabah:</label>
                        <select class="form-control" id="filter_status">
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="blacklist">Blacklist</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Tanggal Per:</label>
                        <input type="date" class="form-control" id="filter_tanggal" max="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-7 d-flex align-items-end">
                        <button type="button" class="btn btn-primary mr-1" id="btn_filter">
                            <i class="fas fa-filter"></i> Terapkan Filter
                        </button>
                        <button type="button" class="btn btn-outline-secondary mr-2" id="btn_reset">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        <button type="button" class="btn btn-secondary" id="btn_print_savings_recap">
                            <i class="fas fa-print"></i> Cetak
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="savings-recap-table">
                        <thead class="thead-dark">
                            <tr>
                                <th width="30">No</th>
                                <th>No. Nasabah</th>
                                <th>NIK</th>
                                <th>Nama Nasabah</th>
                                <th>Alamat</th>
                                <th>No. Telepon</th>
                                <th>Bunga (%)</th>
                                <th>Status</th>
                                <th>Jml. Transaksi</th>
                                <th>Saldo Simpanan</th>
                                <th>Bunga/Bulan</th>
                                <th width="60">Aksi</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-light font-weight-bold">
                                <td colspan="9" class="text-right">Total Dana Simpanan (Hasil Filter):</td>
                                <td class="text-success" colspan="3" id="footer_total_saldo">Rp {{ number_format($totalSaldo, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Detail Transaction Card --}}
        <div class="card mt-4" id="detail-card" style="display:none">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-list mr-2"></i>Detail Transaksi Simpanan
                </h3>
                <small class="text-muted" id="detail-card-subtitle"></small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="savings-detail-table">
                        <thead class="thead-dark">
                            <tr>
                                <th width="40">No</th>
                                <th>Tanggal</th>
                                <th>Nama Nasabah</th>
                                <th>Jenis Transaksi</th>
                                <th>Jumlah</th>
                                <th>Saldo Berjalan</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        </div>{{-- End savings-recap-screen-only --}}

        {{-- Hidden Print Area --}}
        <div id="savings-recap-print-area" aria-live="polite" aria-busy="false"></div>

    </div>
</div>
@endsection

@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.23/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.bootstrap4.min.css">
    <style>
        #savings-recap-table td { vertical-align: middle; }
        .info-box-number { font-size: 1.3rem; }

        /* Print Area - Hidden by default */
        #savings-recap-print-area {
            display: none;
        }

        /* Print-specific styles */
        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            /* Hide everything except print area */
            .main-header,
            .main-sidebar,
            .content-header,
            .main-footer,
            .savings-recap-screen-only {
                display: none !important;
            }

            .content-wrapper,
            .content {
                margin: 0 !important;
                min-height: 0 !important;
                padding: 0 !important;
            }

            /* Show only print area */
            #savings-recap-print-area {
                display: block !important;
                width: 100%;
            }

            #savings-recap-print-area,
            #savings-recap-print-area * {
                visibility: visible;
                color: #000 !important;
            }

            /* Print document styles */
            .savings-recap-print-document {
                color: #000;
                font-size: 8pt;
                line-height: 1.25;
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                padding: 15mm 15mm 15mm 25mm;
            }

            /* Meta table */
            .savings-recap-print-meta {
                width: 100%;
                margin-bottom: 8px;
                font-size: 7.5pt;
                border-collapse: collapse;
            }

            .savings-recap-print-meta td {
                padding: 1px 3px;
                vertical-align: top;
                border: none;
                color: #000;
            }

            .meta-label {
                width: 12%;
                font-weight: bold;
                color: #000;
            }

            .meta-colon {
                width: 1%;
                text-align: center;
            }

            .meta-value {
                width: 37%;
            }

            /* Data table */
            .savings-recap-print-table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
                margin-top: 4px;
                font-size: 7.5pt;
            }

            .savings-recap-print-table th,
            .savings-recap-print-table td {
                border: 1px solid #000;
                padding: 4px 6px;
                vertical-align: middle;
                overflow-wrap: anywhere;
                color: #000;
            }

            .savings-recap-print-table th {
                background-color: #f2f2f2;
                color: #000;
                font-weight: bold;
                text-align: center;
                font-size: 7.5pt;
            }

            .savings-recap-print-table thead {
                display: table-header-group;
            }

            .savings-recap-print-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            /* Summary section */
            .savings-recap-print-summary {
                margin-top: 8px;
            }

            .summary-row td {
                font-weight: bold;
                background-color: #f2f2f2;
                color: #000;
                border: 1px solid #000;
                padding: 6px;
            }

            /* Signature section */
            .savings-recap-print-signature {
                width: 100%;
                margin-top: 20px;
                border-collapse: collapse;
                font-size: 7.5pt;
            }

            .savings-recap-print-signature td {
                border: none !important;
                color: #000;
            }

            .sign-space {
                height: 45px;
            }

            .sign-name {
                font-weight: bold;
                text-decoration: underline;
                font-size: 8pt;
                color: #000;
            }

            .sign-title {
                font-size: 7pt;
                color: #000;
                margin-top: 2px;
            }

            /* Footer */
            .savings-recap-print-footer {
                margin-top: 15px;
                font-size: 6.5pt;
                color: #000;
                border-top: 0.5px dotted #000;
                padding-top: 2px;
            }

            /* Prevent breaking */
            .print-avoid-break {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            /* Utility classes */
            .text-right { text-align: right; }
            .text-center { text-align: center; }

            /* Force color printing */
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
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
        // State filter aktif yang diterapkan
        var today = '{{ date('Y-m-d') }}';
        var activeFilter = {
            status: '',
            tanggal: today
        };

        var table = $('#savings-recap-table').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            responsive: true,
            ajax: {
                url: "{{ route('report.savings-recap') }}",
                data: function (d) {
                    d.status = activeFilter.status;
                    d.tanggal = activeFilter.tanggal;
                }
            },
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'no_nasabah',  name: 'number' },
                { data: 'nik',         name: 'nik' },
                { data: 'nama_nasabah', name: 'name' },
                { data: 'alamat',      name: 'address' },
                { data: 'phone',       name: 'phone' },
                { data: 'rate_bunga',  name: 'interestRate.rate_percent', orderable: false, searchable: false },
                { data: 'status_nasabah', name: 'status', orderable: false },
                { data: 'total_transaksi', name: 'total_transaksi', searchable: false, orderable: false },
                { data: 'saldo_simpanan', name: 'saldo_simpanan', orderable: false, searchable: false },
                { data: 'bunga_bulanan', name: 'bunga_bulanan', orderable: false, searchable: false },
                { data: 'aksi', name: 'aksi', orderable: false, searchable: false },
            ],
            order: [[3, 'asc']],
            createdRow: function (row, data) {
                // Highlight baris nasabah dengan saldo 0
                if (parseInt(data.saldo_raw) === 0) {
                    $(row).addClass('table-warning');
                }
            }
        });

        // Update card dan footer saat data diterima dari server
        table.on('xhr', function () {
            var json = table.ajax.json();
            if (json && json.filtered_total_saldo_formatted !== undefined) {
                $('#card_dana_simpanan').text('Rp ' + json.filtered_total_saldo_formatted);
                $('#footer_total_saldo').text('Rp ' + json.filtered_total_saldo_formatted);
            }
        });

        // Detail table management
        var detailTable = null;

        function initDetailTable() {
            if (detailTable) {
                detailTable.destroy();
            }
            detailTable = $('#savings-detail-table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: true,
                ajax: {
                    url: "{{ route('report.savings-recap') }}",
                    data: function (d) {
                        d.mode = 'detail';
                        d.status = activeFilter.status;
                        d.tanggal = activeFilter.tanggal;
                    }
                },
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'nama_nasabah', name: 'customer.name' },
                    { data: 'jenis_transaksi', name: 'jenis_transaksi', orderable: false, searchable: false },
                    { data: 'jumlah', name: 'amount' },
                    { data: 'saldo_berjalan', name: 'current_balance' },
                ],
                order: [[1, 'asc']],
                createdRow: function (row, data) {
                    if (data.type_raw === 'penarikan') {
                        $(row).addClass('table-danger');
                    }
                }
            });
        }

        // Eksekusi filter HANYA saat tombol "Terapkan Filter" diklik
        $('#btn_filter').click(function () {
            activeFilter.status = $('#filter_status').val();
            activeFilter.tanggal = $('#filter_tanggal').val();

            // Sinkronkan ke form Cetak PDF
            $('#print_status').val(activeFilter.status);
            $('#print_tanggal').val(activeFilter.tanggal);

            table.draw();

            // Show/hide detail card based on date filter
            if (activeFilter.tanggal) {
                $('#detail-card-subtitle').text('Per tanggal: ' + activeFilter.tanggal);
                $('#detail-card').show();
                initDetailTable();
            } else {
                $('#detail-card').hide();
                if (detailTable) {
                    detailTable.destroy();
                    detailTable = null;
                }
            }
        });

        // Reset filter
        $('#btn_reset').click(function () {
            $('#filter_status').val('');
            $('#filter_tanggal').val('');

            activeFilter.status = '';
            activeFilter.tanggal = '';

            $('#print_status').val('');
            $('#print_tanggal').val('');

            table.draw();

            // Hide detail card
            $('#detail-card').hide();
            if (detailTable) {
                detailTable.destroy();
                detailTable = null;
            }
        });

        // Native Print Button Handler
        $('#btn_print_savings_recap').click(function() {
            var btn = $(this);
            var originalHtml = btn.html();

            // Disable button and show loading
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyiapkan laporan...');
            $('#savings-recap-print-area').attr('aria-busy', 'true');

            // Send AJAX request with current active filters
            $.ajax({
                url: "{{ route('report.savings-recap.print') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    status: activeFilter.status,
                    tanggal: activeFilter.tanggal
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.html) {
                        // Insert HTML into print area
                        $('#savings-recap-print-area').html(response.data.html);

                        // Wait for logo image to load before printing
                        var logo = $('#savings-recap-print-area').find('img')[0];

                        if (logo) {
                            if (logo.complete) {
                                // Image already loaded
                                window.print();
                            } else {
                                // Wait for image to load
                                logo.onload = function() {
                                    window.print();
                                };
                                logo.onerror = function() {
                                    // Print anyway even if logo fails
                                    window.print();
                                };
                            }
                        } else {
                            // No logo found, print directly
                            window.print();
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Gagal memuat laporan cetak. Silakan coba lagi.'
                        });
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Gagal memuat laporan cetak.';

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        // Validation errors
                        var errors = xhr.responseJSON.errors;
                        var errorList = Object.values(errors).flat().join('<br>');
                        errorMsg = errorList;
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: errorMsg
                    });
                },
                complete: function() {
                    // Re-enable button
                    btn.prop('disabled', false).html(originalHtml);
                    $('#savings-recap-print-area').attr('aria-busy', 'false');
                }
            });
        });
    });
    </script>
@endpush
