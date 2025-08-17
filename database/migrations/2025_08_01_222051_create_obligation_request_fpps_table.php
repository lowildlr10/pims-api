<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('obligation_request_fpps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('obligation_request_id');
            $table->foreign('obligation_request_id')
                ->references('id')
                ->on('obligation_requests');
            $table->uuid('fpp_id');
            $table->foreign('fpp_id')
                ->references('id')
                ->on('function_program_projects');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obligation_request_fpps');
    }
};
