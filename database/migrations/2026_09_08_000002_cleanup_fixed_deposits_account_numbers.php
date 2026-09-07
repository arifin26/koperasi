<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\FixedDeposit;
use App\Models\Customer;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Data migration: Clean up NULL or anomalous account_number records
     * This ensures all fixed_deposits have valid, non-null account_number values
     */
    public function up()
    {
        // Get all fixed deposits with NULL or problematic account_number
        $depositsToFix = FixedDeposit::whereNull('account_number')
            ->orWhereRaw('account_number = ""')
            ->get();

        foreach ($depositsToFix as $deposit) {
            $customer = $deposit->customer;
            if ($customer) {
                // Generate account_number: DEP-CUSTOMER_ID-NNNNN format
                // Or use existing pattern from nearby records for this customer
                $accountNumber = $this->generateUniqueAccountNumber($customer->id);

                if ($accountNumber) {
                    $deposit->update(['account_number' => $accountNumber]);
                    echo "Fixed deposit #{$deposit->id} ({$deposit->number}): account_number = {$accountNumber}\n";
                }
            }
        }

        // Also check for account_number that match customer.number (should not happen, but just in case)
        $misnatchedDeposits = DB::table('fixed_deposits as fd')
            ->join('customers as c', 'fd.customer_id', '=', 'c.id')
            ->where('fd.account_number', '=', DB::raw('c.number'))
            ->whereNull('fd.deleted_at')
            ->get(['fd.id', 'fd.number', 'fd.account_number', 'c.number as customer_number']);

        foreach ($misnatchedDeposits as $record) {
            // This shouldn't happen after validation, but log it
            echo "WARNING: Deposit #{$record->id} ({$record->number}) has account_number ({$record->account_number}) " .
                 "same as customer.number ({$record->customer_number}). Please review manually.\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // No data rollback - we only filled in NULL values
        // If you need to revert, restore from backup
    }

    /**
     * Generate unique account_number for customer
     */
    private function generateUniqueAccountNumber($customerId)
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return null;
        }

        // Try format: DEP-CUSTOMER_ID-NNNN (e.g., DEP-5-0001)
        $basePrefix = 'DEP-' . $customerId . '-';

        // Find max sequence for this customer
        $lastDeposit = FixedDeposit::where('customer_id', $customerId)
            ->where('account_number', 'LIKE', $basePrefix . '%')
            ->orderBy('account_number', 'desc')
            ->first();

        if ($lastDeposit && $lastDeposit->account_number) {
            // Extract sequence number from existing account_number
            $parts = explode('-', $lastDeposit->account_number);
            $lastSeq = (int) end($parts);
            $newSeq = $lastSeq + 1;
        } else {
            $newSeq = 1;
        }

        $accountNumber = $basePrefix . str_pad($newSeq, 4, '0', STR_PAD_LEFT);

        // Ensure uniqueness for this customer
        while (FixedDeposit::where('customer_id', $customerId)
                ->where('account_number', $accountNumber)
                ->whereNull('deleted_at')
                ->exists()) {
            $newSeq++;
            $accountNumber = $basePrefix . str_pad($newSeq, 4, '0', STR_PAD_LEFT);
        }

        return $accountNumber;
    }
};
