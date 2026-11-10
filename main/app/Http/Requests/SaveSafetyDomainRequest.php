<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveSafetyDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-organization');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('safety_domains')->ignore($this->route('safetyDomain'))],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $record = $this->route('safetyDomain');
            if ($record && ! $this->boolean('is_active') && ($record->qualifications()->active()->exists())) {
                $validator->errors()->add('is_active', 'Deactivate its qualifications before deactivating this domain.');
            }
        }];
    }
}
