<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\InterestRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SavingsRecapPrintTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'teller']);
        $this->manager = User::factory()->create(['role' => 'manager']);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_print_endpoint()
    {
        $response = $this->postJson(route('report.savings-recap.print'));

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_access_print_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'));

        $response->assertStatus(200);
    }

    /** @test */
    public function print_endpoint_returns_json_not_pdf()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'));

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => ['html']
            ]);

        // Should not have PDF headers
        $this->assertStringNotContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function print_endpoint_validates_status_filter()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'), [
                'status' => 'invalid_status'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function print_endpoint_validates_date_format()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'), [
                'tanggal' => '05/09/2024'  // wrong format
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tanggal']);
    }

    /** @test */
    public function print_endpoint_returns_all_customers_when_no_filter_applied()
    {
        $rate = InterestRate::factory()->create(['type' => 'tabungan_sukarela', 'rate_percent' => 2.5]);

        $customer1 = Customer::factory()->create([
            'status' => 'active',
            'interest_rate_id' => $rate->id
        ]);
        $customer2 = Customer::factory()->create([
            'status' => 'blacklist',
            'interest_rate_id' => $rate->id
        ]);

        Deposit::factory()->create([
            'customer_id' => $customer1->id,
            'amount' => 100000,
            'current_balance' => 100000
        ]);

        Deposit::factory()->create([
            'customer_id' => $customer2->id,
            'amount' => 50000,
            'current_balance' => 50000
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'));

        $response->assertStatus(200);

        $html = $response->json('data.html');
        $this->assertStringContainsString($customer1->name, $html);
        $this->assertStringContainsString($customer2->name, $html);
        $this->assertStringContainsString('REKAP SIMPANAN NASABAH', $html);
    }

    /** @test */
    public function print_endpoint_filters_by_active_status()
    {
        $rate = InterestRate::factory()->create(['type' => 'tabungan_sukarela', 'rate_percent' => 2.5]);

        $activeCustomer = Customer::factory()->create([
            'status' => 'active',
            'interest_rate_id' => $rate->id
        ]);
        $blacklistCustomer = Customer::factory()->create([
            'status' => 'blacklist',
            'interest_rate_id' => $rate->id
        ]);

        Deposit::factory()->create([
            'customer_id' => $activeCustomer->id,
            'amount' => 100000,
            'current_balance' => 100000
        ]);

        Deposit::factory()->create([
            'customer_id' => $blacklistCustomer->id,
            'amount' => 50000,
            'current_balance' => 50000
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'), [
                'status' => 'active'
            ]);

        $response->assertStatus(200);

        $html = $response->json('data.html');
        $this->assertStringContainsString($activeCustomer->name, $html);
        $this->assertStringNotContainsString($blacklistCustomer->name, $html);
        $this->assertStringContainsString('Nasabah Aktif', $html);
    }

    /** @test */
    public function print_endpoint_filters_by_blacklist_status()
    {
        $rate = InterestRate::factory()->create(['type' => 'tabungan_sukarela', 'rate_percent' => 2.5]);

        $activeCustomer = Customer::factory()->create([
            'status' => 'active',
            'interest_rate_id' => $rate->id
        ]);
        $blacklistCustomer = Customer::factory()->create([
            'status' => 'blacklist',
            'interest_rate_id' => $rate->id
        ]);

        Deposit::factory()->create([
            'customer_id' => $activeCustomer->id,
            'amount' => 100000,
            'current_balance' => 100000
        ]);

        Deposit::factory()->create([
            'customer_id' => $blacklistCustomer->id,
            'amount' => 50000,
            'current_balance' => 50000
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'), [
                'status' => 'blacklist'
            ]);

        $response->assertStatus(200);

        $html = $response->json('data.html');
        $this->assertStringNotContainsString($activeCustomer->name, $html);
        $this->assertStringContainsString($blacklistCustomer->name, $html);
        $this->assertStringContainsString('Nasabah Blacklist', $html);
    }

    /** @test */
    public function print_endpoint_filters_by_date_cutoff()
    {
        $rate = InterestRate::factory()->create(['type' => 'tabungan_sukarela', 'rate_percent' => 2.5]);

        $customer = Customer::factory()->create([
            'status' => 'active',
            'interest_rate_id' => $rate->id
        ]);

        // Deposit before cutoff
        Deposit::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 100000,
            'current_balance' => 100000,
            'created_at' => Carbon::parse('2024-08-01')
        ]);

        // Deposit after cutoff
        Deposit::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 50000,
            'current_balance' => 150000,
            'created_at' => Carbon::parse('2024-09-01')
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'), [
                'tanggal' => '2024-08-15'
            ]);

        $response->assertStatus(200);

        $html = $response->json('data.html');
        // Should show balance as of 2024-08-15 (only first deposit)
        $this->assertStringContainsString('100.000', $html);
        $this->assertStringNotContainsString('150.000', $html);
    }

    /** @test */
    public function print_endpoint_returns_empty_state_when_no_customers_match()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'), [
                'status' => 'active'
            ]);

        $response->assertStatus(200);

        $html = $response->json('data.html');
        $this->assertStringContainsString('REKAP SIMPANAN NASABAH', $html);
        // Should have totals even if zero
        $this->assertStringContainsString('TOTAL', $html);
    }

    /** @test */
    public function print_endpoint_includes_totals()
    {
        $rate = InterestRate::factory()->create(['type' => 'tabungan_sukarela', 'rate_percent' => 2.5]);

        $customer = Customer::factory()->create([
            'status' => 'active',
            'interest_rate_id' => $rate->id
        ]);

        Deposit::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 100000,
            'current_balance' => 100000
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'));

        $response->assertStatus(200);

        $html = $response->json('data.html');
        $this->assertStringContainsString('TOTAL DANA SIMPANAN', $html);
        $this->assertStringContainsString('TOTAL BUNGA', $html);
    }

    /** @test */
    public function print_endpoint_includes_signature_section()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'));

        $response->assertStatus(200);

        $html = $response->json('data.html');
        $this->assertStringContainsString($this->user->name, $html);
        $this->assertStringContainsString($this->manager->name, $html);
    }

    /** @test */
    public function savings_recap_ajax_orders_by_customer_number_ascending()
    {
        $rate = InterestRate::factory()->create(['type' => 'tabungan_sukarela', 'rate_percent' => 2.5]);

        // Customer with name "Zack" but smaller number
        $customer1 = Customer::factory()->create([
            'name' => 'Zack',
            'number' => 'NAS-001',
            'status' => 'active',
            'interest_rate_id' => $rate->id,
        ]);

        // Customer with name "Adam" but larger number
        $customer2 = Customer::factory()->create([
            'name' => 'Adam',
            'number' => 'NAS-002',
            'status' => 'active',
            'interest_rate_id' => $rate->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('report.savings-recap'), [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('NAS-001', $data[0]['no_nasabah']);
        $this->assertEquals('NAS-002', $data[1]['no_nasabah']);
    }

    /** @test */
    public function savings_recap_print_orders_by_customer_number_ascending()
    {
        $rate = InterestRate::factory()->create(['type' => 'tabungan_sukarela', 'rate_percent' => 2.5]);

        // Customer with name "Zack" but smaller number
        $customer1 = Customer::factory()->create([
            'name' => 'Zack',
            'number' => 'NAS-001',
            'status' => 'active',
            'interest_rate_id' => $rate->id,
        ]);

        // Customer with name "Adam" but larger number
        $customer2 = Customer::factory()->create([
            'name' => 'Adam',
            'number' => 'NAS-002',
            'status' => 'active',
            'interest_rate_id' => $rate->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('report.savings-recap.print'));

        $response->assertStatus(200);

        $html = $response->json('data.html');
        $pos1 = strpos($html, 'NAS-001');
        $pos2 = strpos($html, 'NAS-002');

        $this->assertNotFalse($pos1);
        $this->assertNotFalse($pos2);
        $this->assertLessThan($pos2, $pos1);
    }
}
