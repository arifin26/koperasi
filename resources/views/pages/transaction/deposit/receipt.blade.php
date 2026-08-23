@extends('layouts.receipt')

@section('receipt-title', 'KWITANSI PENERIMAAN SIMPANAN')
@section('receipt-no', $code)

@section('sign-left-label', 'Penyetor / Nasabah,')
@section('sign-left-name', $deposit->customer->name ?? '....................................')
@section('sign-date', $deposit->created_at->isoFormat('D MMMM Y'))
@section('sign-right-label', 'Petugas Kasir / Teller,')
@section('sign-right-name', $deposit->creator->name ?? auth()->user()->name ?? 'Teller')
@section('sign-right-role', 'Teller / Kasir')

@if($deposit->validated_at)
@section('validation-strip')
<div class="validation-strip">
    <table class="validation-table">
        <tr>
            <td style="text-align: left; font-weight: bold; width: 60%;">PENYETORAN TUNAI</td>
            <td style="text-align: right; width: 40%; font-weight: bold;">Usr. {{ strtoupper($deposit->validator->name ?? auth()->user()->name) }}</td>
        </tr>
        <tr>
            <td style="text-align: left;">Rek.{{ $deposit->customer->number ?? '-' }} &nbsp; {{ $deposit->validated_at->format('d/m/Y H:i:s') }}</td>
            <td style="text-align: right; font-weight: bold;">Rp. {{ number_format($deposit->amount, 0, ',', '.') }}</td>
        </tr>
    </table>
</div>
@endsection
@endif

@section('content')
@php
    $typeLabel = match($deposit->type) {
        'bunga' => 'Bunga Simpanan',
        default => 'Simpanan',
    };
@endphp

<table class="content-table">
    <tr>
        <td class="label">Telah Diterima Dari</td>
        <td class="colon">:</td>
        <td class="value">
            <strong>{{ $deposit->customer->name ?? '-' }}</strong>
            <span style="color: #666; font-size: 6.5pt;">(No. Rek: {{ $deposit->customer->number ?? '-' }} | NIK: {{ $deposit->customer->nik ?? '-' }})</span>
        </td>
    </tr>
    <tr>
        <td class="label">Untuk Transaksi</td>
        <td class="colon">:</td>
        <td class="value"><strong>Setoran {{ $typeLabel }}</strong></td>
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
<div class="amount-box">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 58%; vertical-align: middle;">
                <div style="font-size: 6.5pt; color: #555;">JUMLAH SETORAN:</div>
                <div class="amount-val">Rp {{ number_format($deposit->amount, 2, ',', '.') }}</div>
                <div class="terbilang-text"># {{ $terbilang }} #</div>
            </td>
            <td style="width: 42%; vertical-align: middle; border-left: 1px dashed #1a5632; padding-left: 8px;">
                <table style="width: 100%; font-size: 6.8pt; border-collapse: collapse;">
                    <tr>
                        <td style="color: #555;">Saldo Sebelumnya</td>
                        <td style="text-align: right;">Rp {{ number_format($deposit->previous_balance, 2, ',', '.') }}</td>
                    </tr>
                    <tr style="font-weight: bold; color: #1a5632;">
                        <td>Saldo Akhir</td>
                        <td style="text-align: right;">Rp {{ number_format($deposit->current_balance, 2, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
@endsection
