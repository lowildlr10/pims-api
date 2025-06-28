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
        Schema::create('inspection_acceptance_report_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inspection_acceptance_report_id');
            $table->foreign('inspection_acceptance_report_id')
                ->references('id')
                ->on('inspection_acceptance_reports');
            $table->uuid('pr_item_id');
            $table->foreign('pr_item_id')
                ->references('id')
                ->on('purchase_request_items');
            $table->uuid('po_item_id');
            $table->foreign('po_item_id')
                ->references('id')
                ->on('purchase_order_items');
            $table->boolean('accepted')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_acceptance_report_items');
    }
};
