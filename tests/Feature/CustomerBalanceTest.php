<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $teller;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teller = User::create([
            'name' => 'Teller Test',
            'username' => 'tellertest',
            'password' => bcrypt('password'),
            'role' => 'teller',
            'phone' => '08123456789',
        ]);

        $this->customer = Customer::create([
            'number' => 'NAS-001',
            'name' => 'Nasabah Test',
            'nik' => '1234567890123456',
            'phone' => '081234567890',
            'address' => 'Jl. Test No. 1',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);
    }

    public function test_customer_balance_returns_zero_when_no_deposits()
    {
        $response = $this->actingAs($this->teller)->getJson(route('customer.balance', $this->customer->id));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'current_balance' => 0,
                'customer_name' => 'Nasabah Test',
                'customer_number' => 'NAS-001',
            ],
            'has_deposit' => false,
            'deposit_count' => 0,
        ]);
    }

    public function test_customer_balance_returns_accurate_balance_after_deposit_and_withdrawal()
    {
        // 1. Simpanan 500.000
        Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'simpanan',
            'amount' => 500000,
            'previous_balance' => 0,
            'current_balance' => 0,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::now()->subDays(2),
        ]);
        Deposit::recalculateBalance($this->customer->id);

        $response = $this->actingAs($this->teller)->getJson(route('customer.balance', $this->customer->id));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'current_balance' => 500000,
                'has_deposit' => true,
            ],
            'has_deposit' => true,
            'deposit_count' => 1,
        ]);

        // 2. Penarikan 200.000
        Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'penarikan',
            'amount' => 200000,
            'previous_balance' => 0,
            'current_balance' => 0,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::now()->subDay(),
        ]);
        Deposit::recalculateBalance($this->customer->id);

        $responseAfterWithdrawal = $this->actingAs($this->teller)->getJson(route('customer.balance', $this->customer->id));

        $responseAfterWithdrawal->assertStatus(200);
        $responseAfterWithdrawal->assertJson([
            'status' => 'success',
            'data' => [
                'current_balance' => 300000,
            ],
            'has_deposit' => true,
            'deposit_count' => 2,
        ]);
    }

    public function test_customer_balance_requires_authentication()
    {
        $response = $this->getJson(route('customer.balance', $this->customer->id));

        $response->assertStatus(401);
    }

    public function test_customer_balance_includes_active_fixed_deposit_details()
    {
        \App\Models\FixedDeposit::create([
            'customer_id' => $this->customer->id,
            'number' => 'DEP-202609-001',
            'account_number' => 'DEP-001',
            'amount' => 10000000,
            'tenor_months' => 6,
            'rate_percent' => 6.0,
            'start_date' => Carbon::today(),
            'maturity_date' => Carbon::today()->addMonths(6),
            'status' => 'active',
            'notes' => 'Deposito test',
            'created_by' => $this->teller->id,
        ]);

        $response = $this->actingAs($this->teller)->getJson(route('customer.balance', $this->customer->id));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'has_active_deposit' => true,
                'active_deposit_number' => 'DEP-202609-001',
            ],
        ]);
    }

    public function test_customer_balance_returns_404_when_customer_not_found()
    {
        $response = $this->actingAs($this->teller)->getJson(route('customer.balance', 999999));

        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
            'code' => 404,
        ]);
    }
}
