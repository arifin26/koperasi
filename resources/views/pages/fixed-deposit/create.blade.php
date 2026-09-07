@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        @if(session('require_deposit'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h5><i class="icon fas fa-exclamation-triangle"></i> Nasabah Belum Memiliki Simpanan!</h5>
            Nasabah harus memiliki transaksi/rekening simpanan aktif terlebih dahulu agar bunga dan pencairan deposito dapat disalurkan.
            <div class="mt-2">
                <a href="{{ route('transaction.deposit.create', ['customer_id' => session('customer_id') ?? old('customer_id')]) }}" class="btn btn-sm btn-dark font-weight-bold">
                    <i class="fas fa-plus-circle mr-1"></i> Buat Transaksi Simpanan Sekarang
                </a>
            </div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Pembukaan Deposito Baru</h3>
            </div>
            <form action="{{ route('fixed-deposit.store') }}" method="POST" id="formDeposito">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Nasabah <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" data-placeholder="Ketik beberapa kata nama nasabah untuk mencari..." class="form-control select2-ajax @error('customer_id') is-invalid @enderror" required>
                            <option value=""></option>
                            @if($preselectedCustomer)
                            <option value="{{ $preselectedCustomer->id }}" selected>{{ $preselectedCustomer->number }} - {{ $preselectedCustomer->name }}</option>
                            @endif
                        </select>
                        @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        <!-- Warning banner jika nasabah belum punya simpanan -->
                        <div id="no-savings-warning" class="alert alert-warning mt-2 py-2" style="display: none;">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>Perhatian:</strong> Nasabah ini belum memiliki rekening/transaksi simpanan.
                            <div class="mt-1">
                                <a href="#" id="btn-create-savings-link" class="btn btn-xs btn-primary font-weight-bold" target="_blank">
                                    <i class="fas fa-plus-circle mr-1"></i> Buat Simpanan untuk Nasabah Ini Terlebih Dahulu
                                </a>
                            </div>
                        </div>

                        <!-- Info saldo simpanan nasabah jika ada -->
                        <div id="savings-info-box" class="alert alert-info mt-2 py-2" style="display: none;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Rekening Simpanan Aktif &bull; Saldo saat ini: <strong id="savings-balance-display">Rp 0</strong>
                            <small class="d-block text-muted">Bunga bulanan dan pencairan deposito akan langsung disalurkan ke simpanan ini.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nomor Rekening Deposito <span class="text-danger">*</span></label>
                        <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror"
                            value="{{ old('account_number') }}" placeholder="Contoh: DEP-00001" required>
                        <small class="form-text text-muted">Nomor rekening deposito harus berbeda dengan nomor rekening simpanan nasabah.</small>
                        @error('account_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
                        <label>Tenor Deposito <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" min="1" max="120" name="tenor_months" id="tenor_months" class="form-control @error('tenor_months') is-invalid @enderror" value="{{ old('tenor_months', 3) }}" required placeholder="Contoh: 3, 6, 12">
                            <div class="input-group-append"><span class="input-group-text">Bulan</span></div>
                        </div>
                        <small class="form-text text-muted">Bebas menginputkan durasi tenor dalam bulan.</small>
                        @error('tenor_months')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Rate Bunga (% p.a.) <span class="text-danger">*</span></label>
                        @if($rates->isNotEmpty())
                            <select name="rate_percent" id="rate_percent"
                                class="form-control font-weight-bold @error('rate_percent') is-invalid @enderror"
                                required>
                                @foreach($rates as $rate)
                                <option value="{{ $rate->rate_percent }}"
                                    {{ old('rate_percent', $activeRate?->rate_percent) == $rate->rate_percent ? 'selected' : '' }}>
                                    {{ $rate->rate_percent }}% p.a.
                                    (Berlaku: {{ \Carbon\Carbon::parse($rate->effective_date)->format('d/m/Y') }})
                                </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle text-primary"></i>
                                Rate dari Manajemen Bunga. Rate aktif otomatis dipilih.
                            </small>
                        @else
                            <div class="alert alert-warning py-1 mb-1" style="font-size:.85rem">
                                <i class="fas fa-exclamation-triangle"></i>
                                Rate Deposito belum diatur.
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

                    <hr>

                    <div class="form-group">
                        <label>Tanggal Mulai</label>
                        <input type="text" class="form-control" value="{{ date('d F Y') }}" disabled>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Jatuh Tempo</label>
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

                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="notes" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('fixed-deposit.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" id="btnSubmitDeposito" class="btn btn-primary float-right"><i class="fas fa-save"></i> Buka Deposito</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet" />
@endpush

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    var createDepositBaseUrl = '{{ route("transaction.deposit.create") }}';

    // Select2 AJAX for customer
    $('#customer_id').select2({
        theme: 'bootstrap4',
        placeholder: 'Ketik beberapa kata nama nasabah untuk mencari...',
        allowClear: true,
        minimumInputLength: 2,
        language: {
            inputTooShort: function () {
                return 'Ketik minimal 2 huruf nama nasabah...';
            }
        },
        ajax: {
            url: '{{ route("customer.search") }}',
            dataType: 'json',
            delay: 300,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) {
                return { results: data.results };
            }
        }
    });

    // Handle customer change to verify savings account
    $('#customer_id').on('change', function() {
        var customerId = $(this).val();
        if (!customerId) {
            $('#no-savings-warning').hide();
            $('#savings-info-box').hide();
            $('#btnSubmitDeposito').prop('disabled', false);
            return;
        }

        $.ajax({
            url: '/api/nasabah/' + customerId + '/saldo',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    if (!response.has_deposit || response.deposit_count <= 0) {
                        // Nasabah belum punya rekening simpanan
                        $('#btn-create-savings-link').attr('href', createDepositBaseUrl + '?customer_id=' + customerId);
                        $('#no-savings-warning').slideDown();
                        $('#savings-info-box').hide();
                        $('#btnSubmitDeposito').prop('disabled', true);
                    } else {
                        // Nasabah memiliki simpanan aktif
                        $('#savings-balance-display').text(response.data.current_balance_formatted || 'Rp 0');
                        $('#savings-info-box').slideDown();
                        $('#no-savings-warning').hide();
                        $('#btnSubmitDeposito').prop('disabled', false);
                    }
                }
            },
            error: function() {
                $('#no-savings-warning').hide();
                $('#savings-info-box').hide();
                $('#btnSubmitDeposito').prop('disabled', false);
            }
        });
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
});
</script>
@endpush
