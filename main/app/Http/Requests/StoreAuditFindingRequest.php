<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Audit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreAuditFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('conduct-audits');
    }

    public function rules(): array
    {
        $audit = Audit::findOrFail($this->route('audit'));

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'facility_id' => ['required', 'integer', Rule::exists('facilities', 'id')->where('region_id', $audit->region_id)
                ->when($audit->facility_id, fn ($rule) => $rule->where('id', $audit->facility_id))],
            'assignee_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', UserRole::FacilityManager->value)
                ->where('is_active', true)->where('facility_id', $this->input('facility_id'))],
            'due_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Manila')->toDateString()],
        ];
    }
}
