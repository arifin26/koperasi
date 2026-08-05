<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fixed_deposits', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('amount');
            $table->unsignedTinyInteger('tenor_months');
            $table->decimal('rate_percent', 5, 2);
            $table->date('start_date');
            $table->date('maturity_date');
            $table->enum('status', ['active', 'matured', 'extended', 'liquidated'])->default('active');
            $table->unsignedBigInteger('extended_from_id')->nullable();
            $table->timestamp('liquidated_at')->nullable();
            $table->timestamp('matured_at')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('status');
            $table->index('maturity_date');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('extended_from_id')->references('id')->on('fixed_deposits')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('fixed_deposits');
    }
};
