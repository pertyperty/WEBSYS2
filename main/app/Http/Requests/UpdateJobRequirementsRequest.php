<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateJobRequirementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-requirements');
    }

    public function rules(): array
    {
        return [
            'qualification_ids' => ['present', 'array', 'max:200'],
            'qualification_ids.*' => ['required', 'integer', 'distinct', Rule::exists('qualifications', 'id')->where('is_active', true)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('qualification_ids')) {
            $this->merge(['qualification_ids' => []]);
        }
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->route('jobRole')->is_active && $this->input('qualification_ids') !== []) {
                $validator->errors()->add('qualification_ids', 'Reactivate the job before assigning requirements.');
            }
        }];
    }
}
