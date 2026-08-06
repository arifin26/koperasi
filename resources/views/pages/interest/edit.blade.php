@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Rate Bunga</h3>
            </div>
            <form action="{{ route('interest.update', $interest->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label>Jenis Simpanan/Deposito <span class="text-danger">*</span></label>
                        <select name="type" class="form-control @error('type') is-invalid @enderror" required>
                            <option value="tabungan_sukarela" {{ $interest->type == 'tabungan_sukarela' || $interest->type == 'simpanan' ? 'selected' : '' }}>Simpanan</option>
                            <option value="deposito" {{ $interest->type == 'deposito' || strpos($interest->type, 'deposito_') === 0 ? 'selected' : '' }}>Deposito</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Rate (% p.a.) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0.01" max="100" name="rate_percent" class="form-control @error('rate_percent') is-invalid @enderror" value="{{ old('rate_percent', $interest->rate_percent) }}" required placeholder="Contoh: 4.50">
                            <div class="input-group-append">
                                <span class="input-group-text">% p.a.</span>
                            </div>
                        </div>
                        <small class="form-text text-muted">Gunakan titik untuk desimal. Contoh: 4.5</small>
                        @error('rate_percent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Tanggal Mulai Berlaku <span class="text-danger">*</span></label>
                        <input type="date" name="effective_date" class="form-control @error('effective_date') is-invalid @enderror" value="{{ old('effective_date', $interest->effective_date->format('Y-m-d')) }}" required>
                        @error('effective_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Keterangan Tambahan / SK</label>
                        <textarea name="notes" class="form-control">{{ old('notes', $interest->notes) }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('interest.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary float-right">Update Rate Bunga</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
