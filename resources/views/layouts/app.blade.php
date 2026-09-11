<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
    @stack('style')
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars"></i></a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown" data-toggle="tooltip" data-placement="top" title="Menu">
                    <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="left: inherit; right: 0px;">
                        <span class="dropdown-item">
                            <!-- Message Start -->
                            <div class="media">
                                <div class="image mr-3">
                                    <div class="img-circle"
                                        style="width: 50px; height: 50px; background-repeat: no-repeat;background-size: 50px; background-position: center; background-image: url({{ Auth::user()->photo ? asset('storage/' . Auth::user()->photo) : 'https://ui-avatars.com/api/?background=random&name=' . urlencode(Auth::user()->name) }}) ;">
                                    </div>
                                </div>
                                <div class="media-body">
                                    <h3 class="dropdown-item-title font-weight-bold">
                                        {{ Auth::user()->name }}
                                    </h3>
                                    <p class="text-sm">{{ '@' . Auth::user()->username }}</p>
                                    <p class="text-sm text-muted"><i class="fas fa-phone mr-1"></i>
                                        {{ Auth::user()->phone }}</p>
                                </div>
                            </div>
                            <!-- Message End -->
                        </span>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('profile.show') }}" class="dropdown-item">Pengaturan</a>
                        <a href="{{ route('profile.truncate') }}" class="dropdown-item" id="truncate-button">Reset Data</a>
                        <a href="{{ route('logout') }}" class="dropdown-item" id="logout-button">Keluar</a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                        <form id="truncate-form" action="{{ route('profile.truncate') }}" method="POST" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-light-success elevation-2">
            <!-- Brand Logo -->
            <a href="/" class="brand-link">
                {{-- <img src="{{ asset('swamitra.jpeg') }}" alt="{{ config('app.name') }}" class="brand-image"
                    style="opacity: .8"> --}}
                <span class="brand-text font-weight-light">KARYA <strong>DEV</strong></span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
                        <li class="nav-item">
                            <a href="{{ route('home') }}" class="nav-link {{ Route::is('home') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-columns"></i>
                                <p>
                                    Dashboard
                                </p>
                            </a>
                        </li>
                        <li class="nav-header">DATA MASTER</li>
                        @if(auth()->user()->role == 'manager')
                        <li class="nav-item">
                            <a href="{{ route('user.index') }}" class="nav-link {{ Route::is('user.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-briefcase"></i>
                                <p>
                                    Karyawan
                                </p>
                            </a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('customer.index') }}" class="nav-link {{ Route::is('customer.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    Nasabah
                                </p>
                            </a>
                        </li>
                        <li class="nav-header">DATA TRANSAKSI</li>
                        <li class="nav-item {{ Route::is('transaction.*') || Route::is('fixed-deposit.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Route::is('transaction.*') || Route::is('fixed-deposit.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                <p>
                                    Transaksi
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('transaction.deposit.index') }}" class="nav-link {{ Route::is('transaction.deposit.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Simpanan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('transaction.withdrawal.index') }}" class="nav-link {{ Route::is('transaction.withdrawal.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Penarikan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('fixed-deposit.index') }}" class="nav-link {{ Route::is('fixed-deposit.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Deposito</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <!-- Core Banking -->
                        <li class="nav-header">CORE BANKING</li>
                        <li class="nav-item">
                            <a href="{{ route('holiday.index') }}" class="nav-link {{ request()->is('holiday*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>Hari Libur</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('interest.index') }}" class="nav-link {{ request()->is('interest*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-percentage"></i>
                                <p>Manajemen Bunga</p>
                            </a>
                        </li>

                        <!-- Laporan -->
                        <li class="nav-header">LAPORAN</li>
                        <li class="nav-item {{ request()->is('laporan*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->is('laporan*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>
                                    Laporan
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('report.daily') }}" class="nav-link {{ request()->is('laporan/harian*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Laporan Harian</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('report.savings-recap') }}" class="nav-link {{ request()->is('laporan/rekap-simpanan*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Rekap Simpanan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('report.deposit-recap') }}" class="nav-link {{ request()->is('laporan/rekap-deposito*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Rekap Deposito</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Utility -->
                        @if(auth()->user()->role == 'manager')
                        <li class="nav-header">UTILITY</li>
                        <li class="nav-item">
                            <a href="{{ route('trash.index') }}" class="nav-link {{ request()->is('utility*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-recycle"></i>
                                <p>Data Terhapus</p>
                            </a>
                        </li>
                        @endif

                        <li class="nav-item {{ Route::is('collection.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Route::is('collection.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-book"></i>
                                <p>
                                    Kolektor
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('collection.visit.index') }}" class="nav-link {{ Route::is('collection.visit.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Nasabah Bermasalah</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('collection.foreclosure.index') }}" class="nav-link {{ Route::is('collection.foreclosure.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Penarikan Jaminan</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">{!! $title !!}</h1>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <div class="content">
                @yield('content')
            </div>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <!-- Main Footer -->
        <footer class="main-footer">
            <!-- To the right -->
            <div class="float-right d-none d-sm-inline">
                On Development
            </div>
            <!-- Default to the left -->
            <strong>Copyright &copy; 2022 <a href="{{ url('/') }}">{{ config('app.name') }}</a>.</strong>
            All rights reserved.
        </footer>
    </div>
    <!-- ./wrapper -->

    <!-- REQUIRED SCRIPTS -->

    <!-- jQuery -->
    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('js/adminlte.min.js') }}"></script>
    @stack('script')
    <script>
        // Global Passbook Print Modal Selector
        function openPassbookModal(passbookUrl, title, isBulk = false) {
            const isCustomerPassbook = (passbookUrl && passbookUrl.includes('/nasabah/') && passbookUrl.includes('/buku-tabungan'));

            // Jika buku tabungan nasabah (bulk / multi transaksi): Tampilkan modal pilihan cetak
            if (isCustomerPassbook) {
                // Tampilkan loading saat mengambil daftar transaksi
                Swal.fire({
                    title: 'Memuat Data Transaksi...',
                    html: '<i class="fas fa-spinner fa-spin fa-2x text-info"></i><p class="mt-2 text-muted">Mengambil data transaksi buku tabungan...</p>',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Fetch data transaksi via AJAX JSON
                $.ajax({
                    url: passbookUrl + (passbookUrl.includes('?') ? '&' : '?') + 'format=json',
                    method: 'GET',
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        const transactions = response.transactions || [];
                        const customer = response.customer || {};
                        const displayTitle = title || ('Buku Tabungan - ' + (customer.name || 'Nasabah'));

                        if (transactions.length === 0) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Belum Ada Transaksi',
                                text: 'Nasabah ini belum memiliki riwayat transaksi simpanan untuk dicetak.',
                                confirmButtonColor: '#17a2b8'
                            });
                            return;
                        }

                        // Bangun tabel HTML baris transaksi
                        let rowsHtml = '';
                        transactions.forEach(function(txn) {
                            let badgeClass = 'badge-primary';
                            if (txn.type_label === 'Penarikan') badgeClass = 'badge-danger';
                            else if (txn.type_label === 'Bunga') badgeClass = 'badge-warning text-dark';
                            else badgeClass = 'badge-success';

                            rowsHtml += `
                                <tr class="passbook-row-item" data-id="${txn.id}" data-row="${txn.row_num}" style="cursor: pointer;">
                                    <td class="text-center" style="vertical-align: middle;">
                                        <input type="checkbox" class="passbook-row-cb" value="${txn.id}" data-row="${txn.row_num}" style="cursor: pointer;">
                                    </td>
                                    <td class="text-center font-weight-bold" style="vertical-align: middle;">Baris ${txn.row_num}</td>
                                    <td style="vertical-align: middle;">${txn.date}</td>
                                    <td style="vertical-align: middle;"><span class="badge ${badgeClass}">${txn.type_label}</span></td>
                                    <td class="text-right text-success" style="vertical-align: middle;">${txn.credit}</td>
                                    <td class="text-right text-danger" style="vertical-align: middle;">${txn.debit}</td>
                                    <td class="text-right font-weight-bold" style="vertical-align: middle;">Rp ${txn.balance}</td>
                                </tr>
                            `;
                        });

                        Swal.fire({
                            title: '<i class="fas fa-book mr-2 text-info"></i> Pilihan Cetak Buku Tabungan',
                            width: '780px',
                            html: `
                                <div class="text-left" style="font-size: 13px;">
                                    <div class="alert alert-info py-2 px-3 mb-3 d-flex justify-content-between align-items-center">
                                        <div><strong>${displayTitle}</strong></div>
                                        <div><span class="badge badge-light p-1 font-weight-normal">Total: <strong>${transactions.length}</strong> Baris Transaksi</span></div>
                                    </div>

                                    <div class="card p-3 mb-3 bg-light border">
                                        <label class="font-weight-bold mb-2 text-dark"><i class="fas fa-sliders-h mr-1"></i> Pilih Opsi Cetak:</label>
                                        <div class="custom-control custom-radio mb-2">
                                            <input type="radio" id="passbook-mode-all" name="passbook_mode" value="all" class="custom-control-input" checked>
                                            <label class="custom-control-label font-weight-bold" for="passbook-mode-all" style="cursor: pointer;">
                                                <i class="fas fa-print mr-1 text-primary"></i> Cetak Semua (${transactions.length} Baris Data)
                                            </label>
                                            <small class="text-muted d-block ml-4">Mencetak seluruh baris rincian data transaksi secara otomatis dari awal sampai akhir.</small>
                                        </div>
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="passbook-mode-selected" name="passbook_mode" value="selected" class="custom-control-input">
                                            <label class="custom-control-label font-weight-bold" for="passbook-mode-selected" style="cursor: pointer;">
                                                <i class="fas fa-check-square mr-1 text-success"></i> Cetak Sesuai Baris yang Dipilih
                                            </label>
                                            <small class="text-muted d-block ml-4">Pilih satu atau beberapa baris data transaksi tertentu pada tabel untuk dicetak.</small>
                                        </div>
                                    </div>

                                    <div id="passbook-rows-section" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="font-weight-bold mb-0 text-dark">
                                                <i class="fas fa-list-ol mr-1"></i> Centang Baris yang Ingin Dicetak:
                                            </label>
                                            <div>
                                                <button type="button" id="btn-select-all-rows" class="btn btn-outline-primary btn-xs py-0 px-2 mr-1">Pilih Semua</button>
                                                <button type="button" id="btn-unselect-all-rows" class="btn btn-outline-secondary btn-xs py-0 px-2">Batal Pilih</button>
                                            </div>
                                        </div>

                                        <div class="table-responsive border rounded mb-2" style="max-height: 240px; overflow-y: auto;">
                                            <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 12px;">
                                                <thead class="thead-light" style="position: sticky; top: 0; z-index: 2; background-color: #f8f9fa;">
                                                    <tr>
                                                        <th width="35" class="text-center p-1">
                                                            <input type="checkbox" id="passbook-check-all" title="Pilih/Batal Semua">
                                                        </th>
                                                        <th width="75" class="text-center">No Baris</th>
                                                        <th>Tanggal</th>
                                                        <th>Jenis</th>
                                                        <th class="text-right">Masuk</th>
                                                        <th class="text-right">Keluar</th>
                                                        <th class="text-right">Saldo</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${rowsHtml}
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="row align-items-center bg-light p-2 border rounded">
                                            <div class="col-sm-8">
                                                <label for="swal-start-row-input" class="font-weight-bold mb-0 text-dark">
                                                    Mulai Cetak di Baris Buku Fisik Ke:
                                                </label>
                                                <small class="text-muted d-block">Baris pertama yang dipilih akan dicetak mulai dari baris fisik ini (1 s/d 30).</small>
                                            </div>
                                            <div class="col-sm-4 text-right">
                                                <input type="number" id="swal-start-row-input" class="form-control form-control-sm text-center font-weight-bold" min="1" max="30" value="1">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonColor: '#17a2b8',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="fas fa-print"></i> Cetak ke Printer',
                            cancelButtonText: 'Batal',
                            didOpen: () => {
                                const modeAll = document.getElementById('passbook-mode-all');
                                const modeSelected = document.getElementById('passbook-mode-selected');
                                const rowsSection = document.getElementById('passbook-rows-section');
                                const checkAll = document.getElementById('passbook-check-all');
                                const btnSelectAll = document.getElementById('btn-select-all-rows');
                                const btnUnselectAll = document.getElementById('btn-unselect-all-rows');
                                const startRowInput = document.getElementById('swal-start-row-input');

                                function toggleMode() {
                                    if (modeSelected.checked) {
                                        $(rowsSection).slideDown(150);
                                    } else {
                                        $(rowsSection).slideUp(150);
                                    }
                                }

                                function updateStartRowFromSelection() {
                                    const checkedBoxes = $('.passbook-row-cb:checked');
                                    if (checkedBoxes.length > 0) {
                                        const firstRow = parseInt($(checkedBoxes[0]).data('row')) || 1;
                                        startRowInput.value = firstRow;
                                    }
                                    const totalBoxes = $('.passbook-row-cb').length;
                                    checkAll.checked = (checkedBoxes.length === totalBoxes && totalBoxes > 0);
                                }

                                $('input[name="passbook_mode"]').on('change', toggleMode);

                                $(checkAll).on('change', function() {
                                    $('.passbook-row-cb').prop('checked', this.checked);
                                    updateStartRowFromSelection();
                                });

                                $(btnSelectAll).on('click', function() {
                                    $('.passbook-row-cb').prop('checked', true);
                                    checkAll.checked = true;
                                    updateStartRowFromSelection();
                                });

                                $(btnUnselectAll).on('click', function() {
                                    $('.passbook-row-cb').prop('checked', false);
                                    checkAll.checked = false;
                                    startRowInput.value = 1;
                                });

                                $('.passbook-row-item').on('click', function(e) {
                                    if (e.target.type !== 'checkbox') {
                                        const cb = $(this).find('.passbook-row-cb');
                                        cb.prop('checked', !cb.prop('checked'));
                                    }
                                    updateStartRowFromSelection();
                                });

                                $('.passbook-row-cb').on('change', function() {
                                    updateStartRowFromSelection();
                                });
                            },
                            preConfirm: () => {
                                const mode = $('input[name="passbook_mode"]:checked').val();
                                if (mode === 'selected') {
                                    const selectedCheckboxes = $('.passbook-row-cb:checked');
                                    if (selectedCheckboxes.length === 0) {
                                        Swal.showValidationMessage('Silakan pilih minimal 1 baris transaksi yang akan dicetak!');
                                        return false;
                                    }

                                    const selectedIds = [];
                                    selectedCheckboxes.each(function() {
                                        selectedIds.push($(this).val());
                                    });

                                    let startRow = parseInt($('#swal-start-row-input').val()) || 1;
                                    if (startRow < 1 || startRow > 30) {
                                        Swal.showValidationMessage('Nomor baris fisik harus antara 1 s/d 30');
                                        return false;
                                    }

                                    return {
                                        mode: 'selected',
                                        ids: selectedIds,
                                        startRow: startRow
                                    };
                                } else {
                                    return {
                                        mode: 'all',
                                        startRow: 1
                                    };
                                }
                            }
                        }).then((result) => {
                            if (result.isConfirmed && result.value) {
                                const val = result.value;
                                let targetUrl = passbookUrl;
                                if (val.mode === 'selected') {
                                    targetUrl += (targetUrl.includes('?') ? '&' : '?') + 'transaction_ids=' + val.ids.join(',') + '&row=' + val.startRow;
                                } else {
                                    targetUrl += (targetUrl.includes('?') ? '&' : '?') + 'row=1';
                                }
                                window.open(targetUrl, '_blank', 'width=800,height=700');
                            }
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Memuat Data',
                            text: 'Terjadi kesalahan saat memuat data transaksi buku tabungan.',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
                return;
            }

            // Fallback untuk single-transaction passbook (misal pada halaman simpanan/penarikan satuan)
            Swal.fire({
                title: 'Cetak Buku Tabungan',
                html: `
                    <div class="text-left" style="font-size: 13px;">
                        <p class="mb-2"><strong>${title || 'Transaksi Nasabah'}</strong></p>
                        <label for="swal-passbook-row" class="font-weight-bold">Mulai Cetak di Baris Ke:</label>
                        <input id="swal-passbook-row" type="number" class="form-control" value="1" min="1" max="25" style="text-align: center; font-size: 16px; font-weight: bold;">
                        <small class="text-muted d-block mt-1">Pilih baris pada buku tabungan fisik (Baris 1 s/d 25)</small>
                    </div>
                `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#17a2b8',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-print"></i> Cetak ke Printer',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    const row = document.getElementById('swal-passbook-row').value;
                    if (!row || row < 1 || row > 30) {
                        Swal.showValidationMessage('Nomor baris harus antara 1 s/d 25');
                        return false;
                    }
                    return row;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const selectedRow = result.value;
                    const targetUrl = passbookUrl + (passbookUrl.includes('?') ? '&' : '?') + 'row=' + selectedRow;
                    window.open(targetUrl, '_blank', 'width=800,height=700');
                }
            });
        }

        $(document).ready(function() {
            // Handler tombol cetak buku tabungan di tabel/tombol
            $(document).on('click', '.print-passbook-btn', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                const title = $(this).data('title');
                const type = $(this).data('type');
                const isBulk = (type === 'bulk') || (url && url.includes('/nasabah/') && url.includes('/buku-tabungan'));
                openPassbookModal(url, title, isBulk);
            });

            @if (session('receipt_url') && session('passbook_url'))
                Swal.fire({
                    title: 'Transaksi Berhasil!',
                    text: "{{ session('success') }}",
                    icon: 'success',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonColor: '#28a745',
                    denyButtonColor: '#17a2b8',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-print"></i> Cetak Kwitansi',
                    denyButtonText: '<i class="fas fa-book"></i> Print Buku Tabungan',
                    cancelButtonText: 'Selesai',
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open("{{ session('receipt_url') }}", '_blank');
                    } else if (result.isDenied) {
                        openPassbookModal("{{ session('passbook_url') }}", 'Transaksi Terakhir');
                    }
                });
            @elseif (session('receipt_url'))
                Swal.fire({
                    title: 'Transaksi Berhasil!',
                    text: "{{ session('success') }}",
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-print"></i> Cetak Kwitansi',
                    cancelButtonText: 'Selesai',
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open("{{ session('receipt_url') }}", '_blank');
                    }
                });
            @elseif (session('deletion_receipt_url'))
                Swal.fire({
                    title: 'Transaksi Berhasil Dihapus',
                    text: "{{ session('success') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-print"></i> Cetak Kwitansi Penghapusan',
                    cancelButtonText: 'Selesai',
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open("{{ session('deletion_receipt_url') }}", '_blank');
                    }
                });
            @elseif (session('success'))
                notification('success', '{{ session('success') }}')
            @elseif (session('error'))
                notification('error', '{{ session('error') }}')
            @endif

            $(document).on('click', '#logout-button', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Keluar',
                    text: "Anda yakin ingin keluar?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Iya, saya yakin!',
                    cancelButtonText: 'Batalkan',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(this).closest('form.d-inline').submit();
                        $('#logout-form').submit();
                    }
                });
            });
            $(document).on('click', '#truncate-button', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Reset Data',
                    text: "Anda yakin ingin reset data?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Iya, saya yakin!',
                    cancelButtonText: 'Batalkan',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(this).closest('form.d-inline').submit();
                        $('#truncate-form').submit();
                    }
                });
            });

            $(document).on("click", "button[type=submit].delete-data", function(e) {
                Swal.fire({
                    title: 'Hapus',
                    text: 'Anda yakin ingin menghapus?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    // cancelButtonColor: '#d33',
                    confirmButtonText: 'Iya, saya yakin!',
                    cancelButtonText: 'Batalkan',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(this).closest('form.d-inline').submit();
                    }
                });
                return false;
            });
        });

        function notification(icon, message) {
            Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            }).fire({
                icon: icon,
                title: message,
            })
        }
    </script>
</body>

</html>
