<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Region extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }

    public function employees(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Facility::class, 'region_id', 'facility_id')
            ->where('users.role', UserRole::Staff->value);
    }
}
