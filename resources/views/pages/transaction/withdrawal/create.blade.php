@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('transaction.withdrawal.store') }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label>Kode Transaksi</label>
                                        <input type="hidden" name="type" value="penarikan">
                                        <input type="text" class="form-control"
                                            value="(Generated)" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Tanggal</label>
                                        <input type="date" name="created_at" class="form-control @error('created_at') is-invalid @enderror" value="{{ old('created_at',date('Y-m-d')) }}" placeholder="Tanggal">
                                        <span class="error invalid-feedback">{{ $errors->first('created_at') }}</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label>Nasabah</label>
                                        <select class="form-control select2 @error('customer_id') is-invalid @enderror" name="customer_id" id="customer_id">
                                            <!-- AJAX loaded options -->
                                        </select>
                                        <span class="error invalid-feedback">{{ $errors->first('customer_id') }}</span>
                                    </div>
                                    <div class="form-group">
                                        <label>Saldo (Rp)</label>
                                        <input type="text" class="form-control" name="balance" value="Rp0,00" placeholder="Saldo" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Nominal Penarikan (Rp)</label>
                                        <input type="number" min="0" class="form-control @error('amount') is-invalid @enderror" name="amount" id="amount" value="{{ old('amount', 0) }}" placeholder="Nominal Penarikan">
                                        <span class="error invalid-feedback" id="amount-error">{{ $errors->first('amount') }}</span>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">Simpan</button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.col-md-6 -->
        </div>
        <!-- /.row -->
    </div><!-- /.container-fluid -->
    <!-- /.content -->
@endsection

@push('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet" />
@endpush

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function() {
        $('#customer_id').select2({
            theme: 'bootstrap4',
            placeholder: 'Cari Nama atau Nomor Rekening...',
            ajax: {
                url: '{{ route("customer.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data.results };
                }
            }
        });

        $('#customer_id').on('select2:select', function (e) {
            let customerId = e.params.data.id;
            getCurrentBalance(customerId);
        });

        $('#amount').on('input', function() {
            let max = parseFloat($(this).attr('max')) || 0;
            let val = parseFloat($(this).val()) || 0;
            
            if (val > max) {
                $(this).addClass('is-invalid');
                $('#amount-error').text('Nominal penarikan tidak boleh melebihi saldo.').show();
                $('button[type=submit]').prop('disabled', true);
            } else {
                $(this).removeClass('is-invalid');
                $('#amount-error').hide();
                $('button[type=submit]').prop('disabled', false);
            }
        });
    });

    function getCurrentBalance(id) {
        const _balance = $('input:text[name=balance]');
        const _withdrawal = $('input[name=amount]');
        fetch(`{{ url('/api/nasabah') }}/${id}/saldo`)
            .then(response => response.json())
            .then(data => {
                _withdrawal.attr('max', data.data.current_balance);
                _balance.val(data.data.current_balance_formatted);
                _withdrawal.trigger('input'); // re-trigger validation
            });
    }

</script>
@endpush
