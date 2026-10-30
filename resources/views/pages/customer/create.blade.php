@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('customer.store') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label>Nomor Rekening</label>
                                <input type="text" class="form-control @error('number') is-invalid @enderror" name="number" value="{{ old('number') }}" placeholder="Nomor Rekening">
                                <span class="error invalid-feedback">{{ $errors->first('number') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Nama</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="Nama">
                                <span class="error invalid-feedback">{{ $errors->first('name') }}</span>
                            </div>
                            <div class="form-group">
                                <label>NIK</label>
                                <input type="text" class="form-control @error('nik') is-invalid @enderror" name="nik" id="nik" value="{{ old('nik') }}" placeholder="NIK (16 Digit)" maxlength="16">
                                <span class="error invalid-feedback">{{ $errors->first('nik') }}</span>
                                <div id="nik-info-alert" class="alert alert-info mt-2 py-2 px-3 d-none">
                                    <i class="fas fa-info-circle mr-1"></i> <span id="nik-info-message"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Daftar</label>
                                <input type="date" max="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" class="form-control @error('joined_at') is-invalid @enderror" name="joined_at" placeholder="Tanggal Daftar">
                                <span class="error invalid-feedback">{{ $errors->first('joined_at') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <select class="form-control @error('gender') is-invalid @enderror" name="gender">
                                    <option value="L" {{ old('gender') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('gender') == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                <span class="error invalid-feedback">{{ $errors->first('gender') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Lahir</label>
                                <input type="date" max="{{ date('Y-m-d') }}" class="form-control @error('birth') is-invalid @enderror" name="birth" value="{{ old('birth') }}" placeholder="Tanggal Lahir">
                                <span class="error invalid-feedback">{{ $errors->first('birth') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Alamat</label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror" name="address" value="{{ old('address') }}" placeholder="Alamat">
                                <span class="error invalid-feedback">{{ $errors->first('address') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Pekerjaan</label>
                                <input type="text" class="form-control @error('profession') is-invalid @enderror" name="profession" value="{{ old('profession') }}" placeholder="Pekerjaan">
                                <span class="error invalid-feedback">{{ $errors->first('profession') }}</span>
                            </div>
                            <div class="form-group">
                                <label>Pendidikan Terakhir</label>
                                <input type="text" class="form-control @error('last_education') is-invalid @enderror" name="last_education" value="{{ old('last_education') }}" placeholder="Pendidikan Terakhir">
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
                                    @endphp
                                    <option value="{{ $rate->id }}" {{ old('interest_rate_id') == $rate->id ? 'selected' : '' }}>
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

            let lastCheckedNik = '';
            $('input[name="nik"]').on('input change', function () {
                const nikVal = $(this).val().trim();
                if (nikVal.length === 16 && /^\d{16}$/.test(nikVal)) {
                    if (nikVal === lastCheckedNik) return;
                    lastCheckedNik = nikVal;

                    $.ajax({
                        url: "{{ route('customer.lookup-nik') }}",
                        type: "GET",
                        data: { nik: nikVal },
                        dataType: "json",
                        success: function (res) {
                            if (res.exists && res.data) {
                                const d = res.data;
                                if (d.name) $('input[name="name"]').val(d.name);
                                if (d.gender) $('select[name="gender"]').val(d.gender);
                                if (d.birth) $('input[name="birth"]').val(d.birth);
                                if (d.address) $('input[name="address"]').val(d.address);
                                if (d.profession) $('input[name="profession"]').val(d.profession);
                                if (d.last_education) $('input[name="last_education"]').val(d.last_education);
                                if (d.interest_rate_id) {
                                    $('select[name="interest_rate_id"]').val(d.interest_rate_id);
                                } else {
                                    $('select[name="interest_rate_id"]').val('');
                                }

                                $('input[name="number"]').val('');

                                const existingNum = d.existing_number ? ' (Rekening sebelumnya: ' + d.existing_number + ')' : '';
                                $('#nik-info-message').text('NIK ditemukan atas nama ' + d.name + existingNum + '. Data profil berhasil diisi otomatis. Silakan tentukan Nomor Rekening baru.');
                                $('#nik-info-alert').removeClass('d-none');
                            } else {
                                $('#nik-info-alert').addClass('d-none');
                            }
                        },
                        error: function () {
                            $('#nik-info-alert').addClass('d-none');
                        }
                    });
                } else {
                    lastCheckedNik = '';
                    $('#nik-info-alert').addClass('d-none');
                }
            });
        });

        function previewImage() {
            const cover = document.querySelector('.custom-file-input');
            const coverLabel = document.querySelector('.custom-file-label');
            coverLabel.textContent = cover.files[0].name;
        }
    </script>
@endpush
