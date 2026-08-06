<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE interest_rates MODIFY type VARCHAR(50)");
    }

    public function down()
    {
        DB::statement("ALTER TABLE interest_rates MODIFY type VARCHAR(50)");
    }
};
