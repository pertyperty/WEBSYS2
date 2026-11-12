<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacilityComplianceResource extends JsonResource
{
    /** @return array<string, int|float|string|null> */
    public function toArray(Request $request): array
    {
        return [
            'facility_id' => $this->resource['facility_id'],
            'active_staff_count' => $this->resource['active_staff_count'],
            'assessable_count' => $this->resource['assessable_count'],
            'eligible_count' => $this->resource['eligible_count'],
            'not_eligible_count' => $this->resource['not_eligible_count'],
            'not_assessable_count' => $this->resource['not_assessable_count'],
            'compliance_rate' => $this->resource['compliance_rate'],
            'evaluated_on' => $this->resource['evaluated_on'],
        ];
    }
}
