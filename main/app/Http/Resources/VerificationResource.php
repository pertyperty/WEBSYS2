<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationResource extends JsonResource
{
    /** @return array<string, string|null> */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->resource['reference'],
            'employee_reference' => $this->resource['employee_reference'],
            'qualification' => $this->resource['qualification'],
            'review_status' => $this->resource['review_status'],
            'validity' => $this->resource['validity'],
            'issued_on' => $this->resource['issued_on'],
            'expires_on' => $this->resource['expires_on'],
            'reviewed_at' => $this->resource['reviewed_at'],
            'evaluated_on' => $this->resource['evaluated_on'],
        ];
    }
}
