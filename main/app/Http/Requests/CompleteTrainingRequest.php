<?php

namespace App\Http\Requests;

use App\Enums\TrainingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

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
            'filters' => ['sometimes', 'array:search,status,facility_id'],
            'filters.search' => ['nullable', 'string', 'max:100'],
            'filters.status' => ['nullable', Rule::enum(TrainingStatus::class)],
            'filters.facility_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
