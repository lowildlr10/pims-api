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
        Schema::create('obligation_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_request_id');
            $table->foreign('purchase_request_id')
                ->references('id')
                ->on('purchase_requests');
            $table->uuid('purchase_order_id');
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders');
            $table->json('funding')
                ->nullable()
                ->default(json_encode([
                    'general' => false,
                    'mdf_20' => false,
                    'gf_mdrrmf_5' => false,
                    'sef' => false
                ]));
            $table->uuid('payee_id');
            $table->foreign('payee_id')
                ->references('id')
                ->on('suppliers');
            $table->string('obr_no');
            $table->string('office')->nullable();
            $table->text('address')->nullable();
            $table->uuid('responsibility_center_id')->nullable();
            $table->foreign('responsibility_center_id')
                ->references('id')
                ->on('responsibility_centers');
            $table->text('particulars')->nullable();
            $table->decimal('total_amount', 20, 2)->default(0.00);
            $table->json('compliance_status')
                ->nullable()
                ->default(json_encode([
                    'allotment_necessary' => false,
                    'document_valid' => false
                ]));
            $table->uuid('sig_head_id')->nullable();
            $table->foreign('sig_head_id')
                ->references('id')
                ->on('signatories');
            $table->date('head_signed_date')->nullable();
            $table->uuid('sig_budget_id')->nullable();
            $table->foreign('sig_budget_id')
                ->references('id')
                ->on('signatories');
            $table->date('budget_signed_date')->nullable();
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
        Schema::dropIfExists('obligation_requests');
    }
};
