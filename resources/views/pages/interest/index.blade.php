@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline card-outline-tabs">
            <div class="card-header p-0 border-bottom-0">
                <div class="d-flex justify-content-between align-items-center pr-3">
                    <ul class="nav nav-tabs" id="interest-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active font-weight-bold" id="tab-savings" data-toggle="pill" href="#content-savings" role="tab" aria-controls="content-savings" aria-selected="true">
                                <i class="fas fa-wallet mr-1 text-success"></i> Bunga Simpanan
                                <span class="badge badge-success ml-1">{{ $savingsCount ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="tab-deposits" data-toggle="pill" href="#content-deposits" role="tab" aria-controls="content-deposits" aria-selected="false">
                                <i class="fas fa-piggy-bank mr-1 text-info"></i> Bunga Deposito
                                <span class="badge badge-info ml-1">{{ $depositsCount ?? 0 }}</span>
                            </a>
                        </li>
                    </ul>
                    @if(auth()->user()->role == 'manager')
                    <div>
                        <a href="{{ route('interest.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> Tambah Bunga Baru
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="tab-content" id="interest-tabContent">
                    <!-- Tab Simpanan -->
                    <div class="tab-pane fade show active" id="content-savings" role="tabpanel" aria-labelledby="tab-savings">
                        <div class="alert alert-light border mb-3">
                            <i class="fas fa-info-circle text-primary mr-1"></i>
                            Daftar suku bunga simpanan yang tersedia di database. Rate dengan label <span class="badge badge-primary"><i class="fas fa-check"></i> Default</span> digunakan sebagai rate standar jika nasabah tidak memiliki rate khusus.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover" id="savings-table" style="width: 100%;">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Rate (% p.a.)</th>
                                        <th>Tanggal Mulai Berlaku</th>
                                        <th>Keterangan Tambahan / SK</th>
                                        <th>Status</th>
                                        <th style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Deposito -->
                    <div class="tab-pane fade" id="content-deposits" role="tabpanel" aria-labelledby="tab-deposits">
                        <div class="alert alert-light border mb-3">
                            <i class="fas fa-info-circle text-info mr-1"></i>
                            Daftar suku bunga deposito yang tersedia di database. Teller/user dapat memilih rate ini saat membuka rekening deposito baru.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover" id="deposits-table" style="width: 100%;">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Rate (% p.a.)</th>
                                        <th>Tanggal Mulai Berlaku</th>
                                        <th>Keterangan Tambahan / SK</th>
                                        <th>Status</th>
                                        <th style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.23/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.bootstrap4.min.css">
@endpush

@push('script')
    <script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.23/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/responsive.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    var savingsTable = $('#savings-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('interest.index') }}?type=simpanan",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'rate_percent', name: 'rate_percent'},
            {data: 'effective_date', name: 'effective_date'},
            {data: 'notes', name: 'notes'},
            {data: 'is_active', name: 'is_active'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    var depositsTable = $('#deposits-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('interest.index') }}?type=deposito",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'rate_percent', name: 'rate_percent'},
            {data: 'effective_date', name: 'effective_date'},
            {data: 'notes', name: 'notes'},
            {data: 'is_active', name: 'is_active'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
    });
});
</script>
@endpush
