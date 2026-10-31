<section class="savings-recap-print-document">
    <!-- Header Kop Resmi -->
    <table style="width: 100%; border-collapse: collapse; border-bottom: 3px double #000; padding-bottom: 4px; margin-bottom: 10px;">
        <tr>
            <td style="width: 65px; vertical-align: middle; text-align: left; padding: 0;">
                <img src="{{ asset('image/LOGO KOPERASI.png') }}" alt="Logo Koperasi" style="width: 58px; height: 58px;">
            </td>
            <td style="vertical-align: middle; text-align: center; padding: 0 10px;">
                <div style="font-size: 13pt; font-weight: bold; letter-spacing: 0.5px; line-height: 1.15; color: #000; font-family: 'Times New Roman', Times, serif;">KOPERASI UNIT DESA &ldquo; TANI JAYA &rdquo;</div>
                <div style="font-size: 10.5pt; font-weight: bold; letter-spacing: 0.5px; line-height: 1.2; color: #000; margin-top: 2px; font-family: 'Times New Roman', Times, serif;">UNIT SIMPAN PINJAM</div>
                <div style="font-size: 8.5pt; font-weight: bold; line-height: 1.2; color: #000; margin-top: 2px;">Di Gadungan - Kec. Puncu - Kediri Propinsi Jawa Timur</div>
                <div style="font-size: 7.5pt; line-height: 1.2; color: #000; margin-top: 1px;">Badan Hukum No. 4428/BH/II/80. Tanggal 23 September 1996</div>
                <div style="font-size: 7.5pt; line-height: 1.2; color: #000; margin-top: 1px;">Telp. (0354) 393063</div>
            </td>
            <td style="width: 65px; vertical-align: middle; padding: 0;"></td>
        </tr>
    </table>

    <div style="text-align: center; margin-bottom: 8px;">
        <div style="font-size: 11pt; font-weight: bold; text-transform: uppercase; color: #000;">REKAP SIMPANAN NASABAH</div>
        <div style="font-size: 8pt; font-weight: bold; color: #000; margin-top: 2px;">Periode: {{ $periodeLabel ?? 'Semua Periode' }}</div>
    </div>

    <!-- Meta Info -->
    <table class="savings-recap-print-meta">
        <tr>
            <td class="meta-label">Dicetak Oleh</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">{{ $user->name ?? '-' }} ({{ ucfirst($user->role ?? 'Teller') }})</td>
            <td class="meta-label">Tanggal Cetak</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y HH:mm') }} WIB</td>
        </tr>
        <tr>
            <td class="meta-label">Filter Status</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">
                @if($statusFilter == 'active')
                    Nasabah Aktif
                @elseif($statusFilter == 'blacklist')
                    Nasabah Blacklist
                @else
                    Semua Status
                @endif
            </td>
            <td class="meta-label">Total Nasabah</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">{{ count($data) }} Orang</td>
        </tr>
    </table>

    <!-- Data Table -->
    <table class="savings-recap-print-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">No. Rekening</th>
                <th style="width: 16%;">Nama Nasabah</th>
                <th style="width: 28%;">Alamat</th>
                <th style="width: 9%;">Jml Transaksi</th>
                <th style="width: 22%;">Saldo Simpanan (Rp)</th>
                <th style="width: 11%;">Bunga/Bulan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $i => $customer)
            @php
                $lastDeposit = $customer->last_deposit ?? null;
                $saldo = $lastDeposit ? ($lastDeposit->current_balance ?? 0) : 0;
                $rate = $customer->interestRate->rate_percent ?? 0;
                $bungaBulan = floor($saldo * ($rate / 100) / 12);
            @endphp
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td class="text-center">{{ $customer->number ?? '-' }}</td>
                <td><strong>{{ $customer->name ?? '-' }}</strong></td>
                <td>{{ $customer->address ?? '-' }}</td>
                <td class="text-center">{{ $customer->deposits_count ?? 0 }} kali</td>
                <td class="text-right"><strong>Rp {{ number_format($saldo, 0, ',', '.') }}</strong></td>
                <td class="text-right">Rp {{ number_format($bungaBulan, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 15px; color: #000;">Tidak ada data nasabah yang sesuai.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Summary Section -->
    <div class="savings-recap-print-summary print-avoid-break">
        <table style="width: 100%; border-collapse: collapse; margin-top: 8px;">
            <tr class="summary-row">
                <td colspan="5" class="text-right" style="border: 1px solid #000; padding: 6px; background-color: #f2f2f2; font-weight: bold; color: #000;">TOTAL DANA SIMPANAN NASABAH</td>
                <td class="text-right" style="border: 1px solid #000; padding: 6px; background-color: #f2f2f2; font-weight: bold; color: #000;">Rp {{ number_format($totalSaldo, 0, ',', '.') }}</td>
                <td style="border: 1px solid #000; padding: 6px; background-color: #f2f2f2; color: #000;"></td>
            </tr>
            <tr class="summary-row">
                <td colspan="5" class="text-right" style="border: 1px solid #000; padding: 6px; background-color: #f2f2f2; font-weight: bold; color: #000;">TOTAL BUNGA DALAM 1 BULAN</td>
                <td style="border: 1px solid #000; padding: 6px; background-color: #f2f2f2; color: #000;"></td>
                <td class="text-right" style="border: 1px solid #000; padding: 6px; background-color: #f2f2f2; font-weight: bold; color: #000;">
                    @php
                        $totalBungaBulan = 0;
                        foreach ($data as $customer) {
                            $lastDep = $customer->last_deposit ?? null;
                            $saldoCust = $lastDep ? ($lastDep->current_balance ?? 0) : 0;
                            $rateCust = $customer->interestRate->rate_percent ?? 0;
                            $totalBungaBulan += floor($saldoCust * ($rateCust / 100) / 12);
                        }
                    @endphp
                    Rp {{ number_format($totalBungaBulan, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Signatures -->
    <table class="savings-recap-print-signature print-avoid-break">
        <tr>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <div>Dibuat oleh,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $user->name ?? '....................................' }}</div>
                <div class="sign-title">{{ ucfirst($user->role ?? 'Teller') }}</div>
            </td>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <div>Kediri, {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</div>
                <div style="margin-top: 1px;">Diketahui oleh,</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $manager->name ?? '....................................' }}</div>
                <div class="sign-title">Manajer</div>
            </td>
        </tr>
    </table>

    <div class="savings-recap-print-footer">
        * Dokumen ini dicetak otomatis dari Sistem Informasi Koperasi pada {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y HH:mm:ss') }} WIB dan merupakan dokumen laporan yang sah.
    </div>
</section>
