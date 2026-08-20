@extends('layouts.receipt')

@section('receipt-title', 'KWITANSI PENARIKAN SIMPANAN')
@section('receipt-no', $code)

@section('sign-left-label', 'Penerima / Nasabah,')
@section('sign-left-name', $deposit->customer->name ?? '....................................')
@section('sign-date', $deposit->created_at->isoFormat('D MMMM Y'))
@section('sign-right-label', 'Petugas Kasir / Teller,')
@section('sign-right-name', $deposit->creator->name ?? auth()->user()->name ?? 'Teller')
@section('sign-right-role', 'Teller / Kasir')

@section('content')
<table class="content-table">
    <tr>
        <td class="label">Diserahkan Kepada</td>
        <td class="colon">:</td>
        <td class="value">
            <strong>{{ $deposit->customer->name ?? '-' }}</strong>
            <span style="color: #666; font-size: 6.5pt;">(No. Rek: {{ $deposit->customer->number ?? '-' }} | NIK: {{ $deposit->customer->nik ?? '-' }})</span>
        </td>
    </tr>
    <tr>
        <td class="label">Untuk Transaksi</td>
        <td class="colon">:</td>
        <td class="value"><strong>Penarikan Simpanan Harian (Sukarela)</strong></td>
    </tr>
    <tr>
        <td class="label">Tanggal & Waktu</td>
        <td class="colon">:</td>
        <td class="value">{{ $deposit->created_at->isoFormat('dddd, D MMMM Y - HH:mm') }} WIB</td>
    </tr>
    @if($deposit->notes)
    <tr>
        <td class="label">Keterangan</td>
        <td class="colon">:</td>
        <td class="value">{{ $deposit->notes }}</td>
    </tr>
    @endif
</table>

<!-- Amount Box -->
<div class="amount-box" style="background-color: #fff9f8; border-color: #d9534f;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 58%; vertical-align: middle;">
                <div style="font-size: 6.5pt; color: #555;">JUMLAH PENARIKAN:</div>
                <div class="amount-val" style="color: #d9534f;">Rp {{ number_format($deposit->amount, 2, ',', '.') }}</div>
                <div class="terbilang-text"># {{ $terbilang }} #</div>
            </td>
            <td style="width: 42%; vertical-align: middle; border-left: 1px dashed #d9534f; padding-left: 8px;">
                <table style="width: 100%; font-size: 6.8pt; border-collapse: collapse;">
                    <tr>
                        <td style="color: #555;">Saldo Sebelumnya</td>
                        <td style="text-align: right;">Rp {{ number_format($deposit->previous_balance, 2, ',', '.') }}</td>
                    </tr>
                    <tr style="font-weight: bold; color: #333;">
                        <td>Sisa Saldo</td>
                        <td style="text-align: right; color: #1a5632;">Rp {{ number_format($deposit->current_balance, 2, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
@endsection
