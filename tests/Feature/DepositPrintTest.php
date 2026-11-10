<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositPrintTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teller;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::create([
            'name' => 'Manager Koperasi',
            'username' => 'managertest',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'phone' => '08111111111',
        ]);

        $this->teller = User::create([
            'name' => 'Teller Test',
            'username' => 'tellertest',
            'password' => bcrypt('password'),
            'role' => 'teller',
            'phone' => '08222222222',
        ]);

        $this->customer = Customer::create([
            'number' => 'NAS-001',
            'name' => 'Budi Santoso',
            'nik' => '3501234567890001',
            'address' => 'Jl. Merdeka No. 45 Kediri',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_deposit_print(): void
    {
        $response = $this->post(route('transaction.deposit.print'), [
            'customer_id' => $this->customer->id,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_deposit_print_returns_pdf_download_with_expected_headers(): void
    {
        Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'sukarela',
            'amount' => 500000,
            'previous_balance' => 0,
            'current_balance' => 500000,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::parse('2025-05-10 09:00:00'),
        ]);

        $response = $this->actingAs($this->teller)->post(route('transaction.deposit.print'), [
            'customer_id' => $this->customer->id,
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'laporan_simpanan_nasabah_no_rekening_' . $this->customer->number,
            $response->headers->get('content-disposition')
        );
    }

    public function test_deposit_print_view_renders_passbook_format_columns_and_codes(): void
    {
        $d1 = Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'sukarela',
            'amount' => 500000,
            'previous_balance' => 0,
            'current_balance' => 500000,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::parse('2025-05-10 09:00:00'),
        ]);

        $d2 = Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'penarikan',
            'amount' => 100000,
            'previous_balance' => 500000,
            'current_balance' => 400000,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::parse('2025-05-11 10:00:00'),
        ]);

        $d3 = Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'bunga',
            'notes' => 'Bunga tabungan sukarela periode Mei',
            'amount' => 5000,
            'previous_balance' => 400000,
            'current_balance' => 405000,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::parse('2025-05-31 23:59:59'),
        ]);

        $d4 = Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'bunga',
            'notes' => 'Bunga Deposito No. DEP-001',
            'amount' => 15000,
            'previous_balance' => 405000,
            'current_balance' => 420000,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::parse('2025-06-01 08:00:00'),
        ]);

        $transactions = Deposit::where('customer_id', $this->customer->id)
            ->with(['creator'])
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $viewHtml = view('pages.transaction.deposit.print', [
            'title' => 'Laporan Simpanan Nasabah',
            'user' => $this->teller,
            'customer' => $this->customer,
            'date' => Carbon::now()->isoFormat('dddd, D MMMM Y'),
            'manager' => $this->manager,
            'data' => $transactions,
            'lastBalance' => $transactions->last()->current_balance ?? 0,
        ])->render();

        // 1. Kolom tabel passbook
        $this->assertStringContainsString('<th scope="col" style="width: 5%;">No</th>', $viewHtml);
        $this->assertStringContainsString('<th scope="col" style="width: 13%;">Tanggal</th>', $viewHtml);
        $this->assertStringContainsString('<th scope="col" style="width: 8%;">Sandi</th>', $viewHtml);
        $this->assertStringContainsString('<th scope="col" style="width: 19%;">Debet</th>', $viewHtml);
        $this->assertStringContainsString('<th scope="col" style="width: 19%;">Kredit</th>', $viewHtml);
        $this->assertStringContainsString('<th scope="col" style="width: 24%;">Saldo</th>', $viewHtml);
        $this->assertStringContainsString('<th scope="col" style="width: 12%;">Petugas</th>', $viewHtml);

        // 2. Transaksi 1: Setor (Sandi 1)
        $this->assertStringContainsString('10/05/25', $viewHtml);
        $this->assertStringContainsString('<td class="text-center">1</td>', $viewHtml);
        $this->assertStringContainsString('<td class="text-right">500.000</td>', $viewHtml);

        // 3. Transaksi 2: Penarikan (Sandi 2)
        $this->assertStringContainsString('11/05/25', $viewHtml);
        $this->assertStringContainsString('<td class="text-center">2</td>', $viewHtml);
        $this->assertStringContainsString('<td class="text-right">100.000</td>', $viewHtml);

        // 4. Transaksi 3: Bunga Simpanan (Sandi 4)
        $this->assertStringContainsString('31/05/25', $viewHtml);
        $this->assertStringContainsString('<td class="text-center">4</td>', $viewHtml);
        $this->assertStringContainsString('<td class="text-right">5.000</td>', $viewHtml);

        // 5. Transaksi 4: Bunga Deposito (Sandi 3)
        $this->assertStringContainsString('01/06/25', $viewHtml);
        $this->assertStringContainsString('<td class="text-center">3</td>', $viewHtml);
        $this->assertStringContainsString('<td class="text-right">15.000</td>', $viewHtml);

        // 6. Inisial teller
        $this->assertStringContainsString('<td class="text-center">TEL</td>', $viewHtml);

        // 7. Footer saldo akhir
        $this->assertStringContainsString('Saldo Akhir', $viewHtml);
        $this->assertStringContainsString('<th class="text-right">420.000</th>', $viewHtml);

        // 8. Keterangan sandi
        $this->assertStringContainsString('Keterangan Sandi: 1 = Setoran Simpanan | 2 = Penarikan | 3 = Bunga Deposito | 4 = Bunga Simpanan', $viewHtml);
    }

    public function test_deposit_print_renders_correctly_when_no_transactions(): void
    {
        $response = $this->actingAs($this->teller)->post(route('transaction.deposit.print'), [
            'customer_id' => $this->customer->id,
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        $viewHtml = view('pages.transaction.deposit.print', [
            'title' => 'Laporan Simpanan Nasabah',
            'user' => $this->teller,
            'customer' => $this->customer,
            'date' => Carbon::now()->isoFormat('dddd, D MMMM Y'),
            'manager' => $this->manager,
            'data' => collect([]),
            'lastBalance' => 0,
        ])->render();

        $this->assertStringContainsString('Tidak ada transaksi', $viewHtml);
        $this->assertStringContainsString('<th class="text-right">0</th>', $viewHtml);
        $this->assertStringContainsString('Keterangan Sandi: 1 = Setoran Simpanan | 2 = Penarikan | 3 = Bunga Deposito | 4 = Bunga Simpanan', $viewHtml);
    }

    public function test_deposit_print_validates_customer_id(): void
    {
        $response = $this->actingAs($this->teller)->post(route('transaction.deposit.print'), [
            'customer_id' => 999999,
        ]);

        $response->assertSessionHasErrors('customer_id');
    }
}
