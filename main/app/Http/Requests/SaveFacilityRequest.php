<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-organization');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('facilities')->where('region_id', $this->integer('region_id'))->ignore($this->route('facility'))],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')->where('is_active', true)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $facility = $this->route('facility');
            if (! $facility || ! $facility->users()->exists()) {
                return;
            }
            if (! $this->boolean('is_active')) {
                $validator->errors()->add('is_active', 'Remove account assignments before deactivating this facility.');
            }
            if (! $validator->errors()->has('region_id') && $this->integer('region_id') !== $facility->region_id) {
                $validator->errors()->add('region_id', 'Remove account assignments before moving a facility to another region.');
            }
        }];
    }
}
