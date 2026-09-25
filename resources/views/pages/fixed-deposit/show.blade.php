@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Detail Deposito {{ $deposit->number }}</h3>
                <div class="card-tools ml-auto">
                    <a href="{{ route('fixed-deposit.receipt', $deposit) }}" target="_blank" class="btn btn-secondary btn-sm mr-2 receipt-print-btn">
                        <i class="fas fa-print mr-1"></i> Cetak Kwitansi
                    </a>
                    <a href="{{ route('fixed-deposit.edit', $deposit) }}" class="btn btn-primary btn-sm mr-2">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                    @if(auth()->user()->role == 'manager')
                    <form class="d-inline" method="POST" action="{{ route('fixed-deposit.destroy', $deposit) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm delete-data mr-2">
                            <i class="fas fa-trash mr-1"></i> Hapus
                        </button>
                    </form>
                    @endif
                    {!! $deposit->status_label !!}
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr><th width="40%">No. Deposito</th><td>{{ $deposit->number }}</td></tr>
                            <tr><th>Rekening Deposito</th><td class="font-weight-bold text-primary">{{ $deposit->account_number ?? '-' }}</td></tr>
                            <tr><th>Nasabah</th><td>{{ $deposit->customer->name ?? '-' }}</td></tr>
                            <tr><th>No. Rek. Simpanan</th><td>{{ $deposit->customer->number ?? '-' }}</td></tr>
                            <tr><th>Nominal</th><td class="font-weight-bold">Rp {{ number_format($deposit->amount, 0, ',', '.') }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr><th width="40%">Tenor</th><td>{{ $deposit->tenor_months }} Bulan</td></tr>
                            <tr><th>Rate</th><td>{{ $deposit->rate_percent }}% p.a.</td></tr>
                            <tr><th>Bunga/Bulan</th><td class="text-success font-weight-bold">Rp {{ number_format($deposit->monthly_interest, 0, ',', '.') }}</td></tr>
                            <tr><th>Tanggal Mulai</th><td>{{ $deposit->start_date->isoFormat('DD MMMM Y') }}</td></tr>
                            <tr><th>Jatuh Tempo</th><td>{{ $deposit->maturity_date->isoFormat('DD MMMM Y') }}</td></tr>
                        </table>
                    </div>
                </div>

                @if($deposit->notes)
                <div class="alert alert-light mt-2">
                    <strong>Keterangan:</strong> {{ $deposit->notes }}
                </div>
                @endif

                @if($deposit->status == 'active')
                <div class="mt-3">
                    <a href="{{ route('fixed-deposit.extend.form', $deposit) }}" class="btn btn-info"><i class="fas fa-sync"></i> Perpanjang</a>
                    <a href="{{ route('fixed-deposit.liquidate.form', $deposit) }}" class="btn btn-warning"><i class="fas fa-money-bill-wave"></i> Cairkan</a>
                </div>
                @endif
            </div>
        </div>

        <!-- Riwayat Pembayaran Bunga -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Riwayat Pembayaran Bunga Bulanan</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Periode</th>
                            <th>Nominal Bunga</th>
                            <th>Status</th>
                            <th>Tanggal Transfer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deposit->interestPayments as $i => $payment)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $payment->period }}</td>
                            <td>Rp {{ number_format($payment->interest_amount, 0, ',', '.') }}</td>
                            <td>
                                @if($payment->paid_at)
                                    <span class="badge badge-success">Dibayar</span>
                                @else
                                    <span class="badge badge-secondary">Pending</span>
                                @endif
                            </td>
                            <td>{{ $payment->paid_at ? $payment->paid_at->isoFormat('DD MMM Y HH:mm') : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">Belum ada pembayaran bunga.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <a href="{{ route('fixed-deposit.index') }}" class="btn btn-secondary btn-block mb-3"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a>
    </div>
</div>
@endsection
