<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\AuditFinding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFindingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $finding = AuditFinding::visibleTo($this->user())->findOrFail($this->route('finding'));

        return $finding->canReceiveUpdateFrom($this->user());
    }

    public function rules(): array
    {
        $statuses = $this->user()->role === UserRole::ComplianceOfficer ? ['open', 'in_progress', 'pending_verification', 'resolved'] : ['in_progress', 'pending_verification'];

        $finding = AuditFinding::findOrFail($this->route('finding'));

        return [
            'assignee_id' => [
                'nullable', 'integer', Rule::prohibitedIf($this->user()->role !== UserRole::ComplianceOfficer),
                Rule::exists('users', 'id')->where('role', UserRole::FacilityManager->value)
                    ->where('is_active', true)->where('facility_id', $finding->facility_id),
            ],
            'note' => ['required', 'string', 'max:5000'],
            'status' => ['required', Rule::in($statuses)],
        ];
    }
}
