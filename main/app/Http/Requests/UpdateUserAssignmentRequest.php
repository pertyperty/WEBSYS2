<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-users');
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['required', 'boolean'],
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'job_role_id' => ['nullable', 'integer', 'exists:job_roles,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $role = is_string($this->input('role')) ? UserRole::tryFrom($this->input('role')) : null;
            $facility = $this->filled('facility_id');
            $job = $this->filled('job_role_id');
            $region = $this->filled('region_id');

            if ($role === UserRole::Staff && ($facility !== $job || $region)) {
                $validator->errors()->add('facility_id', 'Staff need both a facility and job, or neither; no regional assignment.');
            }
            if ($role === UserRole::FacilityManager && (! $facility || $job || $region)) {
                $validator->errors()->add('facility_id', 'A facility manager needs exactly one facility and no job or regional assignment.');
            }
            if (in_array($role, [UserRole::RegionalDirector, UserRole::Auditor], true) && (! $region || $facility || $job)) {
                $validator->errors()->add('region_id', 'This role needs exactly one region and no facility or job assignment.');
            }
            if (in_array($role, [UserRole::Admin, UserRole::ComplianceOfficer], true) && ($facility || $job || $region)) {
                $validator->errors()->add('role', 'Global roles must not have facility, job, or region assignments.');
            }
            if ($this->route('user')->is($this->user()) && ($role !== UserRole::Admin || ! $this->boolean('is_active'))) {
                $validator->errors()->add('role', 'You cannot deactivate or demote your own administrator account.');
            }
        }];
    }
}
