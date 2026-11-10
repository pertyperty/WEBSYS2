<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditFinding extends Model
{
    use HasFactory;

    protected $fillable = ['audit_id', 'facility_id', 'region_id', 'title', 'description', 'assignee_id', 'due_on'];

    protected $attributes = ['status' => 'open'];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(FindingUpdate::class)->orderBy('id');
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $viewer): Builder
    {
        if (! $viewer->is_active) {
            return $query->whereKey([]);
        }

        return match ($viewer->role) {
            UserRole::Admin, UserRole::ComplianceOfficer => $query,
            UserRole::RegionalDirector => $viewer->region_id ? $query->where('region_id', $viewer->region_id) : $query->whereKey([]),
            UserRole::FacilityManager => $viewer->facility_id ? $query->where('facility_id', $viewer->facility_id) : $query->whereKey([]),
            default => $query->whereKey([]),
        };
    }

    public function canReceiveUpdateFrom(User $user): bool
    {
        return $user->is_active && ($user->role === UserRole::ComplianceOfficer
            || ($user->role === UserRole::FacilityManager && $user->id === $this->assignee_id && $user->facility_id === $this->facility_id));
    }

    protected function casts(): array
    {
        return ['due_on' => 'date', 'resolved_at' => 'datetime'];
    }
}
