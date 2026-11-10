<?php

namespace App\Actions;

use App\Models\ComplianceAssessment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class RecordComplianceAssessment
{
    public function __construct(private AssessEmployeeCompliance $assessment) {}

    /** @return array<string, mixed> The live result, even when unchanged history is not appended. */
    public function handle(User $employee, string $source, ?User $actor = null, ?CarbonImmutable $asOf = null): array
    {
        return DB::transaction(function () use ($employee, $source, $actor, $asOf): array {
            $locked = User::lockForUpdate()->findOrFail($employee->id);
            $snapshot = $this->assessment->handle($locked, $asOf);
            $material = $snapshot;
            unset($material['assessed_on']);
            $fingerprint = hash('sha256', json_encode($material, JSON_THROW_ON_ERROR));
            $previous = ComplianceAssessment::where('employee_id', $locked->id)->latest('id')->first();
            if ($previous?->fingerprint !== $fingerprint) {
                ComplianceAssessment::create([
                    'employee_id' => $locked->id, 'actor_id' => $actor?->id, 'source' => $source,
                    'status' => $snapshot['status'], 'required_count' => $snapshot['required_count'],
                    'satisfied_count' => $snapshot['satisfied_count'], 'percentage' => $snapshot['percentage'],
                    'snapshot' => $snapshot, 'fingerprint' => $fingerprint, 'assessed_on' => $snapshot['assessed_on'],
                ]);
            }

            return $snapshot;
        });
    }
}
