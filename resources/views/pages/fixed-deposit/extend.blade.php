@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Perpanjang Deposito {{ $deposit->number }}</h3>
            </div>
            <form action="{{ route('fixed-deposit.extend', $deposit) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Deposito lama akan ditutup (status: <strong>Diperpanjang</strong>) dan deposito baru akan dibuka dengan nominal yang sama.
                    </div>

                    <table class="table table-sm table-borderless mb-3">
                        <tr><th>No. Deposito Lama</th><td>{{ $deposit->number }}</td></tr>
                        <tr><th>Nominal</th><td>Rp {{ number_format($deposit->amount, 0, ',', '.') }}</td></tr>
                        <tr><th>Nasabah</th><td>{{ $deposit->customer->name ?? '-' }}</td></tr>
                    </table>

                    <div class="form-group">
                        <label>Tenor Baru (Bulan) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" min="1" max="120" name="tenor_months" id="tenor_months" class="form-control @error('tenor_months') is-invalid @enderror" value="{{ old('tenor_months', $deposit->tenor_months) }}" required placeholder="Contoh: 3, 6, 12">
                            <div class="input-group-append"><span class="input-group-text">Bulan</span></div>
                        </div>
                        @error('tenor_months')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Rate Bunga Baru (% p.a.) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="rate_percent" id="rate_percent" class="form-control @error('rate_percent') is-invalid @enderror" value="{{ old('rate_percent', $defaultRate) }}" required placeholder="Contoh: 5.00">
                            <div class="input-group-append"><span class="input-group-text">% p.a.</span></div>
                        </div>
                        @error('rate_percent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('fixed-deposit.show', $deposit) }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-info float-right" onclick="return confirm('Yakin ingin memperpanjang deposito ini?')">
                        <i class="fas fa-sync"></i> Perpanjang Deposito
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
