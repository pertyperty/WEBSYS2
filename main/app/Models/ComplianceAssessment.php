<?php

namespace App\Models;

use App\Enums\ComplianceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceAssessment extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['employee_id', 'actor_id', 'source', 'status', 'required_count', 'satisfied_count', 'percentage', 'snapshot', 'fingerprint', 'assessed_on'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return ['status' => ComplianceStatus::class, 'snapshot' => 'array', 'percentage' => 'decimal:2', 'assessed_on' => 'date'];
    }
}
