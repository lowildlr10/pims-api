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
        Schema::create('abstract_quotation_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('abstract_quotation_id');
            $table->foreign('abstract_quotation_id')
                ->references('id')
                ->on('abstract_quotations');
            $table->uuid('aoq_item_id');
            $table->foreign('aoq_item_id')
                ->references('id')
                ->on('abstract_quotation_items')
                ->onDelete('cascade');
            $table->uuid('supplier_id');
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
            $table->string('brand_model')->nullable();
            $table->decimal('unit_cost', 20, 2)->nullable();
            $table->decimal('total_cost', 20, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abstract_quotation_details');
    }
};
