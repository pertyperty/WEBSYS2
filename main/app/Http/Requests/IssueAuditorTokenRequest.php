<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IssueAuditorTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-users');
    }

    public function rules(): array
    {
        $today = now('Asia/Manila')->toDateString();

        return [
            'auditor_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Auditor->value)->where('is_active', true)],
            'name' => ['required', 'string', 'max:100'],
            'expires_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$today, 'before_or_equal:'.now('Asia/Manila')->addDays(90)->toDateString()],
        ];
    }
}
