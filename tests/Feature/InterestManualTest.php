<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\FixedDeposit;
use App\Models\Holiday;
use App\Models\InterestRate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterestManualTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teller;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create([
            'role' => 'manager',
        ]);

        $this->teller = User::factory()->create([
            'role' => 'teller',
        ]);

        // Rate simpanan aktif 6% p.a.
        InterestRate::create([
            'type' => 'simpanan',
            'rate_percent' => 6.00,
            'effective_date' => now()->subMonth(),
            'is_active' => true,
            'created_by' => $this->manager->id,
        ]);

        // Customer & Initial Deposit
        $this->customer = Customer::factory()->create([
            'status' => 'active',
        ]);

        Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'simpanan',
            'amount' => 10000000, // Rp 10.000.000
            'previous_balance' => 0,
            'current_balance' => 10000000,
            'created_at' => now()->subDays(10),
            'created_by' => $this->manager->id,
        ]);
    }

    /** @test */
    public function non_manager_cannot_trigger_savings_interest_update()
    {
        $response = $this->actingAs($this->teller)
            ->postJson(route('transaction.deposit.update-bunga'), [
                'process_date' => date('Y-m-d'),
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function manager_can_trigger_savings_interest_update_manually()
    {
        $response = $this->actingAs($this->manager)
            ->postJson(route('transaction.deposit.update-bunga'), [
                'process_date' => date('Y-m-d'),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('interest_posting_logs', [
            'posted_by' => $this->manager->id,
            'status' => 'manual',
        ]);
    }

    /** @test */
    public function repeated_savings_interest_updates_do_not_create_duplicate_postings()
    {
        // First run
        $this->actingAs($this->manager)
            ->postJson(route('transaction.deposit.update-bunga'), [
                'process_date' => date('Y-m-d'),
            ]);

        $initialTxnCount = Deposit::where('customer_id', $this->customer->id)->where('type', 'bunga')->count();

        // Second run on same day
        $response = $this->actingAs($this->manager)
            ->postJson(route('transaction.deposit.update-bunga'), [
                'process_date' => date('Y-m-d'),
            ]);

        $response->assertStatus(200);

        $secondTxnCount = Deposit::where('customer_id', $this->customer->id)->where('type', 'bunga')->count();
        $this->assertEquals($initialTxnCount, $secondTxnCount);
    }

    /** @test */
    public function manager_can_trigger_deposit_interest_update_manually()
    {
        // Rate deposito
        $depositoRate = InterestRate::create([
            'type' => 'deposito',
            'rate_percent' => 8.00,
            'effective_date' => now()->subMonth(),
            'is_active' => true,
        ]);

        $fixedDeposit = FixedDeposit::create([
            'number' => 'DEP-TEST-001',
            'customer_id' => $this->customer->id,
            'amount' => 50000000,
            'tenor_months' => 12,
            'rate_percent' => 8.00,
            'start_date' => now()->subMonth(),
            'maturity_date' => now()->addMonths(11),
            'status' => 'active',
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson(route('fixed-deposit.update-bunga'), [
                'process_date' => date('Y-m-d'),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('deposit_interest_payments', [
            'fixed_deposit_id' => $fixedDeposit->id,
            'period' => date('Y-m'),
        ]);
    }
}
