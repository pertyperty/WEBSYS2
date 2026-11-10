<?php

namespace App\Console\Commands;

use App\Actions\RecordComplianceAssessment;
use App\Enums\UserRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ReconcileCompliance extends Command
{
    protected $signature = 'checkpoint:reconcile-compliance';

    protected $description = 'Append changed staff assessments, including expiry and warning date transitions';

    public function handle(RecordComplianceAssessment $history): int
    {
        $asOf = CarbonImmutable::now('Asia/Manila')->startOfDay();
        $count = 0;
        foreach (User::where('role', UserRole::Staff)->lazyById(100) as $employee) {
            $history->handle($employee, 'date_reconciliation', asOf: $asOf);
            $count++;
        }
        $this->info("Reconciled {$count} staff assessments. Unchanged results do not create history rows.");

        return self::SUCCESS;
    }
}
