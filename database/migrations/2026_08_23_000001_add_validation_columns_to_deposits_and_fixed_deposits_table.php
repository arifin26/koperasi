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
        Schema::table('deposits', function (Blueprint $table) {
            $table->timestamp('validated_at')->nullable()->after('notes');
            $table->unsignedBigInteger('validated_by')->nullable()->after('validated_at');

            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('fixed_deposits', function (Blueprint $table) {
            $table->timestamp('validated_at')->nullable()->after('notes');
            $table->unsignedBigInteger('validated_by')->nullable()->after('validated_at');

            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['validated_at', 'validated_by']);
        });

        Schema::table('fixed_deposits', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['validated_at', 'validated_by']);
        });
    }
};
