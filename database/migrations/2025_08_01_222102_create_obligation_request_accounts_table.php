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
        Schema::create('obligation_request_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('obligation_request_id');
            $table->foreign('obligation_request_id')
                ->references('id')
                ->on('obligation_requests');
            $table->uuid('account_id');
            $table->foreign('account_id')
                ->references(columns: 'id')
                ->on('accounts');
            $table->decimal('amount', 20, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obligation_request_accounts');
    }
};
