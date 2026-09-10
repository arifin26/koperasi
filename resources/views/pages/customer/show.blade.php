@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 col-md-8">
                                <div class="form-group">
                                    <label>Nomor Rekening</label>
                                    <input type="text" class="form-control-plaintext @error('number') is-invalid @enderror"
                                        name="number" value="{{ old('number', $user->number) }}"
                                        placeholder="Nomor Rekening" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Nama</label>
                                    <input type="text" class="form-control-plaintext @error('name') is-invalid @enderror"
                                        name="name" value="{{ old('name', $user->name) }}" placeholder="Nama" disabled>
                                </div>
                                <div class="form-group">
                                    <label>NIK</label>
                                    <input type="text" class="form-control-plaintext @error('nik') is-invalid @enderror"
                                        name="nik" value="{{ old('nik', $user->nik) }}" placeholder="NIK" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Status Nasabah</label>
                                    <input type="text" class="form-control-plaintext"
                                        name="nik" value="{{ ucfirst($user->status) }}" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Daftar</label>
                                    <input type="text" class="form-control-plaintext"
                                        value="{{ Carbon\Carbon::parse($user->joined_at)->isoFormat('D MMMM Y') }}"
                                        disabled readonly>
                                </div>
                                <div class="form-group">
                                    <label>Jenis Kelamin</label>
                                    <input type="text" class="form-control-plaintext" value="{{ $user->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Lahir</label>
                                    <input type="text" class="form-control-plaintext"
                                        value="{{ Carbon\Carbon::parse($user->birth)->isoFormat('D MMMM Y') }}"
                                        disabled readonly>
                                </div>
                                <div class="form-group">
                                    <label>Nomor Telepon</label>
                                    <input type="text" class="form-control-plaintext @error('phone') is-invalid @enderror"
                                        name="phone" value="{{ old('phone', $user->phone) }}"
                                        placeholder="Nomor Telepon">
                                </div>
                                <div class="form-group">
                                    <label>Alamat</label>
                                    <input type="text" class="form-control-plaintext @error('address') is-invalid @enderror"
                                        name="address" value="{{ old('address', $user->address) }}" placeholder="Alamat">
                                </div>
                                <div class="form-group">
                                    <label>Pekerjaan</label>
                                    <input type="text" class="form-control-plaintext @error('profession') is-invalid @enderror"
                                        name="profession" value="{{ old('profession', $user->profession) }}"
                                        placeholder="Pekerjaan">
                                </div>
                                <div class="form-group">
                                    <label>Pendidikan Terakhir</label>
                                    <input type="text"
                                        class="form-control-plaintext @error('last_education') is-invalid @enderror"
                                        name="last_education" value="{{ old('last_education', $user->last_education) }}"
                                        placeholder="Pendidikan Terakhir">
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label>Foto</label>
                                <img src="<?= asset($user->photo ? 'storage/' . $user->photo : 'swamitra.jpeg') ?>" class="img-thumbnail img-preview"
                                     style="width: 100%;" alt="Foto">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-md-6 -->
        </div>
        <!-- /.row -->

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0"><i class="fas fa-wallet mr-2"></i> Ringkasan Saldo Simpanan</h3>
                        <div class="card-tools ml-auto">
                            <button type="button" class="btn btn-light btn-sm print-passbook-btn" data-type="bulk" data-url="{{ route('customer.passbook', $user) }}" data-title="Buku Tabungan - {{ $user->name }} ({{ $user->number }})">
                                <i class="fas fa-book mr-1 text-primary"></i> <strong>Print Buku Tabungan</strong>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-12 mb-3">
                                <div class="border p-3 rounded bg-light">
                                    <h6 class="text-muted mb-2">Total Saldo Simpanan</h6>
                                    <h4 class="mb-0 font-weight-bold text-primary">
                                        Rp {{ number_format($balances['simpanan'] ?? 0, 0, ',', '.') }}
                                    </h4>
                                    <small class="text-muted">Setoran simpanan + bunga &minus; penarikan</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.container-fluid -->
    <!-- /.content -->
@endsection
