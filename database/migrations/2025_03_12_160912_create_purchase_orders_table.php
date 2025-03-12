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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_request_id');
            $table->foreign('purchase_request_id')
                ->references('id')
                ->on('purchase_requests');
            $table->string('po_no');
            $table->string('po_date')->nullable();
            $table->uuid('mode_procurement_id');
            $table->foreign('mode_procurement_id')
                ->references('id')
                ->on('procurement_modes');
            $table->uuid('supplier_id');
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
            $table->uuid('place_delivery_id')->nullable();
            $table->foreign('place_delivery_id')
                ->references('id')
                ->on('locations');
            $table->date('delivery_date')->nullable();
            $table->uuid('delivery_term_id')->nullable();
            $table->foreign('delivery_term_id')
                ->references('id')
                ->on('delivery_terms');
            $table->uuid('payment_term_id')->nullable();
            $table->foreign('payment_term_id')
                ->references('id')
                ->on('payment_terms');
            $table->text('total_amount_words')->nullable();
            $table->decimal('total_amount', 20, 2)->default(0.00);
            $table->uuid('sig_approval_id')->nullable();
            $table->foreign('sig_approval_id')
                ->references('id')
                ->on('signatories');
            $table->enum('document_type', ['po', 'jo']);
            $table->string('status');
            $table->timestamp('pending_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
