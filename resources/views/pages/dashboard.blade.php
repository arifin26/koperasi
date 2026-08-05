@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Info Boxes -->
    <div class="row">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Nasabah Aktif</span>
                    <span class="info-box-number">{{ number_format($nasabahAktif, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-arrow-down"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Masuk Hari Ini</span>
                    <span class="info-box-number">Rp{{ number_format($masukHariIni, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-arrow-up"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Keluar Hari Ini</span>
                    <span class="info-box-number">Rp{{ number_format($keluarHariIni, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-wallet"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Tabungan</span>
                    <span class="info-box-number">Rp{{ number_format($totalTabungan, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Engine Status -->
    <div class="row">
        <div class="col-12">
            <div class="alert @if($lastEngineLog && $lastEngineLog->status == 'success') alert-success @else alert-warning @endif">
                <h5><i class="icon fas fa-info"></i> Status Engine Bunga</h5>
                @if($lastEngineLog)
                    Terakhir berjalan: {{ \Carbon\Carbon::parse($lastEngineLog->run_date)->isoFormat('dddd, D MMMM Y HH:mm') }}<br>
                    Status: {{ ucfirst($lastEngineLog->status) }} | Total diproses: {{ $lastEngineLog->total_customers_processed }} nasabah
                @else
                    Engine belum pernah berjalan.
                @endif
            </div>
        </div>
    </div>

    <!-- Chart & Lists -->
    <div class="row">
        <!-- Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">Grafik Transaksi 7 Hari Terakhir</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div class="position-relative mb-4">
                        <canvas id="transactionChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Deposito Jatuh Tempo (30 Hari)</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-valign-middle">
                        <thead>
                            <tr>
                                <th>No. Deposito</th>
                                <th>Nasabah</th>
                                <th>Jatuh Tempo</th>
                                <th>Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($maturedDeposits as $dep)
                            <tr>
                                <td>{{ $dep->number }}</td>
                                <td>{{ $dep->customer->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($dep->maturity_date)->isoFormat('DD MMM Y') }}</td>
                                <td>Rp{{ number_format($dep->amount, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada deposito jatuh tempo dalam 30 hari ke depan</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Transaksi Terakhir</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="products-list product-list-in-card pl-2 pr-2">
                        @forelse($recentTransactions as $trx)
                        <li class="item">
                            <div class="product-info ml-0">
                                <a href="javascript:void(0)" class="product-title">
                                    {{ $trx->customer->name }}
                                    @if($trx->type == 'penarikan')
                                        <span class="badge badge-danger float-right">-Rp{{ number_format($trx->amount, 0, ',', '.') }}</span>
                                    @else
                                        <span class="badge badge-success float-right">+Rp{{ number_format($trx->amount, 0, ',', '.') }}</span>
                                    @endif
                                </a>
                                <span class="product-description">
                                    {{ ucfirst($trx->type) }} ({{ \Carbon\Carbon::parse($trx->created_at)->diffForHumans() }})
                                </span>
                            </div>
                        </li>
                        @empty
                        <li class="item text-center">Belum ada transaksi</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(function () {
        const rawChartData = {!! $chartData !!};
        
        const ctx = document.getElementById('transactionChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: rawChartData.labels,
                datasets: [
                    {
                        label: 'Masuk',
                        backgroundColor: '#28a745',
                        data: rawChartData.masuk
                    },
                    {
                        label: 'Keluar',
                        backgroundColor: '#dc3545',
                        data: rawChartData.keluar
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value, index, values) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }]
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return data.datasets[tooltipItem.datasetIndex].label + ': Rp ' + tooltipItem.yLabel.toLocaleString('id-ID');
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
