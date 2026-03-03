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
        Schema::create('tax_withholdings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type')->unique();
            $table->boolean('is_vat')->default(false);
            $table->decimal('ewt_rate', 5, 4)->default(0);
            $table->decimal('ptax_rate', 5, 4)->default(0);
            $table->boolean('active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_withholdings');
    }
};
