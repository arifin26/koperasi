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
        $(document).ready(function() {

            @if (session('receipt_url'))
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
