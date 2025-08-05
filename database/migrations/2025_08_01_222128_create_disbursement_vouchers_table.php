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
        Schema::create('disbursement_vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_request_id');
            $table->foreign('purchase_request_id')
                ->references('id')
                ->on('purchase_requests');
            $table->uuid('purchase_order_id');
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders');
            $table->uuid('obligation_request_id');
            $table->foreign('obligation_request_id')
                ->references('id')
                ->on('obligation_requests'); 
            $table->string('dv_no');
            $table->enum('mode_payment', ['check', 'cash', 'other'])->nullable();
            $table->uuid('payee_id');
            $table->foreign('payee_id')
                ->references('id')
                ->on('suppliers');
            $table->text('address')->nullable();
            $table->string('office')->nullable();
            $table->uuid('responsibility_center_id');
            $table->foreign('responsibility_center_id')
                ->references('id')
                ->on('responsibility_centers');
            $table->text(column: 'explanation')->nullable();
            $table->decimal('total_amount', 20, 2)->default(0.00);
            $table->json('accountant_certified_choices')
                ->nullable()
                ->default(json_encode([
                    'allotment_obligated' => false,
                    'document_complete' => false
                ]));
            $table->uuid('sig_accountant_id')->nullable();
            $table->foreign('sig_accountant_id')
                ->references('id')
                ->on('signatories');
            $table->date('accountant_signed_date')->nullable();
            $table->uuid('sig_treasurer_id')->nullable();
            $table->foreign('sig_treasurer_id')
                ->references('id')
                ->on('signatories');
            $table->date('treasurer_signed_date')->nullable();
            $table->uuid('sig_head_id')->nullable();
            $table->foreign('sig_head_id')
                ->references('id')
                ->on('signatories');
            $table->date('head_signed_date')->nullable();
            $table->string('check_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->date('check_date')->nullable();
            $table->string('received_name')->nullable();
            $table->date('received_date')->nullable();
            $table->string('or_other_document')->nullable();
            $table->string('jev_no')->nullable();
            $table->date('jev_date')->nullable();
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
        Schema::dropIfExists('disbursement_vouchers');
    }
};
