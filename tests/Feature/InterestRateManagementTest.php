<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\FixedDeposit;
use App\Models\InterestRate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterestRateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::create([
            'name' => 'Manager Test',
            'username' => 'managertest',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'phone' => '08123456788',
        ]);

        $this->teller = User::create([
            'name' => 'Teller Test',
            'username' => 'tellertest',
            'password' => bcrypt('password'),
            'role' => 'teller',
            'phone' => '08123456789',
        ]);
    }

    /**
     * 1. Can create multiple savings interest rates that coexist.
     */
    public function test_can_create_multiple_savings_interest_rates()
    {
        $rate1 = InterestRate::create([
            'type' => 'simpanan',
            'rate_percent' => 2.00,
            'effective_date' => '2026-01-01',
            'notes' => 'Simpanan Reguler',
            'is_active' => true,
            'is_default' => true,
            'created_by' => $this->manager->id,
        ]);

        $rate2 = InterestRate::create([
            'type' => 'simpanan',
            'rate_percent' => 3.00,
            'effective_date' => '2026-01-01',
            'notes' => 'Simpanan Premium',
            'is_active' => true,
            'is_default' => false,
            'created_by' => $this->manager->id,
        ]);

        $this->assertDatabaseHas('interest_rates', ['id' => $rate1->id, 'is_active' => true]);
        $this->assertDatabaseHas('interest_rates', ['id' => $rate2->id, 'is_active' => true]);
        $this->assertEquals(2, InterestRate::savings()->active()->count());
    }

    /**
     * 2. Can create multiple deposit interest rates that coexist.
     */
    public function test_can_create_multiple_deposit_interest_rates()
    {
        $dep1 = InterestRate::create([
            'type' => 'deposito',
            'rate_percent' => 4.00,
            'effective_date' => '2026-01-01',
            'notes' => 'Deposito 3 Bulan',
            'is_active' => true,
            'created_by' => $this->manager->id,
        ]);

        $dep2 = InterestRate::create([
            'type' => 'deposito',
            'rate_percent' => 5.00,
            'effective_date' => '2026-01-01',
            'notes' => 'Deposito 6 Bulan',
            'is_active' => true,
            'created_by' => $this->manager->id,
        ]);

        $dep3 = InterestRate::create([
            'type' => 'deposito',
            'rate_percent' => 6.00,
            'effective_date' => '2026-01-01',
            'notes' => 'Deposito 12 Bulan',
            'is_active' => true,
            'created_by' => $this->manager->id,
        ]);

        $this->assertEquals(3, InterestRate::deposits()->active()->count());
    }

    /**
     * 3. Creating a new rate does not update or deactivate existing rates.
     */
    public function test_creating_new_rate_does_not_deactivate_existing_rates()
    {
        $initialRate = InterestRate::create([
            'type' => 'deposito',
            'rate_percent' => 4.00,
            'effective_date' => '2026-01-01',
            'notes' => 'Deposito 3 Bulan',
            'is_active' => true,
            'created_by' => $this->manager->id,
        ]);

        // Post request to create a new deposit rate
        $response = $this->actingAs($this->manager)->post(route('interest.store'), [
            'type' => 'deposito',
            'rate_percent' => 5.00,
            'effective_date' => '2026-02-01',
            'notes' => 'Deposito 6 Bulan',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('interest.index'));

        // Both rates must remain active!
        $this->assertTrue($initialRate->fresh()->is_active);
        $this->assertEquals(2, InterestRate::deposits()->active()->count());
    }

    /**
     * 4. Customer can have custom savings rate.
     */
    public function test_customer_can_have_custom_savings_rate()
    {
        $regulerRate = InterestRate::create([
            'type' => 'simpanan',
            'rate_percent' => 2.00,
            'effective_date' => '2026-01-01',
            'notes' => 'Simpanan Reguler',
            'is_active' => true,
            'is_default' => true,
        ]);

        $premiumRate = InterestRate::create([
            'type' => 'simpanan',
            'rate_percent' => 3.50,
            'effective_date' => '2026-01-01',
            'notes' => 'Simpanan Premium',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'number' => 'NAS-001',
            'name' => 'Nasabah VIP',
            'nik' => '1234567890123456',
            'phone' => '081234567890',
            'address' => 'Jl. Test',
            'gender' => 'L',
            'birth' => '1990-01-01',
            'last_education' => 'S1',
            'profession' => 'Wiraswasta',
            'status' => 'active',
            'interest_rate_id' => $premiumRate->id,
            'joined_at' => now(),
        ]);

        $this->assertEquals($premiumRate->id, $customer->fresh()->interest_rate_id);
        $this->assertEquals(3.50, $customer->fresh()->interestRate->rate_percent);
    }

    /**
     * 5. FixedDeposit stores selected interest_rate_id and snapshot rate_percent.
     */
    public function test_fixed_deposit_stores_selected_interest_rate_id_and_snapshot()
    {
        $depRate = InterestRate::create([
            'type' => 'deposito',
            'rate_percent' => 5.25,
            'effective_date' => '2026-01-01',
            'notes' => 'Deposito 6 Bulan Spesial',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'number' => 'NAS-002',
            'name' => 'Nasabah Deposito',
            'nik' => '1234567890123457',
            'phone' => '081234567891',
            'address' => 'Jl. Test',
            'gender' => 'L',
            'birth' => '1990-01-01',
            'last_education' => 'S1',
            'profession' => 'Wiraswasta',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        // Nasabah harus punya simpanan dulu
        Deposit::create([
            'customer_id' => $customer->id,
            'type' => 'simpanan',
            'amount' => 50000000,
            'created_by' => $this->teller->id,
        ]);

        $response = $this->actingAs($this->teller)->post(route('fixed-deposit.store'), [
            'customer_id' => $customer->id,
            'account_number' => 'DEP-002',
            'interest_rate_id' => $depRate->id,
            'amount' => 10000000,
            'tenor_months' => 6,
            'rate_percent' => 5.25,
            'notes' => 'Buka Deposito Baru',
        ]);

        $response->assertRedirect(route('fixed-deposit.index'));

        $fixedDeposit = FixedDeposit::where('account_number', 'DEP-002')->first();
        $this->assertNotNull($fixedDeposit);
        $this->assertEquals($depRate->id, $fixedDeposit->interest_rate_id);
        $this->assertEquals(5.25, $fixedDeposit->rate_percent);
    }

    /**
     * 6. Existing FixedDeposit keeps its original rate even after master rate changes.
     */
    public function test_existing_fixed_deposit_keeps_original_rate_after_master_rate_changes()
    {
        $depRate = InterestRate::create([
            'type' => 'deposito',
            'rate_percent' => 5.00,
            'effective_date' => '2026-01-01',
            'notes' => 'Deposito 6 Bulan',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'number' => 'NAS-003',
            'name' => 'Nasabah Snapshot',
            'nik' => '1234567890123458',
            'phone' => '081234567892',
            'address' => 'Jl. Test',
            'gender' => 'L',
            'birth' => '1990-01-01',
            'last_education' => 'S1',
            'profession' => 'Wiraswasta',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $fixedDeposit = FixedDeposit::create([
            'number' => 'DEP-202609-003',
            'account_number' => 'DEP-003',
            'customer_id' => $customer->id,
            'interest_rate_id' => $depRate->id,
            'amount' => 12000000,
            'tenor_months' => 6,
            'rate_percent' => 5.00,
            'start_date' => Carbon::today(),
            'maturity_date' => Carbon::today()->addMonths(6),
            'status' => 'active',
            'created_by' => $this->teller->id,
        ]);

        // Master rate diubah menjadi 6.5%
        $depRate->update(['rate_percent' => 6.50]);

        // Fixed deposit rate harus tetap 5.00%
        $this->assertEquals(5.00, $fixedDeposit->fresh()->rate_percent);
    }

    /**
     * 7. Active rates API endpoint returns active rates.
     */
    public function test_active_rates_api_endpoint()
    {
        InterestRate::create([
            'type' => 'simpanan',
            'rate_percent' => 2.50,
            'effective_date' => '2026-01-01',
            'notes' => 'Simpanan A',
            'is_active' => true,
        ]);

        InterestRate::create([
            'type' => 'simpanan',
            'rate_percent' => 3.00,
            'effective_date' => '2026-01-01',
            'notes' => 'Simpanan B',
            'is_active' => false, // Nonaktif
        ]);

        $response = $this->actingAs($this->teller)->getJson(route('interest.active', ['type' => 'simpanan']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['rate_percent' => '2.50']);
    }
}
