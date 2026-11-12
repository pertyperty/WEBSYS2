<?php

namespace App\Models;

use App\Enums\CredentialEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['credential_document_id', 'actor_id', 'type', 'note'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function credentialDocument(): BelongsTo
    {
        return $this->belongsTo(CredentialDocument::class);
    }

    protected function casts(): array
    {
        return ['type' => CredentialEventType::class];
    }
}
