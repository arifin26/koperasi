<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FixedDeposit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedDepositTest extends TestCase
{
    use RefreshDatabase;

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
            'phone' => '081234567890',
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
            'phone' => '081234567892',
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
            'phone' => '081234567891',
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
        $response->assertSee($expectedUrl, false);
    }
}
