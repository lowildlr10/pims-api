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
            $table->uuid('supply_id');
            $table->foreign('supply_id')
                ->references('id')
                ->on('supplies');
            $table->text('description');
            $table->string('property_no')->nullable();
            $table->string('serial_no')->nullable();
            $table->integer('quantity');
            $table->date('estimated_useful_life')->nullable();
            $table->date('acquired_date')->nullable();
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
