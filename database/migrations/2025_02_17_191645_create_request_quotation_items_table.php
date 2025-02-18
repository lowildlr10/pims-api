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
        Schema::create('request_quotation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('request_quotation_id');
            $table->foreign('request_quotation_id')
                ->references('id')
                ->on('request_quotations');
            $table->uuid('pr_item_id');
            $table->foreign('pr_item_id')
                ->references('id')
                ->on('purchase_request_items');
            $table->uuid('supplier_id')->nullable();
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
            $table->string('brand_model')->nullable();
            $table->decimal('unit_cost', 20, 2)->default(0.00);
            $table->decimal('total_cost', 20, 2)->default(0.00);
            $table->boolean('included')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_quotation_items');
    }
};
