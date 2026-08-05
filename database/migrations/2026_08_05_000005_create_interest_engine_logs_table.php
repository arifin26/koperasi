<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('interest_engine_logs', function (Blueprint $table) {
            $table->id();
            $table->date('run_date')->unique();
            $table->enum('status', ['success', 'skipped', 'failed']);
            $table->string('reason', 255)->nullable();
            $table->unsignedInteger('total_customers')->default(0);
            $table->unsignedBigInteger('total_interest')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('interest_engine_logs');
    }
};
