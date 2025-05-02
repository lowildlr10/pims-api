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
        Schema::create('inspection_acceptance_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_request_id');
            $table->foreign('purchase_request_id')
                ->references('id')
                ->on('purchase_requests');
            $table->uuid('purchase_order_id');
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders');
            $table->uuid('supplier_id');
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
            $table->string('iar_no');
            $table->string('iar_date')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('invoice_date')->nullable();
            $table->string('inspected_date')->nullable();
            $table->boolean('inspected')->default(false);
            $table->uuid('sig_inspection_id')->nullable();
            $table->foreign('sig_inspection_id')
                ->references('id')
                ->on('signatories');
            $table->string('received_date')->nullable();
            $table->boolean('acceptance_complete')->default(false);
            $table->boolean('acceptance_partial')->default(false);
            $table->uuid('sig_acceptance_id')->nullable();
            $table->foreign('sig_acceptance_id')
                ->references('id')
                ->on('signatories');
            $table->string('status');
            $table->json('status_timestamps');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_acceptance_reports');
    }
};
