<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Tambah is_default pada interest_rates jika belum ada
        if (!Schema::hasColumn('interest_rates', 'is_default')) {
            Schema::table('interest_rates', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('is_active');
            });

            // Set rate simpanan aktif existing sebagai default awal
            $activeSimpanan = DB::table('interest_rates')
                ->where('type', 'simpanan')
                ->where('is_active', 1)
                ->orderBy('effective_date', 'desc')
                ->first();

            if ($activeSimpanan) {
                DB::table('interest_rates')
                    ->where('id', $activeSimpanan->id)
                    ->update(['is_default' => true]);
            }
        }

        // 2. Tambah interest_rate_id pada fixed_deposits jika belum ada
        if (!Schema::hasColumn('fixed_deposits', 'interest_rate_id')) {
            Schema::table('fixed_deposits', function (Blueprint $table) {
                $table->unsignedBigInteger('interest_rate_id')->nullable()->after('customer_id');
                $table->foreign('interest_rate_id')->references('id')->on('interest_rates')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('fixed_deposits', 'interest_rate_id')) {
            Schema::table('fixed_deposits', function (Blueprint $table) {
                $table->dropForeign(['interest_rate_id']);
                $table->dropColumn('interest_rate_id');
            });
        }

        if (Schema::hasColumn('interest_rates', 'is_default')) {
            Schema::table('interest_rates', function (Blueprint $table) {
                $table->dropColumn('is_default');
            });
        }
    }
};
