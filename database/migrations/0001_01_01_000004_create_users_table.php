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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('employee_id')->unique();
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->enum('sex', ['male', 'female']);
            $table->uuid('division_id');
            $table->foreign('division_id')
                ->references('id')
                ->on('divisions');
            $table->uuid('section_id');
            $table->foreign('section_id')
                ->references('id')
                ->on('sections');
            $table->uuid('position_id');
            $table->foreign('position_id')
                ->references('id')
                ->on('positions');
            $table->uuid('designation_id')->nullable();
            $table->foreign('designation_id')
                ->references('id')
                ->on('designations');
            $table->string('username')->unique();
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique()->nullable();
            $table->string('password');
            $table->text('avatar')->nullable();
            $table->boolean('allow_signature')->default(false);
            $table->text('signature')->nullable();
            $table->boolean('restricted')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::table('divisions', function (Blueprint $table) {
            $table->uuid('division_head_id')
                ->after('division_name')
                ->nullable();
            $table->foreign('division_head_id')
                ->references('id')
                ->on('users');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->uuid('section_head_id')
                ->after('division_id')
                ->nullable();
            $table->foreign('section_head_id')
                ->references('id')
                ->on('users');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropColumn('division_head_id');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('section_head_id');
        });

        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
