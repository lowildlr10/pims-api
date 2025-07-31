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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('department_id');
            $table->foreign('department_id')
                ->references('id')
                ->on('departments');
            $table->uuid('section_id')->nullable();
            $table->foreign('section_id')
                ->references('id')
                ->on('sections');
            $table->string('pr_no')->unique();
            $table->date('pr_date');
            $table->string('sai_no')->nullable();
            $table->date('sai_date')->nullable();
            $table->string('alobs_no')->nullable();
            $table->date('alobs_date')->nullable();
            $table->text('purpose');
            $table->uuid('funding_source_id')->nullable();
            $table->foreign('funding_source_id')
                ->references('id')
                ->on('funding_sources');
            $table->uuid('requested_by_id');
            $table->foreign('requested_by_id')
                ->references('id')
                ->on('users');
            $table->uuid('sig_cash_availability_id')->nullable();
            $table->foreign('sig_cash_availability_id')
                ->references('id')
                ->on('signatories');
            $table->uuid('sig_approved_by_id')->nullable();
            $table->foreign('sig_approved_by_id')
                ->references('id')
                ->on('signatories');
            $table->tinyInteger('rfq_batch')->default(1);
            $table->decimal('total_estimated_cost', 20, 2)->default(0.00);
            $table->text('disapproved_reason')->nullable();
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
        Schema::dropIfExists('purchase_requests');
    }
};
