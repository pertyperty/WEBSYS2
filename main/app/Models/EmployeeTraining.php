<?php

namespace App\Models;

use App\Enums\TrainingStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTraining extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'training_course_id', 'enrolled_at'];

    protected $attributes = ['status' => 'enrolled'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function trainingCourse(): BelongsTo
    {
        return $this->belongsTo(TrainingCourse::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $viewer): Builder
    {
        if ($viewer->is_active && in_array($viewer->role, [UserRole::Admin, UserRole::ComplianceOfficer], true)) {
            return $query;
        }

        return $query->whereHas('employee', fn (Builder $employees): Builder => $employees->visibleTo($viewer));
    }

    protected function casts(): array
    {
        return ['status' => TrainingStatus::class, 'enrolled_at' => 'datetime', 'completed_at' => 'date'];
    }
}
