<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('qualification_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_training_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('facility_id')->constrained()->restrictOnDelete();
            $table->foreignId('job_role_id')->constrained()->restrictOnDelete();
            $table->uuid('reference')->unique();
            $table->string('private_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->date('issued_at');
            $table->date('expires_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->index(['employee_id', 'qualification_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('credential_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('credential_document_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('type');
            $table->text('note')->nullable();
            $table->timestamp('created_at');
            $table->unique(['credential_document_id', 'type']);
        });

        Schema::table('employee_qualification', function (Blueprint $table): void {
            $table->foreignId('credential_document_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_qualification', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('credential_document_id');
        });
        Schema::dropIfExists('credential_events');
        Schema::dropIfExists('credential_documents');
    }
};
