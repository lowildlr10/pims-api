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
        Schema::create('inventory_issuances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('document_type', ['ris', 'ics', 'are']);
            $table->uuid('purchase_order_id');
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders');
            $table->uuid('responsibility_center_id')->nullable();
            $table->foreign('responsibility_center_id')
                ->references('id')
                ->on('responsibility_centers');
            $table->string('inventory_no');
            $table->date('inventory_date');
            $table->string('sai_no')->nullable();
            $table->date('sai_date')->nullable();
            $table->uuid('requested_by_id')->nullable();
            $table->foreign('requested_by_id')
                ->references('id')
                ->on('users');
            $table->date('requested_date')->nullable();
            $table->uuid('sig_approved_by_id')->nullable();
            $table->foreign('sig_approved_by_id')
                ->references('id')
                ->on('signatories');
            $table->date('approved_date')->nullable();
            $table->uuid('sig_issued_by_id')->nullable();
            $table->foreign('sig_issued_by_id')
                ->references('id')
                ->on('signatories');
            $table->date('issued_date')->nullable();
            $table->uuid('received_by_id')->nullable();
            $table->foreign('received_by_id')
                ->references('id')
                ->on('users');
            $table->date('received_date')->nullable();
            $table->string('status');
            $table->json('status_timestamps')->default(json_encode(new \stdClass));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_issuances');
    }
};
