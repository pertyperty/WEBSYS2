<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Qualification extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'safety_domain_id', 'requires_expiry', 'requires_training', 'is_active'];

    public function safetyDomain(): BelongsTo
    {
        return $this->belongsTo(SafetyDomain::class);
    }

    public function jobRoles(): BelongsToMany
    {
        return $this->belongsToMany(JobRole::class)->withTimestamps();
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'employee_qualification', 'qualification_id', 'employee_id')->withPivot('credential_document_id')->withTimestamps();
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'requires_expiry' => 'boolean', 'requires_training' => 'boolean'];
    }

    public function trainingCourses(): HasMany
    {
        return $this->hasMany(TrainingCourse::class);
    }
}
