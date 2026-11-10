<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReviewCredentialDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('review-credentials');
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'note' => [Rule::requiredIf($this->input('decision') === 'rejected'), 'nullable', 'string', 'max:2000'],
        ];
    }
}
