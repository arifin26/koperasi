<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyInterestAccumulation;
use App\Models\Deposit;
use App\Models\FixedDeposit;
use App\Models\Holiday;
use App\Models\InterestEngineLog;
use App\Models\InterestRate;
use App\Models\User;
use App\Services\DepositInterestService;
use App\Models\WorkdayYearCount;
use App\Services\SavingsInterestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengaman engine bunga otomatis — tiga aturan yang pernah bocor di produksi:
 * deposito jatuh tempo tetap dibayar, bunga bulan berjalan diposting lebih awal,
 * dan transaksi backdate tidak pernah dapat bunga.
 */
class InterestEngineGuardTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::create([
            'name' => 'Manager Test',
            'username' => 'managerguard',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'phone' => '08123450001',
        ]);

        InterestRate::create([
            'type' => 'simpanan',
            'rate_percent' => 6.00,
            'effective_date' => '2026-01-01',
            'is_active' => true,
            'is_default' => true,
            'created_by' => $this->manager->id,
        ]);

        $this->customer = Customer::create([
            'number' => 'NAS-GUARD-1',
            'name' => 'Nasabah Guard',
            'nik' => '3200000000000001',
            'address' => 'Jl. Test',
            'gender' => 'L',
            'birth' => '1990-01-01',
            'last_education' => 'S1',
            'profession' => 'Wiraswasta',
            'status' => 'active',
            'joined_at' => '2026-01-01',
        ]);
    }

    private function fixedDeposit(string $start, string $maturity, string $status = 'active'): FixedDeposit
    {
        return FixedDeposit::create([
            'number' => 'DEP-GUARD-' . substr($start, 0, 7) . '-' . $status,
            'customer_id' => $this->customer->id,
            'amount' => 120000000,
            'tenor_months' => 3,
            'rate_percent' => 12.00, // 1% per bulan -> Rp 1.200.000
            'start_date' => $start,
            'maturity_date' => $maturity,
            'status' => $status,
            'created_by' => $this->manager->id,
        ]);
    }

    /** Deposito yang sudah lewat bulan jatuh tempo tidak berbunga lagi. */
    public function test_matured_deposit_is_not_paid_interest_after_maturity_month()
    {
        $deposit = $this->fixedDeposit('2026-06-01', '2026-09-01', 'matured');

        app(DepositInterestService::class)->processDepositInterest('2026-10-01');

        $this->assertDatabaseMissing('deposit_interest_payments', [
            'fixed_deposit_id' => $deposit->id,
            'period' => '2026-10',
        ]);
    }

    /** Bulan jatuh tempo masih dibayar: tenor 3 bulan = 3 kali bunga. */
    public function test_maturity_month_is_still_paid()
    {
        $deposit = $this->fixedDeposit('2026-06-01', '2026-09-01');
        $service = app(DepositInterestService::class);

        foreach (['2026-06-01', '2026-07-01', '2026-08-01', '2026-09-01', '2026-10-01'] as $date) {
            $service->processDepositInterest($date);
        }

        // Bulan start (Juni) belum berbunga, Oktober sudah lewat jatuh tempo.
        $this->assertSame(
            ['2026-07', '2026-08', '2026-09'],
            $deposit->interestPayments()->orderBy('period')->pluck('period')->all()
        );
    }

    /** Akrual bulan yang belum tutup tidak boleh dibukukan ke buku nasabah. */
    public function test_current_month_accrual_is_not_posted_before_month_end()
    {
        Carbon::setTestNow('2026-11-20');

        Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'simpanan',
            'amount' => 50000000,
            'created_at' => '2026-10-01 08:00:00',
            'created_by' => $this->manager->id,
        ]);

        app(SavingsInterestService::class)->processSavingsInterest('2026-11-20', $this->manager->id);

        $accrued = DailyInterestAccumulation::where('calculation_date', '>=', '2026-11-01')->count();
        $this->assertGreaterThan(0, $accrued, 'bunga November harus terakumulasi');

        // Oktober sudah tutup -> dibukukan. November masih berjalan -> belum.
        $periods = Deposit::where('type', 'bunga')->pluck('notes')->implode(' ');
        $this->assertStringContainsString('Oktober 2026', $periods);
        $this->assertStringNotContainsString('November 2026', $periods);

        $this->assertSame(0, DailyInterestAccumulation::where('calculation_date', '>=', '2026-11-01')
            ->where('is_posted', 1)->count());

        Carbon::setTestNow();
    }

    /** Transaksi backdate membatalkan akrual sejak tanggalnya supaya dihitung ulang. */
    public function test_backdated_transaction_invalidates_later_accruals()
    {
        InterestEngineLog::create([
            'run_date' => '2026-11-10',
            'status' => 'success',
            'reason' => 'test',
        ]);

        DailyInterestAccumulation::create([
            'customer_id' => $this->customer->id,
            'savings_type' => 'simpanan',
            'calculation_date' => '2026-11-10',
            'base_balance' => 1000,
            'rate_percent' => 6.00,
            'interest_amount' => 1,
            'is_posted' => 0,
        ]);

        Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'simpanan',
            'amount' => 7000000,
            'created_at' => '2026-11-05 09:00:00',
            'created_by' => $this->manager->id,
        ]);

        $this->assertDatabaseMissing('interest_engine_logs', ['run_date' => '2026-11-10']);
        $this->assertSame(0, DailyInterestAccumulation::where('calculation_date', '2026-11-10')->count());
    }

    /** Sabtu ikut hari kerja; hanya Minggu dan hari libur yang tidak. */
    public function test_saturday_counts_as_workday_and_only_sunday_is_weekly_off()
    {
        // 2026-11-07 Sabtu, 2026-11-08 Minggu
        $this->assertTrue(Holiday::isWorkday(Carbon::parse('2026-11-07')), 'Sabtu harus hari kerja');
        $this->assertFalse(Holiday::isWorkday(Carbon::parse('2026-11-08')), 'Minggu harus libur');

        // Hari merah dari Manajemen Hari Libur mengalahkan hari kerja biasa.
        Holiday::create([
            'date' => '2026-11-07',
            'year' => 2026,
            'name' => 'Libur Uji',
            'type' => 'holiday',
        ]);

        $this->assertFalse(Holiday::isWorkday(Carbon::parse('2026-11-07')));
    }

    /** Pembagi tahunan ikut Sabtu: 2026 = 365 hari - 52 Minggu - 1 libur. */
    public function test_workday_count_includes_saturdays()
    {
        Holiday::create([
            'date' => '2026-08-17',
            'year' => 2026,
            'name' => 'HUT RI',
            'type' => 'holiday',
        ]);

        // 2026 punya 365 hari dan 52 hari Minggu; 17 Agustus 2026 bukan Minggu.
        $this->assertSame(365 - 52 - 1, WorkdayYearCount::recalculate(2026));
    }

    /** Bunga Oktober diposting 5 November, bukan akhir Oktober atau tanggal 1. */
    public function test_month_is_posted_on_posting_day_of_next_month()
    {
        Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'simpanan',
            'amount' => 50000000,
            'created_at' => '2026-10-01 08:00:00',
            'created_by' => $this->manager->id,
        ]);

        // 31 Oktober: bulan belum berganti -> belum boleh posting.
        Carbon::setTestNow('2026-10-31');
        app(SavingsInterestService::class)->processSavingsInterest('2026-10-31', $this->manager->id);
        $this->assertSame(0, Deposit::where('type', 'bunga')->count(), '31 Oktober belum boleh posting');

        // Tanggal 1-4 November belum waktunya: posting jatuh tanggal 5.
        Carbon::setTestNow('2026-11-04');
        app(SavingsInterestService::class)->processSavingsInterest('2026-11-04', $this->manager->id);
        $this->assertSame(0, Deposit::where('type', 'bunga')->count(), 'sebelum tanggal 5 belum posting');

        // 5 November 2026 (Kamis, hari kerja) -> diposting, bertanggal 5 November.
        Carbon::setTestNow('2026-11-05');
        app(SavingsInterestService::class)->processSavingsInterest('2026-11-05', $this->manager->id);

        $bunga = Deposit::where('type', 'bunga')->get();
        $this->assertCount(1, $bunga);
        $this->assertSame('2026-11-05', Carbon::parse($bunga->first()->created_at)->format('Y-m-d'));
        $this->assertStringContainsString('Oktober 2026', $bunga->first()->notes);

        $this->assertDatabaseHas('interest_posting_logs', ['period' => '2026-10', 'status' => 'success']);

        Carbon::setTestNow();
    }

    /** Kalau tanggal 5 jatuh hari Minggu atau hari merah, posting digeser. */
    public function test_posting_day_shifts_when_it_falls_on_sunday_or_holiday()
    {
        // Periode Juni 2026 -> posting 5 Juli 2026, yang jatuh hari Minggu.
        $this->assertSame(
            'Sunday',
            Carbon::parse('2026-07-05')->format('l'),
            'prasyarat test: 5 Juli 2026 memang hari Minggu'
        );

        $this->assertSame(
            '2026-07-06',
            SavingsInterestService::postingDateFor(Carbon::parse('2026-06-01'))->format('Y-m-d')
        );

        // Hari merah di Manajemen Hari Libur juga menggeser.
        Holiday::create([
            'date' => '2026-11-05',
            'year' => 2026,
            'name' => 'Libur Uji',
            'type' => 'holiday',
        ]);

        $this->assertSame(
            '2026-11-06',
            SavingsInterestService::postingDateFor(Carbon::parse('2026-10-01'))->format('Y-m-d')
        );
    }
}
