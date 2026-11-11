<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'verification_reference'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'staff',
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->verification_reference ??= (string) Str::uuid();
        });
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function jobRole(): BelongsTo
    {
        return $this->belongsTo(JobRole::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function isAssignedStaff(): bool
    {
        return $this->role === UserRole::Staff && $this->facility_id !== null && $this->job_role_id !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function qualifications(): BelongsToMany
    {
        return $this->belongsToMany(Qualification::class, 'employee_qualification', 'employee_id', 'qualification_id')->withPivot('credential_document_id')->withTimestamps();
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $viewer): Builder
    {
        $query->where('role', UserRole::Staff);

        if (! $viewer->is_active) {
            return $query->whereKey([]);
        }

        return match ($viewer->role) {
            UserRole::Admin, UserRole::ComplianceOfficer => $query,
            UserRole::RegionalDirector => $viewer->region_id
                ? $query->whereHas('facility', fn (Builder $facilities): Builder => $facilities->where('region_id', $viewer->region_id))
                : $query->whereKey([]),
            UserRole::FacilityManager => $viewer->facility_id
                ? $query->where('facility_id', $viewer->facility_id)
                : $query->whereKey([]),
            UserRole::Staff => $query->whereKey($viewer->id),
            UserRole::Auditor => $query->whereKey([]),
        };
    }

    public function credentialDocuments(): HasMany
    {
        return $this->hasMany(CredentialDocument::class, 'employee_id');
    }

    public function employeeTrainings(): HasMany
    {
        return $this->hasMany(EmployeeTraining::class, 'employee_id');
    }

    public function trainingCourses(): BelongsToMany
    {
        return $this->belongsToMany(TrainingCourse::class, 'employee_trainings', 'employee_id', 'training_course_id')
            ->withPivot(['id', 'status', 'enrolled_at', 'completed_at', 'completed_by', 'feedback'])->withTimestamps();
    }
}
