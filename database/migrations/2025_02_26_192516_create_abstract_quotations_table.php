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
        Schema::create('abstract_quotations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_request_id');
            $table->foreign('purchase_request_id')
                ->references('id')
                ->on('purchase_requests');
            $table->uuid('bids_awards_committee_id')->nullable();
            $table->foreign('bids_awards_committee_id')
                ->references('id')
                ->on('bids_awards_committees');
            $table->uuid('mode_procurement_id')->nullable();
            $table->foreign('mode_procurement_id')
                ->references('id')
                ->on('procurement_modes');
            $table->string('solicitation_no');
            $table->date('solicitation_date');
            $table->date('opened_on')->nullable();
            $table->string('abstract_no');
            $table->text('bac_action')->nullable();
            $table->uuid('sig_twg_chairperson_id')->nullable();
            $table->foreign('sig_twg_chairperson_id')
                ->references('id')
                ->on('signatories');
            $table->uuid('sig_twg_member_1_id')->nullable();
            $table->foreign('sig_twg_member_1_id')
                ->references('id')
                ->on('signatories');
            $table->uuid('sig_twg_member_2_id')->nullable();
            $table->foreign('sig_twg_member_2_id')
                ->references('id')
                ->on('signatories');
            $table->uuid('sig_chairman_id')->nullable();
            $table->foreign('sig_chairman_id')
                ->references('id')
                ->on('signatories');
            $table->uuid('sig_vice_chairman_id')->nullable();
            $table->foreign('sig_vice_chairman_id')
                ->references('id')
                ->on('signatories');
            $table->uuid('sig_member_1_id')->nullable();
            $table->foreign('sig_member_1_id')
                ->references('id')
                ->on('signatories');
            $table->uuid('sig_member_2_id')->nullable();
            $table->foreign('sig_member_2_id')
                ->references('id')
                ->on('signatories');
            $table->uuid('sig_member_3_id')->nullable();
            $table->foreign('sig_member_3_id')
                ->references('id')
                ->on('signatories');
            $table->string('status');
            $table->timestamp('pending_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abstract_quotations');
    }
};
