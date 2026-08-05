@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Hari Libur</h3>
            </div>
            <form action="{{ route('holiday.update', $holiday) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label>Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', $holiday->date->format('Y-m-d')) }}" required>
                        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Nama Libur / Event <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $holiday->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Tipe <span class="text-danger">*</span></label>
                        <select name="type" class="form-control @error('type') is-invalid @enderror" required>
                            <option value="holiday" {{ old('type', $holiday->type) == 'holiday' ? 'selected' : '' }}>Hari Libur (Merah)</option>
                            <option value="workday" {{ old('type', $holiday->type) == 'workday' ? 'selected' : '' }}>Hari Kerja Pengganti (Sabtu/Minggu masuk)</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="description" class="form-control">{{ old('description', $holiday->description) }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('holiday.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary float-right">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
