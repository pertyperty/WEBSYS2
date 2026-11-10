<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('source', 40);
            $table->string('status', 30);
            $table->unsignedInteger('required_count');
            $table->unsignedInteger('satisfied_count');
            $table->decimal('percentage', 5, 2)->nullable();
            $table->json('snapshot');
            $table->char('fingerprint', 64);
            $table->date('assessed_on');
            $table->timestamp('created_at');
            $table->index(['employee_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_assessments');
    }
};
