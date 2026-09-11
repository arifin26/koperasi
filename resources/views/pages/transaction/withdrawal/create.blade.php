@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('transaction.withdrawal.store') }}" method="post">
                            @csrf
                            <input type="hidden" name="type" value="penarikan">
                            <div class="form-group">
                                <label>Kode Transaksi</label>
                                <input type="text" class="form-control" value="(Generated)" disabled>
                            </div>
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" name="created_at" class="form-control @error('created_at') is-invalid @enderror" value="{{ old('created_at', date('Y-m-d')) }}" placeholder="Tanggal">
                                <span class="error invalid-feedback">{{ $errors->first('created_at') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Nasabah</label>
                                <select class="form-control select2 @error('customer_id') is-invalid @enderror" name="customer_id" id="customer_id">
                                    <!-- AJAX loaded options -->
                                </select>
                                <span class="error invalid-feedback">{{ $errors->first('customer_id') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Saldo Tersedia (Rp)</label>
                                <input type="text" class="form-control" id="balance" name="balance" value="Rp0,00" placeholder="Saldo" readonly>
                            </div>
                            <div class="form-group">
                                <label>Nominal Penarikan (Rp)</label>
                                <input type="number" min="0" class="form-control @error('amount') is-invalid @enderror" name="amount" id="amount" value="{{ old('amount', 0) }}" placeholder="Nominal Penarikan">
                                <span class="error invalid-feedback" id="amount-error">{{ $errors->first('amount') }}</span>
                            </div>
                            <button type="submit" class="btn btn-success" id="btnSubmit">Simpan</button>
                        </form>
                    </div>
                </div>
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
    $(function() {
        var selectedCustomer = @json(old('customer_id') ? \App\Models\Customer::find(old('customer_id')) : (request('customer_id') ? \App\Models\Customer::find(request('customer_id')) : null));

        var selectEl = $('#customer_id').select2({
            theme: 'bootstrap4',
            placeholder: 'Cari Nama atau Nomor Rekening...',
            ajax: {
                url: '{{ route("customer.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results }; }
            }
        });

        $('#customer_id').on('change', function () {
            var customerId = $(this).val();
            if (customerId) {
                getCurrentBalance(customerId);
            } else {
                resetBalance();
            }
        });

        if (selectedCustomer) {
            var option = new Option(selectedCustomer.number + ' - ' + selectedCustomer.name, selectedCustomer.id, true, true);
            selectEl.append(option).trigger('change');
        }

        $('#amount').on('input', function() {
            var max = parseFloat($(this).attr('max')) || 0;
            var val = parseFloat($(this).val()) || 0;
            if (val > max) {
                $(this).addClass('is-invalid');
                $('#amount-error').text('Nominal penarikan tidak boleh melebihi saldo.').show();
                $('#btnSubmit').prop('disabled', true);
            } else if (val <= 0) {
                $(this).removeClass('is-invalid');
                if (max <= 0 && $('#customer_id').val()) {
                    $('#amount-error').text('Nasabah tidak memiliki saldo simpanan untuk ditarik.').show();
                    $('#btnSubmit').prop('disabled', true);
                } else {
                    $('#amount-error').hide();
                    $('#btnSubmit').prop('disabled', false);
                }
            } else {
                $(this).removeClass('is-invalid');
                $('#amount-error').hide();
                $('#btnSubmit').prop('disabled', false);
            }
        });
    });

    function getCurrentBalance(id) {
        var $balance = $('#balance');
        var $withdrawal = $('#amount');
        var $btnSubmit = $('#btnSubmit');
        var $errorBox = $('#amount-error');

        $balance.val('Memuat saldo...');

        fetch(`{{ url('/api/nasabah') }}/${id}/saldo`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Gagal mengambil data saldo');
            }
            return response.json();
        })
        .then(function(data) {
            if (data.status === 'success' && data.data) {
                var currentBalance = data.data.current_balance || 0;
                var formattedBalance = data.data.current_balance_formatted || ('Rp' + Number(currentBalance).toLocaleString('id-ID', { minimumFractionDigits: 2 }));

                $withdrawal.attr('max', currentBalance);
                $balance.val(formattedBalance);

                if (currentBalance <= 0) {
                    $errorBox.text('Nasabah tidak memiliki saldo simpanan untuk ditarik.').show();
                    $btnSubmit.prop('disabled', true);
                } else {
                    $errorBox.hide();
                    $btnSubmit.prop('disabled', false);
                }

                $withdrawal.trigger('input');
            } else {
                resetBalance();
                $errorBox.text(data.message || 'Gagal memuat saldo nasabah.').show();
                $btnSubmit.prop('disabled', true);
            }
        })
        .catch(function() {
            resetBalance();
            $errorBox.text('Terjadi kesalahan saat memuat saldo nasabah.').show();
            $btnSubmit.prop('disabled', true);
        });
    }

    function resetBalance() {
        $('#balance').val('Rp0,00');
        $('#amount').attr('max', 0);
        $('#amount-error').hide();
        $('#btnSubmit').prop('disabled', false);
    }
</script>
@endpush
