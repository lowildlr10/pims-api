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
        Schema::create('logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
            $table->uuid('log_id')->nullable()->index();
            $table->string('log_module')->index();
            $table->enum('log_type', ['log', 'error'])->default('log')->index();
            $table->string('message');
            $table->text('details')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('logged_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
