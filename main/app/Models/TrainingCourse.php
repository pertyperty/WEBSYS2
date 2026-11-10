<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingCourse extends Model
{
    use HasFactory;

    protected $fillable = ['qualification_id', 'title', 'description', 'status'];

    protected $attributes = ['status' => 'draft'];

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'employee_trainings', 'training_course_id', 'employee_id')
            ->withPivot(['id', 'status', 'enrolled_at', 'completed_at', 'completed_by', 'feedback'])->withTimestamps();
    }

    public function acceptsEnrollment(): bool
    {
        return $this->status === CourseStatus::Published
            && $this->qualification->is_active
            && $this->qualification->requires_training
            && $this->qualification->safetyDomain->is_active;
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $viewer): Builder
    {
        if (! $viewer->is_active || $viewer->role === UserRole::Auditor) {
            return $query->whereKey([]);
        }
        if (in_array($viewer->role, [UserRole::Admin, UserRole::ComplianceOfficer], true)) {
            return $query;
        }

        return $query->where(function (Builder $courses) use ($viewer): void {
            $courses->where('status', CourseStatus::Published)
                ->orWhereHas('enrollments', fn (Builder $enrollments): Builder => $enrollments->visibleTo($viewer));
        });
    }

    protected function casts(): array
    {
        return ['status' => CourseStatus::class];
    }
}
