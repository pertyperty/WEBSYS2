<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-organization');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('regions')->ignore($this->route('region'))],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $record = $this->route('region');
            if ($record && ! $this->boolean('is_active') && ($record->facilities()->active()->exists() || $record->users()->exists())) {
                $validator->errors()->add('is_active', 'Deactivate its facilities and remove regional account assignments first.');
            }
        }];
    }
}
