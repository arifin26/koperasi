@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title font-weight-bold">Detail Penarikan {{ $code }}</h3>
                        <div class="ml-auto">
                            <a href="{{ route('transaction.withdrawal.receipt', $deposit) }}" target="_blank" class="btn btn-secondary btn-sm mr-1">
                                <i class="fas fa-print mr-1"></i> Cetak Kwitansi
                            </a>
                            <a href="{{ route('transaction.withdrawal.index') }}" class="btn btn-default btn-sm">
                                <i class="fas fa-arrow-left mr-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label>Kode Transaksi</label>
                                    <input type="text" class="form-control-plaintext"
                                        value="{{ $code }}" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input type="text" class="form-control-plaintext"
                                        value="{{ $deposit->created_at->isoFormat('D MMMM Y') }}" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Nasabah</label>
                                    <input type="text" class="form-control-plaintext"
                                        value="{{ $deposit->customer->number . ' - ' . $deposit->customer->name }}"
                                        placeholder="Nasabah" disabled>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label>Nominal Penarikan (Rp)</label>
                                    <input type="text" class="form-control-plaintext"
                                        value="Rp{{ number_format($deposit->amount, 2, ',', '.') }}" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Saldo Sebelumnya (Rp)</label>
                                    <input type="text" class="form-control-plaintext"
                                        value="Rp{{ number_format($deposit->previous_balance, 2, ',', '.') }}" disabled>
                                </div>
                                <div class="form-group">
                                    <label>Saldo Akhir (Rp)</label>
                                    <input type="text" class="form-control-plaintext"
                                        value="Rp{{ number_format($deposit->current_balance, 2, ',', '.') }}" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-md-6 -->
        </div>
        <!-- /.row -->
    </div><!-- /.container-fluid -->
    <!-- /.content -->
@endsection
