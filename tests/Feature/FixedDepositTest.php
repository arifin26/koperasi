<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\FixedDeposit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedDepositTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\InterestRate::create([
            'name' => 'Default',
            'type' => 'simpanan',
            'rate_percent' => 5,
            'effective_date' => '2026-01-01',
            'is_active' => true,
            'is_default' => true,
        ]);
    }


    public function test_fixed_deposit_detail_page_loads_successfully()
    {
        $user = User::create([
            'name' => 'Teller Test',
            'username' => 'tellertest',
            'password' => bcrypt('password'),
            'role' => 'teller',
            'phone' => '08123456789',
        ]);

        $customer = Customer::create([
            'number' => 'NAS-001',
            'name' => 'Nasabah Test',
            'nik' => '1234567890123456',
            'address' => 'Jl. Test No. 1',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);

        $fixedDeposit = FixedDeposit::create([
            'number' => 'DEP-202609-001',
            'account_number' => 'DEP-001',
            'customer_id' => $customer->id,
            'amount' => 10000000,
            'tenor_months' => 6,
            'rate_percent' => 6.0,
            'start_date' => Carbon::today(),
            'maturity_date' => Carbon::today()->addMonths(6),
            'status' => 'active',
            'notes' => 'Deposito test',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('fixed-deposit.show', $fixedDeposit));

        $response->assertStatus(200);
        $response->assertViewIs('pages.fixed-deposit.show');
        $response->assertSee('DEP-202609-001');
        $response->assertSee('Nasabah Test');
        $response->assertSee(route('fixed-deposit.receipt', $fixedDeposit));
        $response->assertSee(route('fixed-deposit.extend.form', $fixedDeposit));
        $response->assertSee(route('fixed-deposit.liquidate.form', $fixedDeposit));
    }

    public function test_fixed_deposit_receipt_pdf_loads_successfully()
    {
        $user = User::create([
            'name' => 'Teller Test',
            'username' => 'tellertest2',
            'password' => bcrypt('password'),
            'role' => 'teller',
            'phone' => '08123456780',
        ]);

        $customer = Customer::create([
            'number' => 'NAS-003',
            'name' => 'Nasabah Tiga',
            'nik' => '1234567890123458',
            'address' => 'Jl. Test No. 3',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);

        $fixedDeposit = FixedDeposit::create([
            'number' => 'DEP-202609-003',
            'account_number' => 'DEP-003',
            'customer_id' => $customer->id,
            'amount' => 15000000,
            'tenor_months' => 3,
            'rate_percent' => 5.5,
            'start_date' => Carbon::today(),
            'maturity_date' => Carbon::today()->addMonths(3),
            'status' => 'active',
            'notes' => 'Deposito test 3',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('fixed-deposit.receipt', $fixedDeposit));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_rekap_deposito_detail_url_points_to_correct_route()
    {
        $user = User::create([
            'name' => 'Manager Test',
            'username' => 'managertest',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'phone' => '08123456789',
        ]);

        $customer = Customer::create([
            'number' => 'NAS-002',
            'name' => 'Nasabah Dua',
            'nik' => '1234567890123457',
            'address' => 'Jl. Test No. 2',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);

        $fixedDeposit = FixedDeposit::create([
            'number' => 'DEP-202609-002',
            'account_number' => 'DEP-002',
            'customer_id' => $customer->id,
            'amount' => 20000000,
            'tenor_months' => 12,
            'rate_percent' => 7.0,
            'start_date' => Carbon::today(),
            'maturity_date' => Carbon::today()->addMonths(12),
            'status' => 'active',
            'notes' => 'Deposito test 2',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('report.deposit-recap'), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $expectedUrl = route('fixed-deposit.show', $fixedDeposit);
        $this->assertStringContainsString($expectedUrl, stripslashes($response->getContent()));
    }

    public function test_customer_can_create_multiple_active_fixed_deposits()
    {
        $user = User::create([
            'name' => 'Teller Test Multiple',
            'username' => 'tellermultiple',
            'password' => bcrypt('password'),
            'role' => 'teller',
            'phone' => '08123456781',
        ]);

        $customer = Customer::create([
            'number' => 'NAS-004',
            'name' => 'Nasabah Multiple Deposito',
            'nik' => '1234567890123459',
            'address' => 'Jl. Test No. 4',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);

        Deposit::create([
            'customer_id' => $customer->id,
            'type' => 'pokok',
            'amount' => 500000,
            'created_by' => $user->id,
        ]);

        FixedDeposit::create([
            'number' => 'DEP-202610-001',
            'account_number' => 'DEP-001',
            'customer_id' => $customer->id,
            'amount' => 10000000,
            'tenor_months' => 6,
            'rate_percent' => 6.0,
            'start_date' => Carbon::today(),
            'maturity_date' => Carbon::today()->addMonths(6),
            'status' => 'active',
            'notes' => 'Deposito pertama',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('fixed-deposit.store'), [
            'customer_id' => $customer->id,
            'account_number' => 'DEP-002',
            'amount' => 5000000,
            'tenor_months' => 12,
            'rate_percent' => 6.5,
            'start_date' => Carbon::today()->format('Y-m-d'),
            'notes' => 'Deposito kedua',
        ]);

        $response->assertRedirect(route('fixed-deposit.index'));
        $response->assertSessionHasNoErrors();

        $this->assertEquals(
            2,
            FixedDeposit::where('customer_id', $customer->id)->where('status', 'active')->count()
        );
    }

    public function test_duplicate_deposit_account_number_is_rejected()
    {
        $user = User::create([
            'name' => 'Teller Test Duplicate',
            'username' => 'tellerduplicate',
            'password' => bcrypt('password'),
            'role' => 'teller',
            'phone' => '08123456782',
        ]);

        $customer = Customer::create([
            'number' => 'NAS-005',
            'name' => 'Nasabah Test Duplicate',
            'nik' => '1234567890123460',
            'address' => 'Jl. Test No. 5',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);

        Deposit::create([
            'customer_id' => $customer->id,
            'type' => 'pokok',
            'amount' => 500000,
            'created_by' => $user->id,
        ]);

        FixedDeposit::create([
            'number' => 'DEP-202610-002',
            'account_number' => 'DEP-EXISTING',
            'customer_id' => $customer->id,
            'amount' => 10000000,
            'tenor_months' => 6,
            'rate_percent' => 6.0,
            'start_date' => Carbon::today(),
            'maturity_date' => Carbon::today()->addMonths(6),
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('fixed-deposit.store'), [
            'customer_id' => $customer->id,
            'account_number' => 'DEP-EXISTING',
            'amount' => 5000000,
            'tenor_months' => 12,
            'rate_percent' => 6.5,
            'start_date' => Carbon::today()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('account_number');
    }
}
