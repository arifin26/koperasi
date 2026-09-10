<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auto_interest_run_logs', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->unique(); // Y-m format
            $table->timestamp('triggered_at')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('triggered_by')->default('auto'); // 'auto' or user_id
            $table->json('savings_result')->nullable();
            $table->json('deposit_result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('period');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_interest_run_logs');
    }
};
