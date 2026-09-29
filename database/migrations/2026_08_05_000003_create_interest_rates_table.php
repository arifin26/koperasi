<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('interest_rates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->decimal('rate_percent', 5, 2);
            $table->date('effective_date');
            $table->boolean('is_active')->default(1);
            $table->text('notes')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('effective_date');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('interest_rates');
    }
};
