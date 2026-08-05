<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('interest_posting_logs', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->unique();
            $table->enum('status', ['success', 'failed', 'manual']);
            $table->unsignedInteger('total_customers')->default(0);
            $table->unsignedBigInteger('total_interest')->default(0);
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('posted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('interest_posting_logs');
    }
};
