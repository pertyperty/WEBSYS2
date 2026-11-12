<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'region_id', 'is_active'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function verifiableBy(Builder $query, User $auditor): Builder
    {
        if (! $auditor->is_active || $auditor->role !== UserRole::Auditor || ! $auditor->region_id) {
            return $query->whereKey([]);
        }

        return $query->active()->where('region_id', $auditor->region_id)
            ->whereHas('region', fn (Builder $regions): Builder => $regions->active());
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
