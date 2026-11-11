<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['title', 'region_id', 'facility_id', 'author_id', 'audited_on', 'snapshot'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class);
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

    protected function casts(): array
    {
        return ['audited_on' => 'date', 'snapshot' => 'array'];
    }
}
