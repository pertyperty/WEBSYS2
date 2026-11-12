<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeComplianceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $assessment = $this->resource['assessment'];

        return [
            'employee_reference' => $this->resource['employee_reference'],
            'status' => $assessment['status'],
            'required_count' => $assessment['required_count'],
            'satisfied_count' => $assessment['satisfied_count'],
            'qualification_percentage' => $assessment['percentage'],
            'evaluated_on' => $assessment['assessed_on'],
            'qualifications' => array_values(array_map(fn (array $item): array => [
                'qualification' => $item['name'],
                'domain' => $item['domain'],
                'state' => $item['state'],
                'training_completed' => $item['training_completed'],
                'expires_on' => $item['expires_at'],
                'expiring_soon' => $item['expiring_soon'],
            ], $assessment['items'])),
        ];
    }
}
