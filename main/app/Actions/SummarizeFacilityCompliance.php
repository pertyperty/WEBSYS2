<?php

namespace App\Actions;

use App\Enums\ComplianceStatus;
use App\Enums\UserRole;
use App\Models\Facility;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class SummarizeFacilityCompliance
{
    public function __construct(private AssessEmployeeCompliance $assessment) {}

    /** @return array{facility_id: int, active_staff_count: int, assessable_count: int, eligible_count: int, not_eligible_count: int, not_assessable_count: int, compliance_rate: ?float, evaluated_on: string} */
    public function handle(Facility $facility): array
    {
        $date = CarbonImmutable::now('Asia/Manila')->startOfDay();
        $counts = ['eligible' => 0, 'not_eligible' => 0, 'not_assessable' => 0];
        User::where('facility_id', $facility->id)->where('role', UserRole::Staff)->where('is_active', true)
            ->with(AssessEmployeeCompliance::RELATIONS)->chunkById(100, function (Collection $employees) use (&$counts, $date): void {
                foreach ($employees as $employee) {
                    $counts[$this->assessment->handle($employee, $date)['status']]++;
                }
            });
        $eligible = $counts[ComplianceStatus::Eligible->value];
        $assessable = $eligible + $counts[ComplianceStatus::NotEligible->value];

        return [
            'facility_id' => $facility->id,
            'active_staff_count' => array_sum($counts),
            'assessable_count' => $assessable,
            'eligible_count' => $eligible,
            'not_eligible_count' => $counts[ComplianceStatus::NotEligible->value],
            'not_assessable_count' => $counts[ComplianceStatus::NotAssessable->value],
            'compliance_rate' => $assessable === 0 ? null : round($eligible * 100 / $assessable, 2),
            'evaluated_on' => $date->toDateString(),
        ];
    }
}
