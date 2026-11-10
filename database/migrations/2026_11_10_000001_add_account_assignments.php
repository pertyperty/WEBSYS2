<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['region_id', 'name']);
        });

        Schema::create('job_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff')->index();
            $table->boolean('is_active')->default(true);
            $table->foreignId('facility_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('job_role_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facility_id');
            $table->dropConstrainedForeignId('job_role_id');
            $table->dropConstrainedForeignId('region_id');
            $table->dropColumn(['role', 'is_active']);
        });

        Schema::dropIfExists('job_roles');
        Schema::dropIfExists('facilities');
        Schema::dropIfExists('regions');
    }
};
