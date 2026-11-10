<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['regions', 'facilities', 'job_roles'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->boolean('is_active')->default(true);
            });
        }

        Schema::create('safety_domains', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('qualifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('safety_domain_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->boolean('requires_expiry')->default(true);
            $table->boolean('requires_training')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['safety_domain_id', 'name']);
        });

        Schema::create('job_role_qualification', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_role_id')->constrained()->restrictOnDelete();
            $table->foreignId('qualification_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['job_role_id', 'qualification_id']);
        });

        Schema::create('employee_qualification', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('qualification_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'qualification_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_qualification');
        Schema::dropIfExists('job_role_qualification');
        Schema::dropIfExists('qualifications');
        Schema::dropIfExists('safety_domains');

        foreach (['regions', 'facilities', 'job_roles'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropColumn('is_active');
            });
        }
    }
};
