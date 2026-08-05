@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">Cairkan Deposito {{ $deposit->number }}</h3>
            </div>
            <form action="{{ route('fixed-deposit.liquidate', $deposit) }}" method="POST">
                @csrf
                <div class="card-body">
                    @if($isEarly)
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Peringatan:</strong> Deposito ini belum jatuh tempo. Pencairan dini akan dikenai <strong>penalti 1%</strong> dari nominal pokok.
                    </div>
                    @else
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Deposito ini sudah jatuh tempo. Tidak ada penalti pencairan.
                    </div>
                    @endif

                    <table class="table table-bordered">
                        <tr><th width="45%">No. Deposito</th><td>{{ $deposit->number }}</td></tr>
                        <tr><th>Nasabah</th><td>{{ $deposit->customer->name ?? '-' }}</td></tr>
                        <tr><th>Nominal Pokok</th><td>Rp {{ number_format($deposit->amount, 0, ',', '.') }}</td></tr>
                        <tr><th>Jatuh Tempo</th><td>{{ $deposit->maturity_date->isoFormat('DD MMMM Y') }}</td></tr>
                        @if($isEarly)
                        <tr class="table-danger"><th>Penalti (1%)</th><td class="text-danger font-weight-bold">- Rp {{ number_format($penalty, 0, ',', '.') }}</td></tr>
                        @endif
                        <tr class="table-success"><th>Dana yang Ditransfer ke Tabungan Sukarela</th><td class="text-success font-weight-bold">Rp {{ number_format($netAmount, 0, ',', '.') }}</td></tr>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="{{ route('fixed-deposit.show', $deposit) }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-warning float-right" onclick="return confirm('Yakin ingin mencairkan deposito ini? Tindakan ini tidak dapat dibatalkan.')">
                        <i class="fas fa-money-bill-wave"></i> Cairkan Deposito
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
