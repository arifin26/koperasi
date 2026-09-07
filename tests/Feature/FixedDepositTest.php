<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\FixedDeposit;
use App\Models\Deposit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FixedDepositTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'manager']);
        $this->customer = Customer::factory()->create(['status' => 'active']);

        // Create savings account for customer
        Deposit::create([
            'type' => 'pokok',
            'amount' => 1000000,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);
        Deposit::recalculateBalance($this->customer->id);
    }

    /** @test */
    public function can_create_fixed_deposit_with_valid_account_number()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('fixed-deposit.store'), [
            'customer_id' => $this->customer->id,
            'account_number' => 'DEP-00001',
            'amount' => 5000000,
            'tenor_months' => 12,
            'rate_percent' => 5.5,
            'notes' => 'Test deposit',
        ]);

        $response->assertRedirect(route('fixed-deposit.index'));
        $this->assertDatabaseHas('fixed_deposits', [
            'customer_id' => $this->customer->id,
            'account_number' => 'DEP-00001',
            'amount' => 5000000,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function cannot_create_fixed_deposit_if_account_number_equals_customer_number()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('fixed-deposit.store'), [
            'customer_id' => $this->customer->id,
            'account_number' => $this->customer->number, // Same as customer.number (WRONG)
            'amount' => 5000000,
            'tenor_months' => 12,
            'rate_percent' => 5.5,
        ]);

        $response->assertSessionHasErrors('account_number');
        $this->assertDatabaseMissing('fixed_deposits', [
            'customer_id' => $this->customer->id,
            'account_number' => $this->customer->number,
        ]);
    }

    /** @test */
    public function cannot_create_new_deposit_if_customer_has_active_deposit()
    {
        $this->actingAs($this->user);

        // Create first deposit
        FixedDeposit::create([
            'number' => 'DEP-202609-00001',
            'account_number' => 'DEP-00001',
            'customer_id' => $this->customer->id,
            'amount' => 5000000,
            'tenor_months' => 12,
            'rate_percent' => 5.5,
            'start_date' => now(),
            'maturity_date' => now()->addMonths(12),
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Try to create second deposit (should fail)
        $response = $this->post(route('fixed-deposit.store'), [
            'customer_id' => $this->customer->id,
            'account_number' => 'DEP-00002',
            'amount' => 3000000,
            'tenor_months' => 6,
            'rate_percent' => 5.0,
        ]);

        $response->assertSessionHasErrors('customer_id');
        $this->assertDatabaseMissing('fixed_deposits', [
            'customer_id' => $this->customer->id,
            'account_number' => 'DEP-00002',
        ]);
    }

    /** @test */
    public function can_create_new_deposit_after_liquidating_previous_one()
    {
        $this->actingAs($this->user);

        // Create and liquidate first deposit
        $firstDeposit = FixedDeposit::create([
            'number' => 'DEP-202609-00001',
            'account_number' => 'DEP-00001',
            'customer_id' => $this->customer->id,
            'amount' => 5000000,
            'tenor_months' => 12,
            'rate_percent' => 5.5,
            'start_date' => now(),
            'maturity_date' => now()->addMonths(12),
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $firstDeposit->update(['status' => 'liquidated', 'liquidated_at' => now()]);

        // Create new deposit (should succeed)
        $response = $this->post(route('fixed-deposit.store'), [
            'customer_id' => $this->customer->id,
            'account_number' => 'DEP-00002',
            'amount' => 3000000,
            'tenor_months' => 6,
            'rate_percent' => 5.0,
        ]);

        $response->assertRedirect(route('fixed-deposit.index'));
        $this->assertDatabaseHas('fixed_deposits', [
            'customer_id' => $this->customer->id,
            'account_number' => 'DEP-00002',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function extend_copies_account_number_from_original_deposit()
    {
        $this->actingAs($this->user);

        // Create original deposit
        $originalDeposit = FixedDeposit::create([
            'number' => 'DEP-202609-00001',
            'account_number' => 'DEP-00001',
            'customer_id' => $this->customer->id,
            'amount' => 5000000,
            'tenor_months' => 12,
            'rate_percent' => 5.5,
            'start_date' => now(),
            'maturity_date' => now()->addMonths(12),
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Extend deposit
        $response = $this->post(route('fixed-deposit.extend', $originalDeposit), [
            'tenor_months' => 12,
            'rate_percent' => 5.5,
        ]);

        $response->assertRedirect(route('fixed-deposit.index'));

        // Check original deposit marked as extended
        $this->assertDatabaseHas('fixed_deposits', [
            'id' => $originalDeposit->id,
            'status' => 'extended',
        ]);

        // Check new deposit has same account_number as original
        $extendedDeposit = FixedDeposit::where('extended_from_id', $originalDeposit->id)->first();
        $this->assertNotNull($extendedDeposit);
        $this->assertEquals('DEP-00001', $extendedDeposit->account_number);
        $this->assertEquals($originalDeposit->account_number, $extendedDeposit->account_number);
    }

    /** @test */
    public function composite_unique_constraint_allows_reuse_after_soft_delete()
    {
        $this->actingAs($this->user);

        // Create first deposit
        $firstDeposit = FixedDeposit::create([
            'number' => 'DEP-202609-00001',
            'account_number' => 'DEP-00001',
            'customer_id' => $this->customer->id,
            'amount' => 5000000,
            'tenor_months' => 12,
            'rate_percent' => 5.5,
            'start_date' => now(),
            'maturity_date' => now()->addMonths(12),
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Soft delete it
        $firstDeposit->delete();

        // Create new deposit with same account_number (should succeed)
        $response = $this->post(route('fixed-deposit.store'), [
            'customer_id' => $this->customer->id,
            'account_number' => 'DEP-00001', // Same account_number as deleted deposit
            'amount' => 3000000,
            'tenor_months' => 6,
            'rate_percent' => 5.0,
        ]);

        $response->assertRedirect(route('fixed-deposit.index'));
        $this->assertDatabaseHas('fixed_deposits', [
            'customer_id' => $this->customer->id,
            'account_number' => 'DEP-00001',
            'status' => 'active',
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function api_endpoint_returns_has_active_deposit_flag()
    {
        // Create active deposit
        FixedDeposit::create([
            'number' => 'DEP-202609-00001',
            'account_number' => 'DEP-00001',
            'customer_id' => $this->customer->id,
            'amount' => 5000000,
            'tenor_months' => 12,
            'rate_percent' => 5.5,
            'start_date' => now(),
            'maturity_date' => now()->addMonths(12),
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson(route('customer.balance', $this->customer->id));

        $response->assertOk();
        $response->assertJsonPath('data.has_active_deposit', true);
        $response->assertJsonPath('data.active_deposit_number', 'DEP-202609-00001');
    }

    /** @test */
    public function cannot_extend_non_active_deposit()
    {
        $this->actingAs($this->user);

        $deposit = FixedDeposit::create([
            'number' => 'DEP-202609-00001',
            'account_number' => 'DEP-00001',
            'customer_id' => $this->customer->id,
            'amount' => 5000000,
            'tenor_months' => 12,
            'rate_percent' => 5.5,
            'start_date' => now(),
            'maturity_date' => now()->addMonths(12),
            'status' => 'liquidated',
            'liquidated_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $response = $this->post(route('fixed-deposit.extend', $deposit), [
            'tenor_months' => 12,
            'rate_percent' => 5.5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
