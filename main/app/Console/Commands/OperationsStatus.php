<?php

namespace App\Console\Commands;

use App\Actions\RecordOperation;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OperationsStatus extends Command
{
    protected $signature = 'checkpoint:operations-status {--json} {--acknowledge-error : Mark the last application incident acknowledged without deleting logs}';

    protected $description = 'Report scheduler, reconciliation, verified backup freshness, and application incidents';

    public function handle(RecordOperation $record): int
    {
        if ($this->option('acknowledge-error')) {
            $record->handle('application', 'acknowledged');
        }
        $checks = [];
        foreach (config('operations.max_age_minutes') as $operation => $maximumAge) {
            $state = $this->readState($operation);
            $status = $state['status'] ?? 'missing';
            if ($status === 'ok') {
                try {
                    $occurredAt = CarbonImmutable::parse($state['occurred_at']);
                    if ($occurredAt->isFuture() || $occurredAt->lt(CarbonImmutable::now()->subMinutes($maximumAge))) {
                        $status = 'stale';
                    }
                } catch (Throwable) {
                    $status = 'unreadable';
                }
            }
            $checks[$operation] = ['status' => $status, 'occurred_at' => $state['occurred_at'] ?? null];
        }
        $application = $this->readState('application');
        $checks['application'] = ['status' => $application['status'] ?? 'no_recorded_incident', 'occurred_at' => $application['occurred_at'] ?? null];
        $healthy = collect($checks)->every(fn (array $check): bool => in_array($check['status'], ['ok', 'acknowledged', 'no_recorded_incident'], true));
        $result = ['healthy' => $healthy, 'owner' => config('operations.owner'), 'checks' => $checks];
        if (! $healthy) {
            Log::critical('CheckPoint operations need attention.', $result);
        }
        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_THROW_ON_ERROR));
        } else {
            $this->line('Responsible operator: '.config('operations.owner'));
            $this->table(['Check', 'Status', 'Last event'], collect($checks)->map(fn (array $check, string $name): array => [$name, $check['status'], $check['occurred_at'] ?? 'Never'])->all());
            if (! $healthy) {
                $this->error('Action required. Inspect scheduler/service logs, verify a recent backup, and investigate application incidents.');
            }
        }

        return $healthy ? self::SUCCESS : self::FAILURE;
    }

    /** @return array{status?: string, occurred_at?: string} */
    private function readState(string $operation): array
    {
        try {
            $disk = Storage::disk('local');
            if (! $disk->exists('operations/'.$operation.'.json')) {
                return [];
            }
            $state = json_decode($disk->get('operations/'.$operation.'.json'), true, flags: JSON_THROW_ON_ERROR);

            return is_array($state) ? $state : ['status' => 'unreadable'];
        } catch (Throwable) {
            return ['status' => 'unreadable'];
        }
    }
}
