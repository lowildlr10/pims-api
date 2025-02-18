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
        Schema::create('request_quotation_canvassers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('request_quotation_id');
            $table->foreign('request_quotation_id')
                ->references('id')
                ->on('request_quotations');
            $table->uuid('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_quotation_canvassers');
    }
};
