<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\InterestRate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rekap simpanan difilter dengan RENTANG tanggal (dari - sampai), bukan satu
 * tanggal "Per".
 *
 * Aturan yang diuji:
 *  - Saldo Simpanan  = posisi saldo per AKHIR rentang (saldo itu titik waktu)
 *  - Jml Transaksi   = transaksi DI DALAM rentang
 *  - Rentang terbalik ditukar, tidak menghasilkan laporan kosong
 */
class SavingsRecapRangeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Petugas Rekap',
            'username' => 'petugasrekap',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'phone' => '08123450011',
        ]);

        InterestRate::create([
            'type' => 'simpanan',
            'rate_percent' => 1.00,
            'effective_date' => '2026-01-01',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->customer = Customer::create([
            'number' => '210000001',
            'name' => 'Nasabah Rentang',
            'nik' => '3200000000000011',
            'address' => 'Jl. Test',
            'status' => 'active',
            'joined_at' => '2026-01-01',
        ]);

        // 3 setoran di tiga bulan berbeda, saldo berjalan 100rb -> 300rb -> 600rb
        foreach ([['2026-09-10', 100000, 100000], ['2026-10-10', 200000, 300000], ['2026-11-10', 300000, 600000]] as [$tgl, $jml, $saldo]) {
            Deposit::create([
                'customer_id' => $this->customer->id,
                'type' => 'simpanan',
                'amount' => $jml,
                'previous_balance' => $saldo - $jml,
                'current_balance' => $saldo,
                'created_at' => $tgl . ' 09:00:00',
            ]);
        }
    }

    private function print(array $params): string
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'), $params);

        $response->assertStatus(200);

        return $response->json('data.html');
    }

    public function test_balance_is_taken_at_end_of_range()
    {
        $html = $this->print(['tanggal_dari' => '2026-09-01', 'tanggal_sampai' => '2026-10-31']);

        // Saldo per 31 Okt = 300.000, bukan 600.000 (setoran November di luar rentang)
        $this->assertStringContainsString('300.000', $html);
        $this->assertStringNotContainsString('600.000', $html);
    }

    public function test_transaction_count_is_limited_to_the_range()
    {
        // Oktober saja: 1 transaksi
        $html = $this->print(['tanggal_dari' => '2026-10-01', 'tanggal_sampai' => '2026-10-31']);
        $this->assertStringContainsString('>1 kali<', $html);

        // Sep-Nov: 3 transaksi
        $html = $this->print(['tanggal_dari' => '2026-09-01', 'tanggal_sampai' => '2026-11-30']);
        $this->assertStringContainsString('>3 kali<', $html);
    }

    public function test_period_label_shows_the_range()
    {
        $html = $this->print(['tanggal_dari' => '2026-09-01', 'tanggal_sampai' => '2026-10-31']);

        $this->assertStringContainsString('1 September 2026 s.d. 31 Oktober 2026', $html);
        $this->assertStringNotContainsString('Semua Periode', $html);
    }

    public function test_only_end_date_behaves_like_the_old_per_date()
    {
        $html = $this->print(['tanggal_sampai' => '2026-10-31']);

        $this->assertStringContainsString('s.d. 31 Oktober 2026', $html);
        $this->assertStringContainsString('300.000', $html);
    }

    public function test_no_date_means_all_periods()
    {
        $html = $this->print([]);

        $this->assertStringContainsString('Semua Periode', $html);
        $this->assertStringContainsString('600.000', $html);
    }

    public function test_reversed_range_is_swapped_instead_of_returning_nothing()
    {
        // Validasi menolak dari > sampai, jadi yang diuji di sini adalah jalur
        // tabel (AJAX) yang menerima apa saja dari query string.
        // Controller memakai $request->ajax(), yang membaca header X-Requested-With.
        $response = $this->actingAs($this->user)
            ->get(route('report.savings-recap') . '?' . http_build_query([
                'tanggal_dari' => '2026-10-31',
                'tanggal_sampai' => '2026-10-01',
            ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('recordsTotal'));
        $this->assertSame('1 kali', $response->json('data.0.total_transaksi'));
    }

    public function test_validation_rejects_end_date_before_start_date()
    {
        $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'), [
                'tanggal_dari' => '2026-10-31',
                'tanggal_sampai' => '2026-10-01',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tanggal_sampai']);
    }
}
