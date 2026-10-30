<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassbookPrintTest extends TestCase
{
    use RefreshDatabase;

    protected User $teller;
    protected Customer $customer;
    protected Deposit $deposit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teller = User::create([
            'name' => 'Teller Test',
            'username' => 'tellerpassbook',
            'password' => bcrypt('password'),
            'role' => 'teller',
            'phone' => '08123456789',
        ]);

        $this->customer = Customer::create([
            'number' => 'NAS-001',
            'name' => 'Nasabah Buku Tabungan',
            'nik' => '1234567890123456',
            'address' => 'Jl. Koperasi No. 10',
            'status' => 'active',
            'joined_at' => Carbon::now(),
        ]);

        $this->deposit = Deposit::create([
            'customer_id' => $this->customer->id,
            'type' => 'sukarela',
            'amount' => 250000,
            'previous_balance' => 0,
            'current_balance' => 250000,
            'created_by' => $this->teller->id,
            'created_at' => Carbon::parse('2025-05-10 10:00:00'),
        ]);
    }

    /**
     * Tamu yang belum login dialihkan ke halaman login.
     */
    public function test_unauthenticated_user_cannot_access_passbook_print(): void
    {
        $response = $this->get(route('transaction.deposit.passbook', $this->deposit->id));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    /**
     * Halaman cetak buku tabungan dengan row=2:
     * - Status 200
     * - Default margin kiri 0mm (mentok ke kiri)
     * - Lebar tabel eksplisit 138mm
     * - Baris 1 berupa baris kosong (empty-row)
     * - Baris 2 memiliki nomor 2 pada kolom col-no
     * - Urutan kolom: col-no, col-date, col-code, col-debit, col-credit, col-balance, col-teller
     */
    public function test_passbook_print_defaults_to_zero_margins_and_138mm_table_width(): void
    {
        $response = $this->actingAs($this->teller)->get(
            route('transaction.deposit.passbook', ['simpanan' => $this->deposit->id, 'row' => 2])
        );

        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Margin default 0mm pada .passbook-page
        $this->assertStringContainsString('padding-left: 0mm;', $content);
        $this->assertStringContainsString('padding-top: 0mm;', $content);
        $this->assertStringContainsString('padding-right: 0mm;', $content);

        // 2. Lebar tabel 138mm agar tidak melar/stretch melebihi total kolom
        $this->assertStringContainsString("width: 138mm;\n            border-collapse: collapse;", $content);

        // 3. Lebar col-no tetap 8mm
        $this->assertStringContainsString(".col-no {\n            width: 8mm;\n            text-align: center;", $content);

        // 4. Baris 1 adalah baris kosong sebelum startRow=2
        $this->assertMatchesRegularExpression(
            '/<tr class="empty-row">\s*<td class="col-no">&nbsp;<\/td>\s*<td class="col-date">&nbsp;<\/td>/',
            $content
        );

        // 5. Baris 2 menampilkan angka baris '2' pada kolom pertama (col-no)
        $this->assertMatchesRegularExpression(
            '/<tr>\s*<td class="col-no">\s*2\s*<\/td>/',
            $content
        );

        // 6. Urutan kolom dalam baris transaksi presisi
        $this->assertMatchesRegularExpression(
            '/<td class="col-no">\s*2\s*<\/td>\s*<td class="col-date">10\/05\/25<\/td>\s*<td class="col-code">1<\/td>\s*<td class="col-debit">-<\/td>\s*<td class="col-credit">250\.000<\/td>\s*<td class="col-balance">250\.000<\/td>\s*<td class="col-teller">TEL<\/td>/',
            $content
        );
    }

    /**
     * Parameter query opsional left_margin, top_margin, right_margin dapat mengubah padding.
     */
    public function test_passbook_print_supports_custom_margins_via_query_params(): void
    {
        $response = $this->actingAs($this->teller)->get(
            route('transaction.deposit.passbook', [
                'simpanan' => $this->deposit->id,
                'row' => 2,
                'left_margin' => '2mm',
                'top_margin' => '5mm',
                'right_margin' => '3mm',
            ])
        );

        $response->assertStatus(200);

        $content = $response->getContent();

        $this->assertStringContainsString('padding-left: 2mm;', $content);
        $this->assertStringContainsString('padding-top: 5mm;', $content);
        $this->assertStringContainsString('padding-right: 3mm;', $content);
    }
}
