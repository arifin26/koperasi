<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('daily_interest_accumulations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->enum('savings_type', ['sukarela', 'wajib']);
            $table->date('calculation_date');
            $table->unsignedBigInteger('base_balance');
            $table->decimal('rate_percent', 5, 2);
            $table->unsignedBigInteger('interest_amount');
            $table->boolean('is_posted')->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'calculation_date', 'savings_type'], 'uq_customer_date_type');
            $table->index('calculation_date');
            $table->index('is_posted');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_interest_accumulations');
    }
};
