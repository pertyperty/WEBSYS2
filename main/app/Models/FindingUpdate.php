<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FindingUpdate extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['author_id', 'status', 'note', 'assignee_name'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
