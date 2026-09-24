@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-7">
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
                        <select name="type" id="interest_type" class="form-control @error('type') is-invalid @enderror" required>
                            <option value="simpanan" {{ old('type', $interest->type) == 'simpanan' ? 'selected' : '' }}>Simpanan</option>
                            <option value="deposito" {{ old('type', $interest->type) == 'deposito' ? 'selected' : '' }}>Deposito</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Rate (% p.a.) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="rate_percent" class="form-control @error('rate_percent') is-invalid @enderror" value="{{ old('rate_percent', $interest->rate_percent) }}" required placeholder="Contoh: 4.50">
                            <div class="input-group-append">
                                <span class="input-group-text">% p.a.</span>
                            </div>
                        </div>
                        <small class="form-text text-muted">Gunakan titik untuk desimal. Contoh: 4.5</small>
                        @error('rate_percent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Tanggal Mulai Berlaku <span class="text-danger">*</span></label>
                        <input type="date" name="effective_date" class="form-control @error('effective_date') is-invalid @enderror" value="{{ old('effective_date', $interest->effective_date ? $interest->effective_date->format('Y-m-d') : '') }}" required>
                        @error('effective_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Keterangan Tambahan / SK</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Contoh: SK Pengurus No. 12/2026">{{ old('notes', $interest->notes) }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group mb-2" id="default_savings_wrapper">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_default" name="is_default" value="1" {{ old('is_default', $interest->is_default) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_default">Jadikan sebagai Rate Default Simpanan Koperasi</label>
                        </div>
                        <small class="form-text text-muted">Rate default akan digunakan untuk nasabah yang tidak memiliki rate khusus.</small>
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $interest->is_active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Status Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('interest.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary float-right">
                        <i class="fas fa-save mr-1"></i> Update Rate Bunga
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
    function toggleDefaultCheckbox() {
        if ($('#interest_type').val() === 'simpanan') {
            $('#default_savings_wrapper').slideDown();
        } else {
            $('#default_savings_wrapper').slideUp();
            $('#is_default').prop('checked', false);
        }
    }

    $('#interest_type').on('change', toggleDefaultCheckbox);
    toggleDefaultCheckbox();
});
</script>
@endpush
