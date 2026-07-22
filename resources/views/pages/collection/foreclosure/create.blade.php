@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('collection.foreclosure.store') }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Penarikan</label>
                                        <input type="date" class="form-control @error('date') is-invalid @enderror" name="date" value="{{ old('date',date('Y-m-d')) }}" placeholder="Tanggal Daftar">
                                        <span class="error invalid-feedback">{{ $errors->first('date') }}</span>
                                    </div>
                                    <div class="form-group">
                                        <label>Nasabah</label>
                                        <select class="form-control @error('customer_id') is-invalid @enderror" name="customer_id">
                                            @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->number . ' - ' . $customer->name }}</option>
                                            @endforeach
                                        </select>
                                        <span class="error invalid-feedback">{{ $errors->first('customer_id') }}</span>
                                    </div>
                                    <div class="form-group">
                                        <label>Jaminan</label>
                                        <input type="hidden" name="collateral_id">
                                        <input type="hidden" name="collateral_amount">
                                        <input type="text" readonly class="form-control @error('collateral_id') is-invalid @enderror" name="collateral" value="{{ old('collateral_id') }}" placeholder="Jaminan">
                                        <span class="error invalid-feedback">{{ $errors->first('collateral_id') }}</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label>Sudah Dibayar (Rp)</label>
                                        <input type="text" class="form-control change-installment @error('paid_amount') is-invalid @enderror" name="paid_amount" readonly placeholder="Sisa Pembayaran">
                                        <span class="error invalid-feedback">{{ $errors->first('paid_amount') }}</span>
                                    </div>
                                    <div class="form-group">
                                        <label>Sisa Pembayaran (Rp)</label>
                                        <input type="number" min="0" class="form-control change-installment @error('remaining_amount') is-invalid @enderror" name="remaining_amount" value="{{ old('remaining_amount', 0) }}" placeholder="Sisa Pembayaran">
                                        <span class="error invalid-feedback">{{ $errors->first('remaining_amount') }}</span>
                                    </div>
                                    <div class="form-group">
                                        <label>Pengembalian (Rp)</label>
                                        <input type="number" min="0" class="form-control change-installment @error('return_amount') is-invalid @enderror" name="return_amount" value="{{ old('return_amount', 0) }}" placeholder="Sisa Pembayaran">
                                        <span class="error invalid-feedback">{{ $errors->first('return_amount') }}</span>
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

@push('script')
<script>
    $(function() {
        $('select[name=customer_id]').on('change', function() {
            customerId = $(this).val();
        });
    });
</script>
@endpush
