<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FixedDeposit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cetakan deposito harus memakai NO REKENING deposito (account_number),
 * bukan nomor internal DEP-YYYYMM-NNNNN.
 */
class FixedDepositPrintNumberTest extends TestCase
{
    use RefreshDatabase;

    private const NO_DEPOSITO = 'DEP-202610-00002';
    private const NO_REKENING = '211000261';

    private FixedDeposit $deposit;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $user = User::create([
            'name' => 'Teller Cetak',
            'username' => 'tellercetak',
            'password' => bcrypt('password'),
            'role' => 'teller',
            'phone' => '08123450009',
        ]);

        $customer = Customer::create([
            'number' => '210000630',
            'name' => 'USP SIDOMULYO',
            'nik' => '3200000000000009',
            'address' => 'Jl. Test',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);

        $this->deposit = FixedDeposit::create([
            'number' => self::NO_DEPOSITO,
            'account_number' => self::NO_REKENING,
            'customer_id' => $customer->id,
            'amount' => 150000000,
            'tenor_months' => 3,
            'rate_percent' => 9.00,
            'start_date' => '2026-10-01',
            'maturity_date' => '2027-01-01',
            'status' => 'active',
            'created_by' => $user->id,
            'validated_at' => Carbon::parse('2026-10-01 09:00:00'),
        ]);
    }

    private function render(string $view, array $extra = []): string
    {
        return view($view, array_merge([
            'title' => 'Cetak',
            'fixed_deposit' => $this->deposit->load('customer'),
            'terbilang' => 'seratus lima puluh juta rupiah',
            'pageSizeCss' => '',
        ], $extra))->render();
    }

    public function test_print_number_accessor_uses_account_number()
    {
        $this->assertSame(self::NO_REKENING, $this->deposit->print_number);
    }

    /** Data lama yang account_number-nya kosong tidak boleh mencetak nomor kosong. */
    public function test_print_number_falls_back_to_internal_number_when_account_number_empty()
    {
        $this->deposit->account_number = null;

        $this->assertSame(self::NO_DEPOSITO, $this->deposit->print_number);
    }

    public function test_receipt_prints_account_number_not_deposit_number()
    {
        // View jatuh balik ke auth()->user() kalau deposito belum divalidasi.
        $this->actingAs($this->user);

        $html = $this->render('pages.fixed-deposit.receipt');

        $this->assertStringContainsString('No: ' . self::NO_REKENING, $html);
        $this->assertStringNotContainsString(self::NO_DEPOSITO, $html);
    }

    public function test_destroy_receipt_prints_account_number_not_deposit_number()
    {
        $this->actingAs($this->user);

        $html = $this->render('pages.fixed-deposit.destroy-receipt', ['deletedBy' => 'Manajer']);

        $this->assertStringContainsString('No: ' . self::NO_REKENING, $html);
        $this->assertStringNotContainsString(self::NO_DEPOSITO, $html);
    }

    public function test_list_report_prints_account_number_not_deposit_number()
    {
        $html = view('pages.fixed-deposit.print', [
            'title' => 'Laporan Deposito',
            'user' => $this->user,
            'date' => Carbon::now()->isoFormat('dddd, D MMMM Y'),
            'manager' => null,
            'data' => FixedDeposit::with('customer')->get(),
        ])->render();

        $this->assertStringContainsString(self::NO_REKENING, $html);
        $this->assertStringNotContainsString(self::NO_DEPOSITO, $html);
    }
}
