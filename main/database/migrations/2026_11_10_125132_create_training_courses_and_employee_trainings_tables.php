<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('qualification_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('employee_trainings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('training_course_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('enrolled');
            $table->timestamp('enrolled_at');
            $table->date('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('feedback')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'training_course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_trainings');
        Schema::dropIfExists('training_courses');
    }
};
