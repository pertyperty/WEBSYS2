<?php

namespace App\Http\Requests;

use App\Enums\CourseStatus;
use App\Models\Qualification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveTrainingCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-courses');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'qualification_id' => ['required', 'integer', 'exists:qualifications,id'],
            'status' => ['required', Rule::enum(CourseStatus::class)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['qualification_id', 'status'])) {
                return;
            }
            $course = $this->route('course');
            $archived = $this->input('status') === CourseStatus::Archived->value;
            if (! $course && $archived) {
                $validator->errors()->add('status', 'Create a course as draft or published before archiving it.');
            }
            $qualification = Qualification::with('safetyDomain')->findOrFail($this->integer('qualification_id'));
            $sameMapping = $course && $course->qualification_id === $qualification->id;
            if ((! $archived || ! $sameMapping) && (! $qualification->is_active || ! $qualification->requires_training || ! $qualification->safetyDomain->is_active)) {
                $validator->errors()->add('qualification_id', 'Choose an active training-required qualification in an active safety domain.');
            }
        }];
    }
}
