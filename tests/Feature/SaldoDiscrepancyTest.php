<?php

namespace Tests\Feature;

use App\Http\Controllers\DepositController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportController;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\InterestRate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SaldoDiscrepancyTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teller;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::create([
            'name' => 'Manager Test',
            'username' => 'managertest',
            'password' => bcrypt('admin'),
            'role' => 'manager',
            'phone' => '08123456788',
        ]);

        $this->teller = User::create([
            'name' => 'Teller Test',
            'username' => 'tellertest',
            'password' => bcrypt('admin'),
            'role' => 'teller',
            'phone' => '08123456789',
        ]);

        $rate = InterestRate::create([
            'type' => 'simpanan',
            'rate_percent' => 4.0,
            'effective_date' => Carbon::now()->subYear(),
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'number' => 'NAS-001',
            'name' => 'Test Yogi',
            'nik' => '1234567890123456',
            'phone' => '081234567890',
            'address' => 'Jl. Test No. 1',
            'status' => 'active',
            'interest_rate_id' => $rate->id,
            'joined_at' => Carbon::now(),
        ]);
    }

    public function test_backdated_transaction_recalculates_running_balance_chronologically()
    {
        // 1. Input transaksi pertama dengan tanggal 25 Sep 2026 (amount 1000)
        $dep1 = Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'simpanan',
            'amount' => 1000,
            'previous_balance' => 0,
            'current_balance' => 0,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::parse('2026-09-25 10:00:00'),
        ]);
        Deposit::recalculateBalance($this->customer->id);

        $dep1->refresh();
        $this->assertEquals(0, $dep1->previous_balance);
        $this->assertEquals(1000, $dep1->current_balance);

        // 2. Input transaksi kedua berstatus backdated tanggal 24 Sep 2026 (amount 2000)
        // Karena diinput belakangan, ID dep2 > ID dep1
        $dep2 = Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'simpanan',
            'amount' => 2000,
            'previous_balance' => 0,
            'current_balance' => 0,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::parse('2026-09-24 10:00:00'),
        ]);
        Deposit::recalculateBalance($this->customer->id);

        $dep1->refresh();
        $dep2->refresh();

        $this->assertTrue($dep2->id > $dep1->id, 'ID transaksi backdated harus lebih besar');

        // Transaksi 24 Sep harus jadi transaksi ke-1
        $this->assertEquals(0, $dep2->previous_balance);
        $this->assertEquals(2000, $dep2->current_balance);

        // Transaksi 25 Sep harus bergeser jadi transaksi ke-2 dengan saldo 3000
        $this->assertEquals(2000, $dep1->previous_balance);
        $this->assertEquals(3000, $dep1->current_balance);
    }

    public function test_report_and_dashboard_use_chronological_latest_deposit_not_max_id()
    {
        // Setup 2 transaksi: dep1 (25 Sep, 1000) dan dep2 (24 Sep, 2000)
        $dep1 = Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'simpanan',
            'amount' => 1000,
            'previous_balance' => 0,
            'current_balance' => 0,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::parse('2026-09-25 10:00:00'),
        ]);
        $dep2 = Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'simpanan',
            'amount' => 2000,
            'previous_balance' => 0,
            'current_balance' => 0,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::parse('2026-09-24 10:00:00'),
        ]);
        Deposit::recalculateBalance($this->customer->id);

        // Verifikasi Dashboard HomeController totalTabungan
        $home = new HomeController();
        $homeView = $home->index();
        $this->assertEquals(3000, $homeView->getData()['totalTabungan'], 'Dashboard totalTabungan harus 3000 (bukan 2000 dari MAX(id))');

        // Verifikasi ReportController savingsRecap totalSaldo
        $report = new ReportController();
        $req = Request::create('/laporan/rekap-simpanan', 'GET');
        $reportView = $report->savingsRecap($req);
        $this->assertEquals(3000, $reportView->getData()['totalSaldo'], 'Report totalSaldo harus 3000 (bukan 2000 dari MAX(id))');

        // Verifikasi ReportController savingsRecap AJAX
        $reqAjax = Request::create('/laporan/rekap-simpanan', 'GET');
        $reqAjax->headers->set('X-Requested-With', 'XMLHttpRequest');
        $resAjax = $report->savingsRecap($reqAjax);
        $dataAjax = json_decode($resAjax->getContent(), true);

        $this->assertEquals(3000, $dataAjax['filtered_total_saldo']);
        $this->assertEquals('Rp 3.000', $dataAjax['data'][0]['saldo_simpanan']);
        $this->assertEquals(3000, $dataAjax['data'][0]['saldo_raw']);
    }

    public function test_receipt_view_displays_original_created_at_and_clarified_labels()
    {
        // Setup 2 transaksi
        $dep1 = Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'simpanan',
            'amount' => 1000,
            'previous_balance' => 0,
            'current_balance' => 0,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::parse('2026-09-25 10:00:00'),
        ]);
        $dep2 = Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'simpanan',
            'amount' => 2000,
            'previous_balance' => 0,
            'current_balance' => 0,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::parse('2026-09-24 10:00:00'),
        ]);
        Deposit::recalculateBalance($this->customer->id);
        $dep1->refresh();
        $dep2->refresh();

        $depController = new DepositController();

        // 1. Kwitansi dep2 (transaksi lampau/backdated 24 Sep)
        $view2 = $depController->receipt($dep2)->render();
        $this->assertStringContainsString('24 September 2026', $view2, 'Tanggal kwitansi harus tanggal transaksi asli, bukan tanggal cetak');
        $this->assertStringContainsString('Saldo Sebelumnya', $view2);
        $this->assertStringContainsString('Saldo Setelah Transaksi', $view2);
        $this->assertStringContainsString('Saldo Terkini Rekening', $view2, 'Kwitansi transaksi lampau harus menampilkan saldo terkini rekening');
        $this->assertStringContainsString('3.000', $view2);

        // 2. Kwitansi dep1 (transaksi terkini secara kronologis 25 Sep)
        $view1 = $depController->receipt($dep1)->render();
        $this->assertStringContainsString('25 September 2026', $view1);
        $this->assertStringContainsString('Saldo Setelah Transaksi', $view1);
        $this->assertStringNotContainsString('Saldo Terkini Rekening', $view1, 'Kwitansi transaksi terbaru tidak perlu baris saldo terkini tambahan');
    }

    public function test_index_views_contain_saldo_berjalan_column_header()
    {
        $this->actingAs($this->teller);
        $depIndexHtml = view('pages.transaction.deposit.index', [
            'title' => 'Simpanan',
            'customers' => [],
            'types' => [],
        ])->render();
        $this->assertStringContainsString('Saldo Berjalan', $depIndexHtml);
        $this->assertStringContainsString('Saldo berjalan pada saat transaksi terjadi', $depIndexHtml);

        $wdIndexHtml = view('pages.transaction.withdrawal.index', [
            'title' => 'Penarikan',
            'customers' => [],
        ])->render();
        $this->assertStringContainsString('Saldo Berjalan', $wdIndexHtml);
        $this->assertStringContainsString('Saldo berjalan pada saat transaksi terjadi', $wdIndexHtml);
    }
}
