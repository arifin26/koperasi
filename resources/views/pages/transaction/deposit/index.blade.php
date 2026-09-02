@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <!-- Info Cards Ringkasan Status Bunga Simpanan -->
        <div class="row mb-3">
            <div class="col-12 col-md-6 col-lg-6">
                <div class="info-box bg-gradient-success shadow-sm mb-2">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text font-weight-bold">Bunga Simpanan Terposting ({{ now()->isoFormat('MMMM Y') }})</span>
                        <span class="info-box-number" style="font-size: 1.35rem;">
                            Rp {{ number_format($bungaSimpananTerposting ?? 0, 0, ',', '.') }}
                        </span>
                        <div class="progress" style="height: 3px;">
                            <div class="progress-bar bg-white" style="width: 100%"></div>
                        </div>
                        <span class="progress-description text-white-50 small">
                            <i class="fas fa-receipt mr-1"></i> {{ $bungaSimpananTerpostingCount ?? 0 }} transaksi bunga telah masuk ke rekening
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-6">
                <div class="info-box bg-gradient-warning shadow-sm mb-2">
                    <span class="info-box-icon text-white"><i class="fas fa-hourglass-half"></i></span>
                    <div class="info-box-content text-white">
                        <span class="info-box-text font-weight-bold text-white">Bunga Simpanan Menunggu Posting</span>
                        <span class="info-box-number text-white" style="font-size: 1.35rem;">
                            Rp {{ number_format($bungaSimpananBelumDiposting ?? 0, 0, ',', '.') }}
                        </span>
                        <div class="progress" style="height: 3px;">
                            <div class="progress-bar bg-white" style="width: 100%"></div>
                        </div>
                        <span class="progress-description text-white small">
                            <i class="fas fa-calendar-alt mr-1"></i> {{ $bungaSimpananBelumDipostingCount ?? 0 }} nasabah &bull; Jadwal posting: Tgl 1 Bulan Berikutnya
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-12 col-md-5 mb-2 mb-md-0">
                                <a href="{{ route('transaction.deposit.create') }}" class="btn btn-success">Baru</a>
                                <button class="btn btn-outline-success" data-toggle="modal"
                                    data-target="#print">Cetak</button>
                                @if(auth()->user()->role == 'manager')
                                <button class="btn btn-warning" data-toggle="modal" data-target="#updateBungaModal">
                                    <i class="fas fa-coins"></i> Update Bunga
                                </button>
                                @endif
                            </div>
                            <div class="col-12 col-md-7 row">
                                <div class="col-12 col-md-3">
                                    <select class="form-control" name="customer">
                                        <option value="">Semua Nasabah</option>
                                        @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->number . ' - ' . $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <select class="form-control" name="type">
                                        <option value="">Semua Jenis</option>
                                        @foreach ($types as $key => $val)
                                        <option value="{{ $key }}">{{ $val }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <input type="date" class="form-control date-filter" name="time_from" placeholder="Sejak">
                                </div>
                                <div class="col-12 col-md-3">
                                    <input type="date" class="form-control date-filter" name="time_to" placeholder="Hingga">
                                </div>
                            </div>
                        </div>
                        <table id="datatable-bs" class="table table-bordered table-hover">
                            <thead>
                                <tr class="text-center">
                                    <th style="width: 60px">#</th>
                                    <th style="width: 70px;">Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Nasabah</th>
                                    <th>Masuk</th>
                                    <th>Keluar</th>
                                    <th>Saldo</th>
                                    <th style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /.col-md-6 -->
        </div>
        <!-- /.row -->
    </div><!-- /.container-fluid -->
    <!-- /.content -->

    <!-- Modal -->
    <div class="modal fade" id="print" tabindex="-1" aria-labelledby="printLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('transaction.deposit.print') }}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="printLabel">Cetak Laporan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label>Nasabah</label>
                                    <select class="form-control" name="customer_id">
                                        @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->number . ' - ' . $customer->name . ' - ' . $customer->nik }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        Tekan tombol Cetak untuk mengunduh laporan dalam bentuk PDF.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-success">Cetak</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Update Bunga -->
    <div class="modal fade" id="updateBungaModal" tabindex="-1" role="dialog" aria-labelledby="updateBungaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="formUpdateBunga">
                    @csrf
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title font-weight-bold" id="updateBungaModalLabel"><i class="fas fa-coins mr-1"></i> Update Bunga Simpanan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i> Apakah Anda yakin ingin melakukan update bunga simpanan?
                        </div>
                        <div class="form-group">
                            <label for="process_date">Tanggal Proses Bunga:</label>
                            <input type="date" class="form-control" id="process_date" name="process_date" value="{{ date('Y-m-d') }}" required>
                            <small class="form-text text-muted">Sistem akan menghitung bunga harian (hari kerja) dan memposting transaksi bunga s.d. tanggal ini.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning font-weight-bold" id="btnSubmitUpdateBunga">
                            <i class="fas fa-play mr-1"></i> Jalankan Proses
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Summary Hasil -->
    <div class="modal fade" id="summaryBungaModal" tabindex="-1" role="dialog" aria-labelledby="summaryBungaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold" id="summaryBungaModalLabel"><i class="fas fa-check-circle mr-1"></i> Hasil Update Bunga Simpanan</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="font-weight-bold text-success" id="summaryMessage"></p>
                    <table class="table table-sm table-bordered">
                        <tr>
                            <td class="font-weight-bold">Tanggal Proses:</td>
                            <td id="resProcessDate">-</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Total Rekening/Nasabah Diproses:</td>
                            <td id="resTotalCustomers">0</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Berhasil Diposting:</td>
                            <td class="text-success font-weight-bold" id="resPostedCount">0</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Dilewati / Libur:</td>
                            <td id="resSkipped">0</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Duplikat / Sudah Diproses:</td>
                            <td id="resDuplicate">0</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Error:</td>
                            <td id="resErrors">0</td>
                        </tr>
                        <tr class="table-success">
                            <td class="font-weight-bold">Total Nominal Bunga:</td>
                            <td class="font-weight-bold text-success" id="resTotalInterest">Rp 0</td>
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
        $(function() {
            let customerId = null;
            let timeFrom = null;
            let timeTo = null;
            let type = null;

            //Initialize Datatables Elements
            const dtTable = $('#datatable-bs').DataTable({
                ajax: {
                    url: "{!! url()->current() !!}",
                    data: function (d) {
                        d.customer = customerId;
                        d.type = type;
                        d.from = timeFrom;
                        d.to = timeTo;
                    }
                },
                autoWidth: false,
                responsive: true,
                processing: true,
                serverSide: true,
                lengthChange: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
                },
                columns: [{
                        data: 'id',
                        name: 'id',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'type',
                        name: 'type',
                    },
                    {
                        data: 'customer',
                        name: 'customer',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'masuk',
                        name: 'masuk'
                    },
                    {
                        data: 'keluar',
                        name: 'keluar'
                    },
                    {
                        data: 'current_balance',
                        name: 'current_balance'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            $('select[name=type]').on('change', function() {
                type = $(this).val();
                dtTable.draw();
            });

            $('select[name=customer]').on('change', function() {
                customerId = $(this).val();
                dtTable.draw();
            });

            $('.date-filter').on('change', function() {
                const filterName = $(this).attr('name');
                const filterValue = $(this).val();

                if (filterName == 'time_from') {
                    timeFrom = filterValue;
                } else {
                    timeTo = filterValue;
                }

                dtTable.draw();
            });

            $('#formUpdateBunga').on('submit', function(e) {
                e.preventDefault();
                const btn = $('#btnSubmitUpdateBunga');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...');

                $.ajax({
                    url: "{{ route('transaction.deposit.update-bunga') }}",
                    type: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#updateBungaModal').modal('hide');
                        btn.prop('disabled', false).html('<i class="fas fa-play mr-1"></i> Jalankan Proses');

                        if (response.success) {
                            $('#summaryMessage').text(response.message);
                            $('#resProcessDate').text(response.process_date);
                            $('#resTotalCustomers').text(response.total_customers);
                            $('#resPostedCount').text(response.posted_count);
                            $('#resSkipped').text(response.skipped_days);
                            $('#resDuplicate').text(response.duplicate);
                            $('#resErrors').text(response.errors);
                            $('#resTotalInterest').text('Rp ' + new Intl.NumberFormat('id-ID').format(response.total_interest));

                            $('#summaryBungaModal').modal('show');
                            dtTable.draw();
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

            $(document).on('click', '.btn-validate', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                const title = $(this).data('title') || 'transaksi ini';

                Swal.fire({
                    title: 'Validasi Transaksi',
                    text: `Apakah Anda yakin ingin memvalidasi ${title}? Transaksi yang telah divalidasi akan mencantumkan baris validasi pada cetak kwitansi.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-check"></i> Ya, Validasi',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                Swal.fire({
                                    title: 'Berhasil Divalidasi!',
                                    text: res.message || 'Transaksi berhasil divalidasi.',
                                    icon: 'success',
                                    showCancelButton: true,
                                    confirmButtonColor: '#28a745',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: '<i class="fas fa-print"></i> Cetak Validasi Sekarang',
                                    cancelButtonText: 'Tutup'
                                }).then((printResult) => {
                                    if (printResult.isConfirmed && res.validation_print_url) {
                                        window.open(res.validation_print_url, '_blank');
                                    }
                                });
                                dtTable.draw(false);
                            },
                            error: function(xhr) {
                                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan saat memvalidasi.';
                                Swal.fire('Gagal!', msg, 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
