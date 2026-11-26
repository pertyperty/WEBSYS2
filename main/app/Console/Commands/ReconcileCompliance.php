<?php

namespace App\Console\Commands;

use App\Actions\ReconcileExpiryAlerts;
use App\Actions\RecordComplianceAssessment;
use App\Actions\RecordOperation;
use App\Enums\UserRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ReconcileCompliance extends Command
{
    protected $signature = 'checkpoint:reconcile-compliance';

    protected $description = 'Append changed staff assessments, including expiry and warning date transitions';

    public function handle(RecordComplianceAssessment $history, ReconcileExpiryAlerts $alerts, RecordOperation $operation): int
    {
        $asOf = CarbonImmutable::now('Asia/Manila')->startOfDay();
        $count = 0;
        try {
            foreach (User::where('role', UserRole::Staff)->lazyById(100) as $employee) {
                $history->handle($employee, 'date_reconciliation', asOf: $asOf);
                $alerts->handle($employee, $asOf);
                $count++;
            }
        } catch (\Throwable $exception) {
            $operation->handle('reconciliation', 'failed', $exception::class);
            throw $exception;
        }
        $operation->handle('reconciliation');
        $this->info("Reconciled {$count} staff assessments. Unchanged results do not create history rows.");

        return self::SUCCESS;
    }
}
