@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Tambah Rate Bunga Baru</h3>
            </div>
            <form action="{{ route('interest.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Menyimpan rate baru akan secara otomatis menonaktifkan rate lama untuk jenis yang sama.
                    </div>
                    <div class="form-group">
                        <label>Jenis Simpanan/Deposito <span class="text-danger">*</span></label>
                        <select name="type" class="form-control @error('type') is-invalid @enderror" required>
                            <option value="tabungan_sukarela">Simpanan Harian</option>
                            <option value="deposito_3_bulan">Deposito 3 Bulan</option>
                            <option value="deposito_6_bulan">Deposito 6 Bulan</option>
                            <option value="deposito_12_bulan">Deposito 12 Bulan</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Rate (% p.a.) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0.01" max="100" name="rate_percent" class="form-control @error('rate_percent') is-invalid @enderror" value="{{ old('rate_percent') }}" required placeholder="Contoh: 4.50">
                            <div class="input-group-append">
                                <span class="input-group-text">% p.a.</span>
                            </div>
                        </div>
                        <small class="form-text text-muted">Gunakan titik untuk desimal. Contoh: 4.5</small>
                        @error('rate_percent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Tanggal Mulai Berlaku <span class="text-danger">*</span></label>
                        <input type="date" name="effective_date" class="form-control @error('effective_date') is-invalid @enderror" value="{{ old('effective_date', date('Y-m-d')) }}" required>
                        @error('effective_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Keterangan Tambahan / SK</label>
                        <textarea name="notes" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('interest.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary float-right">Simpan Rate Baru</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
