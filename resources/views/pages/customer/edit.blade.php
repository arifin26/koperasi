@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('customer.update', $user) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="id" value="{{ $user->id }}">
                            <div class="form-group">
                                <label>Nomor Rekening</label>
                                <input type="text" class="form-control @error('number') is-invalid @enderror" name="number" value="{{ old('number', $user->number) }}" placeholder="Nomor Rekening">
                                <span class="error invalid-feedback">{{ $errors->first('number') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Nama</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" placeholder="Nama">
                                <span class="error invalid-feedback">{{ $errors->first('name') }}</span>
                            </div>
                            <div class="form-group">
                                <label>NIK</label>
                                <input type="text" class="form-control @error('nik') is-invalid @enderror" name="nik" value="{{ old('nik', $user->nik) }}" placeholder="NIK">
                                <span class="error invalid-feedback">{{ $errors->first('nik') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Status Nasabah</label>
                                <select class="form-control @error('status') is-invalid @enderror" name="status">
                                    <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="blacklist" {{ old('status', $user->status) == 'blacklist' ? 'selected' : '' }}>Blacklist</option>
                                </select>
                                <span class="error invalid-feedback">{{ $errors->first('status') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Daftar</label>
                                <input type="date" max="{{ date('Y-m-d') }}" class="form-control @error('joined_at') is-invalid @enderror" name="joined_at" value="{{ old('joined_at', date('Y-m-d', strtotime($user->joined_at))) }}" placeholder="Tanggal Daftar">
                                <span class="error invalid-feedback">{{ $errors->first('joined_at') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <select class="form-control @error('gender') is-invalid @enderror" name="gender">
                                    <option value="L" {{ old('gender', $user->gender) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('gender', $user->gender) == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                <span class="error invalid-feedback">{{ $errors->first('gender') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Lahir</label>
                                <input type="date" max="{{ date('Y-m-d') }}" class="form-control @error('birth') is-invalid @enderror" name="birth" value="{{ old('birth', $user->birth) }}" placeholder="Tanggal Lahir">
                                <span class="error invalid-feedback">{{ $errors->first('birth') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Nomor Telepon</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Nomor Telepon">
                                <span class="error invalid-feedback">{{ $errors->first('phone') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Alamat</label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror" name="address" value="{{ old('address', $user->address) }}" placeholder="Alamat">
                                <span class="error invalid-feedback">{{ $errors->first('address') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Pekerjaan</label>
                                <input type="text" class="form-control @error('profession') is-invalid @enderror" name="profession" value="{{ old('profession', $user->profession) }}" placeholder="Pekerjaan">
                                <span class="error invalid-feedback">{{ $errors->first('profession') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Pendidikan Terakhir</label>
                                <input type="text" class="form-control @error('last_education') is-invalid @enderror" name="last_education" value="{{ old('last_education', $user->last_education) }}" placeholder="Pendidikan Terakhir">
                                <span class="error invalid-feedback">{{ $errors->first('last_education') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Paket Bunga Simpanan Khusus <small class="text-muted">(Opsional)</small></label>
                                <select class="form-control @error('interest_rate_id') is-invalid @enderror" name="interest_rate_id">
                                    <option value="">-- Gunakan Rate Default Koperasi --</option>
                                    @foreach($savingsRates as $rate)
                                    @php
                                        $desc = $rate->notes ? ' — ' . $rate->notes : '';
                                        $isDef = $rate->is_default ? ' [Default Koperasi]' : '';
                                        $selected = (old('interest_rate_id', $user->interest_rate_id) == $rate->id) ? 'selected' : '';
                                    @endphp
                                    <option value="{{ $rate->id }}" {{ $selected }}>
                                        {{ number_format($rate->rate_percent, 2, ',', '.') }}% p.a.{{ $desc }}{{ $isDef }}
                                    </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Jika dikosongkan, nasabah akan menggunakan suku bunga default koperasi.</small>
                                <span class="error invalid-feedback">{{ $errors->first('interest_rate_id') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Foto</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input @error('photo') is-invalid @enderror" accept="image/*" name="photo" id="image">
                                        <label class="custom-file-label" for="image">Pilih Gambar</label>
                                    </div>
                                </div>
                                @error('photo')
                                <span class="text-danger text-sm">{{ $errors->first('photo') }}</span>
                                @enderror
                                <div class="form-text font-weight-lighter text-sm">Maksimal: 2048KB</div>
                            </div>
                            @if($user->photo)
                            <div class="mb-3">
                                <img src="<?= asset('storage/' . $user->photo) ?>" class="img-thumbnail img-preview" style="max-width:200px;" alt="Foto">
                            </div>
                            @endif
                            <button type="submit" class="btn btn-success">Simpan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(function () {
            $('#image').on('change', function () {
                previewImage();
            });
        });

        function previewImage() {
            const cover = document.querySelector('.custom-file-input');
            const coverLabel = document.querySelector('.custom-file-label');
            const imgPreview = document.querySelector('.img-preview');
            coverLabel.textContent = cover.files[0].name;
            const coverFile = new FileReader();
            coverFile.readAsDataURL(cover.files[0]);
            coverFile.onload = function (e) {
                if (imgPreview) imgPreview.src = e.target.result;
            }
        }
    </script>
@endpush
