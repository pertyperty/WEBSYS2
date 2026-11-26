<?php

namespace App\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class RecordOperation
{
    public function handle(string $operation, string $status = 'ok', ?string $errorClass = null, ?CarbonImmutable $occurredAt = null): void
    {
        if (! config('operations.enabled')) {
            return;
        }
        try {
            $saved = Storage::disk('local')->put('operations/'.$operation.'.json', json_encode([
                'status' => $status,
                'occurred_at' => ($occurredAt ?? CarbonImmutable::now())->toIso8601String(),
                'checked_at' => CarbonImmutable::now()->toIso8601String(),
                'error_class' => $errorClass,
            ], JSON_THROW_ON_ERROR));
            if (! $saved) {
                throw new RuntimeException('Operational status write failed.');
            }
        } catch (Throwable) {
            error_log('CheckPoint could not persist operational status. Check private storage permissions.');
        }
    }
}
