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
        Schema::create('inventory_issuance_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventory_issuance_id');
            $table->foreign('inventory_issuance_id')
                ->references('id')
                ->on('inventory_issuances');
            $table->uuid('inventory_supply_id');
            $table->foreign('inventory_supply_id')
                ->references('id')
                ->on('inventory_supplies');
            $table->integer('stock_no');
            $table->text('description');
            $table->string('inventory_item_no')->nullable();
            $table->string('property_no')->nullable();
            $table->integer('quantity');
            $table->string('estimated_useful_life')->nullable();
            $table->date('acquired_date')->nullable();
            $table->decimal('unit_cost', 20, 2)->default(0.00);
            $table->decimal('total_cost', 20, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_issuance_items');
    }
};
