<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (!Schema::hasColumn('deposits', 'notes')) {
                $table->text('notes')->nullable()->after('current_balance');
            }
            if (!Schema::hasColumn('deposits', 'is_system_generated')) {
                $table->boolean('is_system_generated')->default(false);
            }
            if (!Schema::hasColumn('deposits', 'period')) {
                $table->string('period', 20)->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (Schema::hasColumn('deposits', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('deposits', 'is_system_generated')) {
                $table->dropColumn('is_system_generated');
            }
            if (Schema::hasColumn('deposits', 'period')) {
                $table->dropColumn('period');
            }
        });
    }
};
