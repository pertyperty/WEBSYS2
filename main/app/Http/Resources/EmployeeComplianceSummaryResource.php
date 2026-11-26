<?php

namespace App\Http\Resources;

use App\Enums\ComplianceStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeComplianceSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $assessment = $this->resource['assessment'];

        return [
            'employee_reference' => $this->resource['employee_reference'],
            'status' => $assessment['status'],
            'duty_status' => ComplianceStatus::from($assessment['status'])->dutyLabel(),
            'required_count' => $assessment['required_count'],
            'satisfied_count' => $assessment['satisfied_count'],
            'qualification_percentage' => $assessment['percentage'],
            'evaluated_on' => $assessment['assessed_on'],
        ];
    }
}
