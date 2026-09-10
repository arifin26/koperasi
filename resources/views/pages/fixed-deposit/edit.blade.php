@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-edit mr-1"></i> Edit Deposito {{ $deposit->number }}</h3>
            </div>
            <form action="{{ route('fixed-deposit.update', $deposit) }}" method="POST" id="formEditDeposito">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <!-- Info Nasabah (Read-only) -->
                    <div class="form-group">
                        <label>Nasabah</label>
                        <div class="alert alert-light border">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $deposit->customer->name ?? '-' }}</strong>
                                    <div class="text-muted small">No. Rekening Simpanan: {{ $deposit->customer->number ?? '-' }} | NIK: {{ $deposit->customer->nik ?? '-' }}</div>
                                </div>
                                <div>
                                    {!! $deposit->status_label !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nomor Rekening Deposito -->
                    <div class="form-group">
                        <label>Nomor Rekening Deposito <span class="text-danger">*</span></label>
                        <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror"
                            value="{{ old('account_number', $deposit->account_number) }}" placeholder="Contoh: DEP-00001" required>
                        <small class="form-text text-muted">Nomor rekening khusus untuk bilyet deposito ini.</small>
                        @error('account_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Nominal Deposito -->
                    <div class="form-group">
                        <label>Nominal Deposito <span class="text-danger">*</span> <small class="text-muted">(Min. Rp 1.000.000)</small></label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                            <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror"
                                value="{{ old('amount', $deposit->amount) }}" min="1000000" required placeholder="Contoh: 10000000">
                        </div>
                        @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Tenor Deposito -->
                    <div class="form-group">
                        <label>Tenor Deposito <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" min="1" max="120" name="tenor_months" id="tenor_months" class="form-control @error('tenor_months') is-invalid @enderror"
                                value="{{ old('tenor_months', $deposit->tenor_months) }}" required placeholder="Contoh: 3, 6, 12">
                            <div class="input-group-append"><span class="input-group-text">Bulan</span></div>
                        </div>
                        <small class="form-text text-muted">Durasi penempatan deposito dalam satuan bulan.</small>
                        @error('tenor_months')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Rate Bunga -->
                    <div class="form-group">
                        <label>Rate Bunga (% p.a.) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="rate_percent" id="rate_percent"
                                class="form-control font-weight-bold @error('rate_percent') is-invalid @enderror"
                                value="{{ old('rate_percent', $deposit->rate_percent) }}" required placeholder="Contoh: 6.00">
                            <div class="input-group-append"><span class="input-group-text">% p.a.</span></div>
                        </div>
                        @error('rate_percent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <hr>

                    <!-- Tanggal Mulai -->
                    <div class="form-group">
                        <label>Tanggal Mulai Deposito <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror"
                            value="{{ old('start_date', $deposit->start_date ? $deposit->start_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                        @error('start_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Estimasi Jatuh Tempo & Bunga (Live Calculation) -->
                    <div class="form-group">
                        <label>Tanggal Jatuh Tempo (Kalkulasi)</label>
                        <input type="text" id="maturity_display" class="form-control font-weight-bold text-primary" disabled value="-">
                    </div>
                    <div class="form-group">
                        <label>Estimasi Bunga per Bulan</label>
                        <input type="text" id="monthly_interest_display" class="form-control font-weight-bold text-success" disabled value="-">
                    </div>
                    <div class="form-group">
                        <label>Estimasi Total Bunga (s.d Jatuh Tempo)</label>
                        <input type="text" id="total_interest_display" class="form-control font-weight-bold text-success" disabled value="-">
                    </div>

                    <!-- Keterangan -->
                    <div class="form-group">
                        <label>Keterangan / Catatan</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $deposit->notes) }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('fixed-deposit.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" id="btnSubmitDeposito" class="btn btn-warning font-weight-bold float-right">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
$(document).ready(function() {
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
    }

    function calculateMaturityAndInterest() {
        var startDateVal = $('#start_date').val();
        var tenor = parseInt($('#tenor_months').val()) || 0;
        var amount = parseFloat($('#amount').val()) || 0;
        var rate = parseFloat($('#rate_percent').val()) || 0;

        if (startDateVal && tenor > 0) {
            var startDate = new Date(startDateVal);
            var maturityDate = new Date(startDate);
            maturityDate.setMonth(maturityDate.getMonth() + tenor);

            var options = { day: 'numeric', month: 'long', year: 'numeric' };
            $('#maturity_display').val(maturityDate.toLocaleDateString('id-ID', options));
        } else {
            $('#maturity_display').val('-');
        }

        if (amount > 0 && rate > 0) {
            var monthlyInterest = Math.floor(amount * (rate / 100) / 12);
            var totalInterest = monthlyInterest * tenor;
            $('#monthly_interest_display').val(formatRupiah(monthlyInterest) + ' / bulan');
            $('#total_interest_display').val(formatRupiah(totalInterest) + ' (' + tenor + ' bulan)');
        } else {
            $('#monthly_interest_display').val('-');
            $('#total_interest_display').val('-');
        }
    }

    $('#start_date, #tenor_months, #amount, #rate_percent').on('input change', calculateMaturityAndInterest);

    // Initial calculation on load
    calculateMaturityAndInterest();
});
</script>
@endpush
