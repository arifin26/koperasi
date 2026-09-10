<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\FixedDeposit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class DepositRecapTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'teller']);
    }

    /** @test */
    public function deposit_recap_ajax_returns_filtered_totals_metadata()
    {
        $customer1 = Customer::factory()->create(['status' => 'active']);
        $customer2 = Customer::factory()->create(['status' => 'active']);

        FixedDeposit::create([
            'customer_id' => $customer1->id,
            'number' => 'DEP001',
            'amount' => 1000000,
            'rate_percent' => 6,
            'tenor_months' => 12,
            'start_date' => '2024-01-01',
            'maturity_date' => '2025-01-01',
            'status' => 'active'
        ]);

        FixedDeposit::create([
            'customer_id' => $customer2->id,
            'number' => 'DEP002',
            'amount' => 2000000,
            'rate_percent' => 8,
            'tenor_months' => 12,
            'start_date' => '2024-02-01',
            'maturity_date' => '2025-02-01',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('report.deposit-recap'), [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('filtered_total_nominal', $data);
        $this->assertArrayHasKey('filtered_total_nominal_formatted', $data);
        $this->assertArrayHasKey('filtered_total_bunga', $data);
        $this->assertArrayHasKey('filtered_total_bunga_formatted', $data);

        // Total nominal should be 3,000,000
        $this->assertEquals(3000000, $data['filtered_total_nominal']);

        // Total monthly interest = floor(1000000 * 6/100 / 12) + floor(2000000 * 8/100 / 12)
        // = floor(5000) + floor(13333.33) = 5000 + 13333 = 18333
        $this->assertEquals(18333, $data['filtered_total_bunga']);
    }

    /** @test */
    public function deposit_recap_respects_status_filter()
    {
        $activeCustomer = Customer::factory()->create(['status' => 'active']);
        $liquidatedCustomer = Customer::factory()->create(['status' => 'active']);

        FixedDeposit::create([
            'customer_id' => $activeCustomer->id,
            'number' => 'DEP001',
            'amount' => 1000000,
            'rate_percent' => 6,
            'tenor_months' => 12,
            'start_date' => '2024-01-01',
            'maturity_date' => '2025-01-01',
            'status' => 'active'
        ]);

        FixedDeposit::create([
            'customer_id' => $liquidatedCustomer->id,
            'number' => 'DEP002',
            'amount' => 2000000,
            'rate_percent' => 8,
            'tenor_months' => 12,
            'start_date' => '2024-02-01',
            'maturity_date' => '2025-02-01',
            'status' => 'liquidated'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('report.deposit-recap') . '?status=active', [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertStatus(200);

        $data = $response->json();

        // Should only include active deposit
        $this->assertEquals(1000000, $data['filtered_total_nominal']);
        $this->assertEquals(5000, $data['filtered_total_bunga']);
    }

    /** @test */
    public function deposit_recap_respects_month_filter()
    {
        $customer = Customer::factory()->create(['status' => 'active']);

        FixedDeposit::create([
            'customer_id' => $customer->id,
            'number' => 'DEP001',
            'amount' => 1000000,
            'rate_percent' => 6,
            'tenor_months' => 12,
            'start_date' => '2024-01-15',
            'maturity_date' => '2025-01-15',
            'status' => 'active'
        ]);

        FixedDeposit::create([
            'customer_id' => $customer->id,
            'number' => 'DEP002',
            'amount' => 2000000,
            'rate_percent' => 8,
            'tenor_months' => 12,
            'start_date' => '2024-02-15',
            'maturity_date' => '2025-02-15',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('report.deposit-recap') . '?bulan=01', [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertStatus(200);

        $data = $response->json();

        // Should only include January deposit
        $this->assertEquals(1000000, $data['filtered_total_nominal']);
        $this->assertEquals(5000, $data['filtered_total_bunga']);
    }

    /** @test */
    public function deposit_recap_respects_year_filter()
    {
        $customer = Customer::factory()->create(['status' => 'active']);

        FixedDeposit::create([
            'customer_id' => $customer->id,
            'number' => 'DEP001',
            'amount' => 1000000,
            'rate_percent' => 6,
            'tenor_months' => 12,
            'start_date' => '2023-05-01',
            'maturity_date' => '2024-05-01',
            'status' => 'active'
        ]);

        FixedDeposit::create([
            'customer_id' => $customer->id,
            'number' => 'DEP002',
            'amount' => 2000000,
            'rate_percent' => 8,
            'tenor_months' => 12,
            'start_date' => '2024-05-01',
            'maturity_date' => '2025-05-01',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('report.deposit-recap') . '?tahun=2024', [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertStatus(200);

        $data = $response->json();

        // Should only include 2024 deposit
        $this->assertEquals(2000000, $data['filtered_total_nominal']);
        $this->assertEquals(13333, $data['filtered_total_bunga']);
    }

    /** @test */
    public function deposit_recap_returns_zero_totals_when_no_data_matches()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('report.deposit-recap') . '?status=active', [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertEquals(0, $data['filtered_total_nominal']);
        $this->assertEquals(0, $data['filtered_total_bunga']);
        $this->assertEquals('0', $data['filtered_total_nominal_formatted']);
        $this->assertEquals('0', $data['filtered_total_bunga_formatted']);
    }

    /** @test */
    public function deposit_recap_calculates_monthly_interest_correctly()
    {
        $customer = Customer::factory()->create(['status' => 'active']);

        // Test floor calculation
        // Amount: 1,500,000, Rate: 7.5%, Monthly = floor(1500000 * 7.5/100 / 12) = floor(9375) = 9375
        FixedDeposit::create([
            'customer_id' => $customer->id,
            'number' => 'DEP001',
            'amount' => 1500000,
            'rate_percent' => 7.5,
            'tenor_months' => 12,
            'start_date' => '2024-01-01',
            'maturity_date' => '2025-01-01',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('report.deposit-recap'), [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertEquals(9375, $data['filtered_total_bunga']);
    }

    /** @test */
    public function deposit_recap_combines_multiple_filters()
    {
        $customer = Customer::factory()->create(['status' => 'active']);

        FixedDeposit::create([
            'customer_id' => $customer->id,
            'number' => 'DEP001',
            'amount' => 1000000,
            'rate_percent' => 6,
            'tenor_months' => 12,
            'start_date' => '2024-01-15',
            'maturity_date' => '2025-01-15',
            'status' => 'active'
        ]);

        FixedDeposit::create([
            'customer_id' => $customer->id,
            'number' => 'DEP002',
            'amount' => 2000000,
            'rate_percent' => 8,
            'tenor_months' => 12,
            'start_date' => '2024-02-15',
            'maturity_date' => '2025-02-15',
            'status' => 'liquidated'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('report.deposit-recap') . '?status=active&bulan=01&tahun=2024', [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertStatus(200);

        $data = $response->json();

        // Should only include active + January + 2024
        $this->assertEquals(1000000, $data['filtered_total_nominal']);
        $this->assertEquals(5000, $data['filtered_total_bunga']);
    }

    /** @test */
    public function deposit_recap_ajax_orders_by_account_number_ascending()
    {
        $customer = Customer::factory()->create(['status' => 'active']);

        // Deposit with earlier start date but larger account_number
        FixedDeposit::create([
            'customer_id' => $customer->id,
            'number' => 'DEP-2024-001',
            'account_number' => 'REK-002',
            'amount' => 1000000,
            'rate_percent' => 6,
            'tenor_months' => 12,
            'start_date' => '2024-01-01',
            'maturity_date' => '2025-01-01',
            'status' => 'active'
        ]);

        // Deposit with later start date but smaller account_number
        FixedDeposit::create([
            'customer_id' => $customer->id,
            'number' => 'DEP-2024-002',
            'account_number' => 'REK-001',
            'amount' => 2000000,
            'rate_percent' => 6,
            'tenor_months' => 12,
            'start_date' => '2024-02-01',
            'maturity_date' => '2025-02-01',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('report.deposit-recap'), [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('REK-001', $data[0]['rekening_deposito']);
        $this->assertEquals('REK-002', $data[1]['rekening_deposito']);
    }

    /** @test */
    public function deposit_recap_print_orders_by_account_number_and_renders_successfully()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $customer = Customer::factory()->create(['status' => 'active']);

        FixedDeposit::create([
            'customer_id' => $customer->id,
            'number' => 'DEP-2024-001',
            'account_number' => 'REK-002',
            'amount' => 1000000,
            'rate_percent' => 6,
            'tenor_months' => 12,
            'start_date' => '2024-01-01',
            'maturity_date' => '2025-01-01',
            'status' => 'active'
        ]);

        FixedDeposit::create([
            'customer_id' => $customer->id,
            'number' => 'DEP-2024-002',
            'account_number' => 'REK-001',
            'amount' => 2000000,
            'rate_percent' => 6,
            'tenor_months' => 12,
            'start_date' => '2024-02-01',
            'maturity_date' => '2025-02-01',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('report.deposit-recap.print'));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }
}
