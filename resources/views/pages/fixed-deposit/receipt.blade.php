@extends('layouts.receipt')

@section('receipt-title', 'TANDA TERIMA PEMBUKAAN DEPOSITO')
@section('receipt-no', $fixed_deposit->number)

@section('sign-left-label', 'Nasabah Deposan,')
@section('sign-left-name', $fixed_deposit->customer->name ?? '....................................')
@section('sign-date', $fixed_deposit->start_date->isoFormat('D MMMM Y'))
@section('sign-right-label', 'Pejabat / Kasir Koperasi,')
@section('sign-right-name', $fixed_deposit->creator->name ?? auth()->user()->name ?? 'Petugas')
@section('sign-right-role', 'Customer Service / Teller')

@if($fixed_deposit->validated_at)
@section('validation-strip')
<div class="validation-strip">
    <table class="validation-table">
        <tr>
            <td style="text-align: left; font-weight: bold; width: 60%;">PEMBUKAAN DEPOSITO</td>
            <td style="text-align: right; width: 40%; font-weight: bold;">Usr. {{ strtoupper($fixed_deposit->validator->name ?? auth()->user()->name) }}</td>
        </tr>
        <tr>
            <td style="text-align: left;">Rek.{{ $fixed_deposit->customer->number ?? '-' }} &nbsp; {{ $fixed_deposit->validated_at->format('d/m/Y H:i:s') }}</td>
            <td style="text-align: right; font-weight: bold;">Rp. {{ number_format($fixed_deposit->amount, 0, ',', '.') }}</td>
        </tr>
    </table>
</div>
@endsection
@endif

@section('content')
<table class="content-table">
    <tr>
        <td class="label">Nama Nasabah</td>
        <td class="colon">:</td>
        <td class="value">
            <strong>{{ $fixed_deposit->customer->name ?? '-' }}</strong>
            <span style="color: #666; font-size: 6.5pt;">(No. Rek: {{ $fixed_deposit->customer->number ?? '-' }} | Telp: {{ $fixed_deposit->customer->phone ?? '-' }})</span>
        </td>
    </tr>
    <tr>
        <td class="label">Tenor & Suku Bunga</td>
        <td class="colon">:</td>
        <td class="value">
            <strong>{{ $fixed_deposit->tenor_months }} Bulan ({{ $fixed_deposit->rate_percent }}% p.a.)</strong> 
            <span style="color: #1a5632; font-weight: bold; font-size: 6.5pt;">&bull; Est. Bunga: Rp {{ number_format($fixed_deposit->monthly_interest, 0, ',', '.') }}/bln</span>
        </td>
    </tr>
    <tr>
        <td class="label">Periode Deposito</td>
        <td class="colon">:</td>
        <td class="value">{{ $fixed_deposit->start_date->isoFormat('D MMM Y') }} s/d {{ $fixed_deposit->maturity_date->isoFormat('D MMM Y') }}</td>
    </tr>
    @if($fixed_deposit->notes)
    <tr>
        <td class="label">Catatan</td>
        <td class="colon">:</td>
        <td class="value">{{ $fixed_deposit->notes }}</td>
    </tr>
    @endif
</table>

<!-- Amount Box -->
<div class="amount-box" style="background-color: #f0f7ff; border-color: #007bff;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 58%; vertical-align: middle;">
                <div style="font-size: 6.5pt; color: #555;">NOMINAL PENEMPATAN DEPOSITO:</div>
                <div class="amount-val" style="color: #0056b3;">Rp {{ number_format($fixed_deposit->amount, 2, ',', '.') }}</div>
                <div class="terbilang-text"># {{ $terbilang }} #</div>
            </td>
            <td style="width: 42%; vertical-align: middle; border-left: 1px dashed #007bff; padding-left: 8px;">
                <table style="width: 100%; font-size: 6.8pt; border-collapse: collapse;">
                    <tr>
                        <td style="color: #555;">Tanggal Buka</td>
                        <td style="text-align: right;">{{ $fixed_deposit->start_date->isoFormat('DD-MM-Y') }}</td>
                    </tr>
                    <tr style="font-weight: bold; color: #d9534f;">
                        <td>Jatuh Tempo</td>
                        <td style="text-align: right;">{{ $fixed_deposit->maturity_date->isoFormat('DD-MM-Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
@endsection
