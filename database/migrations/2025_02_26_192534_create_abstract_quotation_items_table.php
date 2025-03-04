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
        Schema::create('abstract_quotation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('abstract_quotation_id');
            $table->foreign('abstract_quotation_id')
                ->references('id')
                ->on('abstract_quotations');
            $table->uuid('pr_item_id');
            $table->foreign('pr_item_id')
                ->references('id')
                ->on('purchase_request_items');
            $table->uuid('awardee_id')->nullable();
            $table->foreign('awardee_id')
                ->references('id')
                ->on('suppliers');
            $table->boolean('included')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abstract_quotation_items');
    }
};
