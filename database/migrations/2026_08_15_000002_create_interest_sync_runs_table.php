<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInterestSyncRunsTable extends Migration
{
    public function up()
    {
        Schema::create('interest_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('User who triggered the sync');
            $table->date('sync_from_date')->comment('Earliest date to catch up from');
            $table->date('sync_to_date')->comment('Latest date to sync to');
            $table->enum('status', ['pending', 'running', 'success', 'failed'])->default('pending');
            $table->integer('customers_processed')->default(0);
            $table->integer('total_interest_calculated')->default(0);
            $table->text('error_message')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('interest_sync_runs');
    }
}
