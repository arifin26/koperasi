<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix: Change account_number constraint from global unique to composite unique (customer_id, account_number)
     * This allows each customer to have their own unique account_number for deposits
     */
    public function up()
    {
        Schema::table('fixed_deposits', function (Blueprint $table) {
            // Drop the existing global unique constraint
            $table->dropUnique(['account_number']);
        });

        // Add new composite unique constraint: (customer_id, account_number)
        // This ensures 1 customer = 1 account_number (per-customer unique)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE fixed_deposits ADD CONSTRAINT unique_customer_account_number UNIQUE (customer_id, account_number)');
        } else {
            Schema::table('fixed_deposits', function (Blueprint $table) {
                $table->unique(['customer_id', 'account_number'], 'unique_customer_account_number');
            });
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE fixed_deposits DROP CONSTRAINT unique_customer_account_number');
        } else {
            Schema::table('fixed_deposits', function (Blueprint $table) {
                $table->dropUnique('unique_customer_account_number');
            });
        }
        Schema::table('fixed_deposits', function (Blueprint $table) {
            // Restore the old (wrong) constraint for rollback
            $table->unique(['account_number']);
        });
    }
};
