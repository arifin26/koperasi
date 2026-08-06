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
                        @php
                            $rateDefault = $activeRate ? $activeRate->rate_percent : $deposit->rate_percent;
                        @endphp
                        @if($activeRate)
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100"
                                    name="rate_percent" id="rate_percent"
                                    class="form-control font-weight-bold @error('rate_percent') is-invalid @enderror"
                                    value="{{ old('rate_percent', $activeRate->rate_percent) }}"
                                    required readonly>
                                <div class="input-group-append"><span class="input-group-text">% p.a.</span></div>
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle text-primary"></i>
                                Dari Manajemen Bunga: <strong>{{ $activeRate->rate_percent }}%</strong>
                                (berlaku sejak {{ \Carbon\Carbon::parse($activeRate->effective_date)->isoFormat('D MMM Y') }}).
                                <a href="#" id="overrideRateLink">Ubah manual?</a>
                            </small>
                        @else
                            <div class="alert alert-warning py-1 mb-1" style="font-size:.85rem">
                                <i class="fas fa-exclamation-triangle"></i>
                                Rate Deposito belum diatur di Manajemen Bunga.
                                <a href="{{ route('interest.create') }}" target="_blank">Tambah sekarang</a>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100"
                                    name="rate_percent" id="rate_percent"
                                    class="form-control @error('rate_percent') is-invalid @enderror"
                                    value="{{ old('rate_percent', $deposit->rate_percent) }}"
                                    required placeholder="Masukkan rate bunga">
                                <div class="input-group-append"><span class="input-group-text">% p.a.</span></div>
                            </div>
                        @endif
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

@section('scripts')
<script>
$(document).ready(function() {
    // Override rate link toggle
    $('#overrideRateLink').on('click', function(e) {
        e.preventDefault();
        var field = $('#rate_percent');
        if (field.prop('readonly')) {
            field.prop('readonly', false).focus().removeClass('font-weight-bold').addClass('border-warning');
            $(this).text('Gunakan dari Manajemen Bunga');
        } else {
            field.prop('readonly', true).addClass('font-weight-bold').removeClass('border-warning');
            field.val(field.data('system-rate'));
            $(this).text('Ubah manual?');
        }
    });

    @if($activeRate)
    $('#rate_percent').data('system-rate', {{ $activeRate->rate_percent }});
    @endif
});
</script>
@endsection
