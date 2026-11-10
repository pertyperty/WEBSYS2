<?php

namespace App\Http\Requests;

use App\Enums\CourseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveQualificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-organization');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('qualifications')->where('safety_domain_id', $this->integer('safety_domain_id'))->ignore($this->route('qualification'))],
            'safety_domain_id' => ['required', 'integer', Rule::exists('safety_domains', 'id')->where('is_active', true)],
            'requires_expiry' => ['required', 'boolean'],
            'requires_training' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $qualification = $this->route('qualification');
            if ($qualification && $qualification->trainingCourses()->exists() && ! $this->boolean('requires_training')) {
                $validator->errors()->add('requires_training', 'Keep training required while course history exists.');
            }
            if ($qualification && ! $this->boolean('is_active') && $qualification->trainingCourses()->where('status', '!=', CourseStatus::Archived)->exists()) {
                $validator->errors()->add('is_active', 'Archive linked courses before deactivating this qualification.');
            }
            if ($qualification && ! $this->boolean('is_active') && $qualification->jobRoles()->exists()) {
                $validator->errors()->add('is_active', 'Remove this qualification from job requirements before deactivating it.');
            }
        }];
    }
}
