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
        Schema::create('supplies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_id')->nullable();
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders');
            $table->uuid('po_item_id')->nullable();
            $table->foreign('po_item_id')
                ->references('id')
                ->on('purchase_order_items');
            $table->string('sku')->nullable()->unique();
            $table->string('upc')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->uuid('item_classification_id')->nullable();
            $table->foreign('item_classification_id')
                ->references('id')
                ->on('item_classifications');
            $table->decimal('unit_cost', 20, 2)->default(0.00);
            $table->enum('required_document', ['ics', 'are', 'ris']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};
