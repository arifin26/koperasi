@extends('layouts.receipt')

@section('receipt-title', 'BUKTI PENGHAPUSAN SIMPANAN')
@section('receipt-no', $code)

@section('sign-left-label', 'Nasabah / Penyimpan,')
@section('sign-left-name', $deposit->customer->name ?? '....................................')
@section('sign-date', \Carbon\Carbon::now()->isoFormat('D MMMM Y'))
@section('sign-right-label', 'Petugas / Teller,')
@section('sign-right-name', $deletedBy ?? auth()->user()->name ?? 'Teller')
@section('sign-right-role', 'Teller / Kasir')

@section('content')
@php
    $typeLabel = match($deposit->type) {
        'bunga' => 'Bunga Simpanan',
        default => 'Simpanan',
    };
@endphp

<table class="content-table">
    <tr>
        <td class="label">Nasabah</td>
        <td class="colon">:</td>
        <td class="value">
            <strong>{{ $deposit->customer->name ?? '-' }}</strong>
            <span style="color: #666; font-size: 6.5pt;">(No. Rek: {{ $deposit->customer->number ?? '-' }} | NIK: {{ $deposit->customer->nik ?? '-' }})</span>
        </td>
    </tr>
    <tr>
        <td class="label">Jenis Transaksi</td>
        <td class="colon">:</td>
        <td class="value"><strong>{{ $typeLabel }}</strong></td>
    </tr>
    <tr>
        <td class="label">Tgl. Transaksi / Hapus</td>
        <td class="colon">:</td>
        <td class="value">{{ $deposit->created_at->isoFormat('D MMM Y HH:mm') }} / <span style="color: #dc3545; font-weight: bold;">{{ \Carbon\Carbon::now()->isoFormat('D MMM Y HH:mm') }}</span></td>
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
<div class="amount-box" style="background-color: #fff5f5; border-color: #dc3545;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 60%; vertical-align: middle;">
                <div style="font-size: 6.5pt; color: #555;">NOMINAL TRANSAKSI DIHAPUS:</div>
                <div class="amount-val" style="color: #dc3545;">Rp {{ number_format($deposit->amount, 2, ',', '.') }}</div>
                <div class="terbilang-text"># {{ $terbilang }} #</div>
            </td>
            <td style="width: 40%; vertical-align: middle; border-left: 1px dashed #dc3545; padding-left: 8px;">
                <div style="background-color: #dc3545; color: #fff; text-align: center; padding: 2px 5px; border-radius: 3px; font-weight: bold; font-size: 7.5pt; letter-spacing: 0.5px;">
                    TRANSAKSI DIHAPUS
                </div>
                <div style="font-size: 6.2pt; color: #888; margin-top: 2px; text-align: center;">
                    Bukti penghapusan dari sistem (non-tunai).
                </div>
            </td>
        </tr>
    </table>
</div>
@endsection
