@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Pembukaan Deposito Baru</h3>
            </div>
            <form action="{{ route('fixed-deposit.store') }}" method="POST" id="formDeposito">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Nasabah <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" class="form-control select2-ajax @error('customer_id') is-invalid @enderror" required>
                        </select>
                        @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Nominal Deposito <span class="text-danger">*</span> <small class="text-muted">(Min. Rp 1.000.000)</small></label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                            <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" min="1000000" required placeholder="Contoh: 10000000">
                        </div>
                        @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Tenor & Pilihan Bunga <span class="text-danger">*</span></label>
                        <select class="form-control tenor-select @error('tenor_months') is-invalid @enderror" name="tenor_months" id="tenor_months">
                            <option value="">-- Pilih Tenor --</option>
                            @php $tenors = [3,6,12]; @endphp
                            @foreach($tenors as $t)
                                @php
                                    $rateKey = 'deposito_'.$t.'_bulan';
                                    $rateValue = isset($rates[$rateKey]) ? $rates[$rateKey]->rate_percent : '-';
                                @endphp
                                <option value="{{ $t }}" data-rate="{{ $rateValue }}" {{ old('tenor_months') == $t ? 'selected' : '' }}>
                                    Deposito {{ $t }} Bulan — {{ $rateValue }}% p.a.
                                </option>
                            @endforeach
                        </select>
                        @error('tenor_months')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal Mulai</label>
                                <input type="text" class="form-control" value="{{ date('d F Y') }}" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal Jatuh Tempo</label>
                                <input type="text" id="maturity_display" class="form-control" disabled value="-">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Estimasi Bunga per Bulan</label>
                        <input type="text" id="monthly_interest_display" class="form-control font-weight-bold text-success" disabled value="-">
                    </div>

                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="notes" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('fixed-deposit.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary float-right"><i class="fas fa-save"></i> Buka Deposito</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Select2 AJAX for customer
    $('#customer_id').select2({
        placeholder: 'Ketik Nama / No. Rekening Nasabah...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '/api/nasabah/search',
            dataType: 'json',
            delay: 300,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return { id: item.id, text: item.number + ' — ' + item.name };
                    })
                };
            }
        }
    });

    // Auto-calculate maturity & interest
    function recalculate() {
        var amount = parseInt($('input[name="amount"]').val()) || 0;
        var selected = $('#tenor_months').find(':selected');
        
        if (selected.val() === "" || amount < 1000000) {
            $('#maturity_display').val('-');
            $('#monthly_interest_display').val('-');
            return;
        }

        var tenor = parseInt(selected.val());
        var rate = parseFloat(selected.data('rate'));

        // Maturity date
        var maturity = new Date();
        maturity.setMonth(maturity.getMonth() + tenor);
        var options = { day: '2-digit', month: 'long', year: 'numeric' };
        $('#maturity_display').val(maturity.toLocaleDateString('id-ID', options));

        // Monthly interest
        var monthlyInterest = Math.floor(amount * (rate / 100) / 12);
        $('#monthly_interest_display').val('Rp ' + monthlyInterest.toLocaleString('id-ID') + ' / bulan');
    }

    $('input[name="amount"]').on('input', recalculate);
    $('#tenor_months').on('change', recalculate);
    recalculate();
});
</script>
@endsection
