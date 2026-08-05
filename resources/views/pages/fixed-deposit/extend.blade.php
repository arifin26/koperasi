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
                        <label>Tenor Baru <span class="text-danger">*</span></label>
                        @php $tenors = [3,6,12]; @endphp
                        @foreach($tenors as $t)
                            @php
                                $rateKey = 'deposito_'.$t.'_bulan';
                                $rateValue = isset($rates[$rateKey]) ? $rates[$rateKey]->rate_percent : '-';
                            @endphp
                            <div class="custom-control custom-radio">
                                <input class="custom-control-input" type="radio" name="tenor_months" id="tenor_{{ $t }}" value="{{ $t }}" {{ $t == $deposit->tenor_months ? 'checked' : '' }}>
                                <label class="custom-control-label" for="tenor_{{ $t }}">
                                    {{ $t }} Bulan — <strong>{{ $rateValue }}% p.a.</strong>
                                </label>
                            </div>
                        @endforeach
                        @error('tenor_months')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
