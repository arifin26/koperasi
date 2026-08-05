<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('deposit_interest_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fixed_deposit_id');
            $table->string('period', 7); // YYYY-MM
            $table->unsignedBigInteger('interest_amount');
            $table->unsignedBigInteger('savings_txn_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['fixed_deposit_id', 'period'], 'uq_deposit_period');
            $table->foreign('fixed_deposit_id')->references('id')->on('fixed_deposits');
            $table->foreign('savings_txn_id')->references('id')->on('deposits')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deposit_interest_payments');
    }
};
