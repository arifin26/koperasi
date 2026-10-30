<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FixedDeposit;
use App\Models\InterestRate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerUpdateAndExcelExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teller;

    protected function setUp(): void
    {
        parent::setUp();

        InterestRate::create([
            'name' => 'Default',
            'type' => 'simpanan',
            'rate_percent' => 5.0,
            'effective_date' => '2026-01-01',
            'is_active' => true,
            'is_default' => true,
        ]);

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

    /** @test */
    public function customer_can_be_stored_without_phone()
    {
        $payload = [
            'nik' => '3506123456780001',
            'name' => 'Nasabah Tanpa Telp',
            'number' => 'REK-001',
            'gender' => 'L',
            'birth' => '1990-01-01',
            'address' => 'Desa Puncu',
            'last_education' => 'SMA',
            'profession' => 'Petani',
        ];

        $response = $this->actingAs($this->teller)
            ->post(route('customer.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('customer.index'));

        $this->assertDatabaseHas('customers', [
            'nik' => '3506123456780001',
            'name' => 'Nasabah Tanpa Telp',
            'number' => 'REK-001',
        ]);
    }

    /** @test */
    public function multiple_customers_can_have_same_nik()
    {
        Customer::create([
            'nik' => '3506123456780001',
            'name' => 'Nasabah Multi 1',
            'number' => 'REK-MULTI-1',
            'gender' => 'L',
            'birth' => '1990-01-01',
            'address' => 'Desa Puncu',
            'last_education' => 'SMA',
            'profession' => 'Petani',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);

        $payload2 = [
            'nik' => '3506123456780001',
            'name' => 'Nasabah Multi 2',
            'number' => 'REK-MULTI-2',
            'gender' => 'L',
            'birth' => '1990-01-01',
            'address' => 'Desa Puncu',
            'last_education' => 'SMA',
            'profession' => 'Petani',
        ];

        $response = $this->actingAs($this->teller)
            ->post(route('customer.store'), $payload2);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('customer.index'));

        $this->assertDatabaseHas('customers', [
            'nik' => '3506123456780001',
            'number' => 'REK-MULTI-1',
        ]);
        $this->assertDatabaseHas('customers', [
            'nik' => '3506123456780001',
            'number' => 'REK-MULTI-2',
        ]);
    }

    /** @test */
    public function duplicate_account_number_is_rejected()
    {
        Customer::create([
            'nik' => '3506123456780001',
            'name' => 'Nasabah Pertama',
            'number' => 'REK-UNIQUE-1',
            'gender' => 'L',
            'birth' => '1990-01-01',
            'address' => 'Desa Puncu',
            'last_education' => 'SMA',
            'profession' => 'Petani',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);

        $payloadDuplicate = [
            'nik' => '3506123456789999',
            'name' => 'Nasabah Kedua',
            'number' => 'REK-UNIQUE-1',
            'gender' => 'L',
            'birth' => '1992-02-02',
            'address' => 'Desa Puncu',
            'last_education' => 'SMP',
            'profession' => 'Pedagang',
        ];

        $response = $this->actingAs($this->teller)
            ->post(route('customer.store'), $payloadDuplicate);

        $response->assertSessionHasErrors(['number']);
    }

    /** @test */
    public function lookup_nik_returns_customer_profile_when_found()
    {
        Customer::create([
            'nik' => '3506111122223333',
            'name' => 'Dewi Sartika',
            'number' => 'REK-DEWI-01',
            'gender' => 'P',
            'birth' => '1995-12-25',
            'address' => 'Jl. Pahlawan No. 45',
            'last_education' => 'S1',
            'profession' => 'Guru',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->teller)
            ->getJson(route('customer.lookup-nik', ['nik' => '3506111122223333']));

        $response->assertStatus(200);
        $response->assertJson([
            'exists' => true,
            'data' => [
                'name' => 'Dewi Sartika',
                'gender' => 'P',
                'birth' => '1995-12-25',
                'address' => 'Jl. Pahlawan No. 45',
                'profession' => 'Guru',
                'last_education' => 'S1',
                'existing_number' => 'REK-DEWI-01',
            ],
        ]);
    }

    /** @test */
    public function lookup_nik_returns_false_when_not_found()
    {
        $response = $this->actingAs($this->teller)
            ->getJson(route('customer.lookup-nik', ['nik' => '3506000000000000']));

        $response->assertStatus(200);
        $response->assertJson([
            'exists' => false,
        ]);
    }

    /** @test */
    public function savings_recap_export_excel_returns_xlsx_stream()
    {
        Customer::create([
            'nik' => '3506111122223333',
            'name' => 'Dewi Sartika',
            'number' => 'REK-DEWI-01',
            'gender' => 'P',
            'birth' => '1995-12-25',
            'address' => 'Jl. Pahlawan No. 45',
            'last_education' => 'S1',
            'profession' => 'Guru',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('report.savings-recap.export'), [
                'status' => 'active',
                'tanggal' => date('Y-m-d'),
            ]);

        $response->assertStatus(200);
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString('laporan_rekap_simpanan_', $response->headers->get('Content-Disposition'));
        $this->assertStringEndsWith('.xlsx"', $response->headers->get('Content-Disposition'));
    }

    /** @test */
    public function deposit_recap_export_excel_returns_xlsx_stream()
    {
        $customer = Customer::create([
            'nik' => '3506111122223333',
            'name' => 'Dewi Sartika',
            'number' => 'REK-DEWI-01',
            'gender' => 'P',
            'birth' => '1995-12-25',
            'address' => 'Jl. Pahlawan No. 45',
            'last_education' => 'S1',
            'profession' => 'Guru',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);

        FixedDeposit::create([
            'number' => 'DEP-202610-001',
            'account_number' => 'DEP-001',
            'customer_id' => $customer->id,
            'amount' => 10000000,
            'tenor_months' => 12,
            'rate_percent' => 7.0,
            'start_date' => Carbon::today(),
            'maturity_date' => Carbon::today()->addMonths(12),
            'status' => 'active',
            'notes' => 'Test Deposito',
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('report.deposit-recap.export'), [
                'status' => 'active',
                'bulan' => '',
                'tahun' => '',
            ]);

        $response->assertStatus(200);
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString('laporan_rekap_deposito_', $response->headers->get('Content-Disposition'));
        $this->assertStringEndsWith('.xlsx"', $response->headers->get('Content-Disposition'));
    }
}
