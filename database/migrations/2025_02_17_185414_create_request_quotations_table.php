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
        Schema::create('request_quotations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_request_id');
            $table->foreign('purchase_request_id')
                ->references('id')
                ->on('purchase_requests');
            $table->enum('signed_type', ['bac', 'lce']);
            $table->string('rfq_no')->unique();
            $table->date('rfq_date');
            $table->uuid('supplier_id')->nullable();
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
            $table->dateTime('opening_dt')->nullable();
            $table->uuid('sig_approval_id')->nullable();
            $table->foreign('sig_approval_id')
                ->references('id')
                ->on('signatories');
            $table->boolean('vat_registered')->nullable();
            $table->tinyInteger('batch')->default(1);
            $table->string('status');
            $table->decimal('grand_total_cost', 20, 2)->default(0.00);
            $table->timestamp('canvassing_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_quotations');
    }
};
