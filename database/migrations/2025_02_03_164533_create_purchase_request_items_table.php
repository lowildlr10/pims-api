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
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_request_id');
            $table->foreign('purchase_request_id')
                ->references('id')
                ->on('purchase_requests');
            $table->smallInteger('item_sequence');
            $table->integer('quantity');
            $table->uuid('unit_issue_id');
            $table->foreign('unit_issue_id')
                ->references('id')
                ->on('unit_issues');
            $table->text('description');
            $table->integer('stock_no');
            $table->decimal('estimated_unit_cost', 20, 2);
            $table->decimal('estimated_cost', 20, 2);
            $table->uuid('awarded_to_id')->nullable();
            $table->foreign('awarded_to_id')
                ->references('id')
                ->on('suppliers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};
