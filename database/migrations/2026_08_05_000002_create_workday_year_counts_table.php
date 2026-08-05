<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workday_year_counts', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedSmallInteger('workday_count');
            $table->timestamp('calculated_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('workday_year_counts');
    }
};
