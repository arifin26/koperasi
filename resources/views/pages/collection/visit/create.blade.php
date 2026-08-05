@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('collection.visit.store') }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Kunjungan</label>
                                        <input type="date" class="form-control @error('created_at') is-invalid @enderror" name="created_at" value="{{ old('created_at',date('Y-m-d')) }}" placeholder="Tanggal Daftar">
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
                                        <label>Sisa Pembayaran (Rp)</label>
                                        <input type="number" min="0" class="form-control change-installment @error('remaining_amount') is-invalid @enderror" name="remaining_amount" value="{{ old('remaining_amount', 0) }}" placeholder="Sisa Pembayaran">
                                        <span class="error invalid-feedback">{{ $errors->first('remaining_amount') }}</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label>Kolektor</label>
                                        <select class="form-control @error('user_id') is-invalid @enderror" name="user_id">
                                            @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                        <span class="error invalid-feedback">{{ $errors->first('user_id') }}</span>
                                    </div>
                                    <div class="form-group">
                                        <label>Keterangan</label>
                                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" placeholder="Keterangan" name="description" cols="30" rows="5">{{ old('description') }}</textarea>
                                        <span class="error invalid-feedback">{{ $errors->first('description') }}</span>
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
                url: '/api/nasabah/search',
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
    });
</script>
@endpush
