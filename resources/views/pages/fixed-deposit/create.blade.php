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

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tenor Deposito <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" min="1" max="120" name="tenor_months" id="tenor_months" class="form-control @error('tenor_months') is-invalid @enderror" value="{{ old('tenor_months', 3) }}" required placeholder="Contoh: 3, 6, 12">
                                    <div class="input-group-append"><span class="input-group-text">Bulan</span></div>
                                </div>
                                <small class="form-text text-muted">Bebas menginputkan durasi tenor dalam bulan.</small>
                                @error('tenor_months')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Rate Bunga (% p.a.) <span class="text-danger">*</span></label>
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
                                        Rate bunga Deposito belum diatur.
                                        <a href="{{ route('interest.create') }}" target="_blank">Tambah sekarang</a>
                                    </div>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" max="100"
                                            name="rate_percent" id="rate_percent"
                                            class="form-control @error('rate_percent') is-invalid @enderror"
                                            value="{{ old('rate_percent') }}"
                                            required placeholder="Masukkan rate bunga">
                                        <div class="input-group-append"><span class="input-group-text">% p.a.</span></div>
                                    </div>
                                @endif
                                @error('rate_percent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
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
                                <input type="text" id="maturity_display" class="form-control font-weight-bold text-primary" disabled value="-">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Estimasi Bunga per Bulan</label>
                                <input type="text" id="monthly_interest_display" class="form-control font-weight-bold text-success" disabled value="-">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Estimasi Total Bunga (s.d Jatuh Tempo)</label>
                                <input type="text" id="total_interest_display" class="form-control font-weight-bold text-success" disabled value="-">
                            </div>
                        </div>
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
        var tenor = parseInt($('#tenor_months').val()) || 0;
        var rate = parseFloat($('#rate_percent').val()) || 0;
        
        if (tenor <= 0) {
            $('#maturity_display').val('-');
            $('#monthly_interest_display').val('-');
            $('#total_interest_display').val('-');
            return;
        }

        // Maturity date calculation
        var maturity = new Date();
        maturity.setMonth(maturity.getMonth() + tenor);
        var options = { day: '2-digit', month: 'long', year: 'numeric' };
        $('#maturity_display').val(maturity.toLocaleDateString('id-ID', options));

        if (amount >= 1000000 && rate > 0) {
            // Monthly interest
            var monthlyInterest = Math.floor(amount * (rate / 100) / 12);
            var totalInterest = monthlyInterest * tenor;

            $('#monthly_interest_display').val('Rp ' + monthlyInterest.toLocaleString('id-ID') + ' / bulan');
            $('#total_interest_display').val('Rp ' + totalInterest.toLocaleString('id-ID') + ' (selama ' + tenor + ' bulan)');
        } else {
            $('#monthly_interest_display').val('-');
            $('#total_interest_display').val('-');
        }
    }

    $('input[name="amount"], #tenor_months, #rate_percent').on('input change', recalculate);
    recalculate();

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
            recalculate();
        }
    });

    // Store the system rate as data attribute for reset
    @if(isset($activeRate))
    $('#rate_percent').data('system-rate', {{ $activeRate->rate_percent }});
    @endif
});
</script>
@endsection
