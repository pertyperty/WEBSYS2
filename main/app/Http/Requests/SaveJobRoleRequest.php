<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveJobRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-organization');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('job_roles')->ignore($this->route('jobRole'))],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $record = $this->route('jobRole');
            if ($record && ! $this->boolean('is_active') && ($record->users()->exists())) {
                $validator->errors()->add('is_active', 'Remove employee assignments before deactivating this job.');
            }
        }];
    }
}
