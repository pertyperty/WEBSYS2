<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CompleteTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('complete-training');
    }

    public function rules(): array
    {
        $enrollmentDate = $this->route('enrollment')->enrolled_at->timezone('Asia/Manila')->toDateString();

        return [
            'completed_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$enrollmentDate, 'before_or_equal:'.now('Asia/Manila')->toDateString()],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
