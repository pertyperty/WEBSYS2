<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('view-reports');
    }

    public function rules(): array
    {
        $user = $this->user();
        $facility = Rule::exists('facilities', 'id');
        if ($user->role === UserRole::FacilityManager) {
            $facility->where('id', $user->facility_id ?? 0);
        } elseif ($user->role === UserRole::RegionalDirector) {
            $facility->where('region_id', $user->region_id ?? 0);
        }

        return [
            'type' => ['nullable', Rule::in(['readiness', 'expiry', 'training', 'findings'])],
            'facility_id' => ['nullable', 'integer', $facility],
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }
}
