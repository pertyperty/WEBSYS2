<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('conduct-audits');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')->where('is_active', true)],
            'facility_id' => ['nullable', 'integer', Rule::exists('facilities', 'id')->where('region_id', $this->input('region_id'))->where('is_active', true)],
        ];
    }
}
