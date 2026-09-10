@extends('layouts.receipt')

@section('receipt-title', 'BUKTI PENGHAPUSAN DEPOSITO')
@section('receipt-no', $fixed_deposit->number)

@section('sign-left-label', 'Nasabah Deposan,')
@section('sign-left-name', $fixed_deposit->customer->name ?? '....................................')
@section('sign-date', \Carbon\Carbon::now()->isoFormat('D MMMM Y'))
@section('sign-right-label', 'Petugas / Kasir Koperasi,')
@section('sign-right-name', $deletedBy ?? auth()->user()->name ?? 'Petugas')
@section('sign-right-role', 'Customer Service / Teller')

@section('content')
<table class="content-table">
    <tr>
        <td class="label">Nasabah</td>
        <td class="colon">:</td>
        <td class="value">
            <strong>{{ $fixed_deposit->customer->name ?? '-' }}</strong>
            <span style="color: #000; font-size: 6.5pt;">(No. Rek: {{ $fixed_deposit->customer->number ?? '-' }} | NIK: {{ $fixed_deposit->customer->nik ?? '-' }})</span>
        </td>
    </tr>
    <tr>
        <td class="label">No. Deposito / Tenor</td>
        <td class="colon">:</td>
        <td class="value"><strong>{{ $fixed_deposit->number }}</strong> ({{ $fixed_deposit->tenor_months }} Bulan &bull; {{ $fixed_deposit->rate_percent }}% p.a.)</td>
    </tr>
    <tr>
        <td class="label">Tgl. Buka / Hapus</td>
        <td class="colon">:</td>
        <td class="value">{{ $fixed_deposit->start_date->isoFormat('D MMM Y') }} / <span style="color: #000; font-weight: bold;">{{ \Carbon\Carbon::now()->isoFormat('D MMM Y HH:mm') }}</span></td>
    </tr>
    @if($fixed_deposit->notes)
    <tr>
        <td class="label">Keterangan</td>
        <td class="colon">:</td>
        <td class="value">{{ $fixed_deposit->notes }}</td>
    </tr>
    @endif
</table>

<!-- Amount Box -->
<div class="amount-box" style="background-color: #f8f8f8; border-color: #000;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 60%; vertical-align: middle;">
                <div style="font-size: 6.5pt; color: #000;">NOMINAL DEPOSITO DIHAPUS:</div>
                <div class="amount-val" style="color: #000;">Rp {{ number_format($fixed_deposit->amount, 2, ',', '.') }}</div>
                <div class="terbilang-text"># {{ $terbilang }} #</div>
            </td>
            <td style="width: 40%; vertical-align: middle; border-left: 1px dashed #000; padding-left: 8px;">
                <div style="background-color: #000; color: #fff; text-align: center; padding: 2px 5px; border-radius: 3px; font-weight: bold; font-size: 7.5pt; letter-spacing: 0.5px;">
                    DEPOSITO DIHAPUS
                </div>
                <div style="font-size: 6.2pt; color: #000; margin-top: 2px; text-align: center;">
                    Bukti penghapusan dari sistem (non-tunai).
                </div>
            </td>
        </tr>
    </table>
</div>
@endsection
