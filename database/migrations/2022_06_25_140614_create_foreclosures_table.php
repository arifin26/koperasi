<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('foreclosures', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->unsignedBigInteger('collateral_amount')->default(0);
            $table->unsignedBigInteger('remaining_amount')->default(0);
            $table->unsignedBigInteger('return_amount')->default(0);
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('collateral_id')->constrained('collaterals')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('foreclosures');
    }
};
