<?php

namespace App\Models;

use App\Enums\CredentialStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Hidden(['private_path'])]
class CredentialDocument extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'qualification_id', 'employee_training_id', 'facility_id', 'job_role_id', 'reference', 'private_path', 'mime_type', 'file_size', 'issued_at', 'expires_at'];

    protected $attributes = ['status' => 'pending'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class);
    }

    public function employeeTraining(): BelongsTo
    {
        return $this->belongsTo(EmployeeTraining::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function jobRole(): BelongsTo
    {
        return $this->belongsTo(JobRole::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CredentialEvent::class)->orderBy('id');
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $viewer): Builder
    {
        if (! $viewer->is_active) {
            return $query->whereKey([]);
        }

        return match ($viewer->role) {
            UserRole::Admin, UserRole::ComplianceOfficer => $query,
            UserRole::Staff => $query->where('employee_id', $viewer->id),
            default => $query->whereKey([]),
        };
    }

    protected function casts(): array
    {
        return ['status' => CredentialStatus::class, 'issued_at' => 'date', 'expires_at' => 'date', 'file_size' => 'integer'];
    }
}
