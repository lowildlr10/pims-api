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
        Schema::create('inventory_supplies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_id');
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders');
            $table->uuid('po_item_id')->nullable();
            $table->foreign('po_item_id')
                ->references('id')
                ->on('purchase_order_items');
            $table->smallInteger('item_sequence');
            $table->string('sku')->nullable()->unique();
            $table->string('upc')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->uuid('item_classification_id')->nullable();
            $table->foreign('item_classification_id')
                ->references('id')
                ->on('item_classifications');
            $table->uuid('unit_issue_id');
            $table->foreign('unit_issue_id')
                ->references('id')
                ->on('unit_issues');
            $table->integer('quantity');
            $table->decimal('unit_cost', 20, 2)->default(0.00);
            $table->decimal('total_cost', 20, 2)->default(0.00);
            $table->enum('required_document', ['ics', 'are', 'ris']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_supplies');
    }
};
