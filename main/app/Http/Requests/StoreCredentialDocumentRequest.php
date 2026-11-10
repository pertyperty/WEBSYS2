<?php

namespace App\Http\Requests;

use App\Models\EmployeeTraining;
use App\Models\Qualification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreCredentialDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('submit-credentials');
    }

    public function rules(): array
    {
        $qualification = Qualification::find($this->integer('qualification_id'));

        return [
            'qualification_id' => ['required', 'integer', Rule::exists('qualifications', 'id')->where('is_active', true)],
            'issued_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Manila')->toDateString()],
            'expires_at' => [Rule::requiredIf($qualification?->requires_expiry ?? false), 'nullable', 'date_format:Y-m-d', 'after_or_equal:issued_at'],
            'employee_training_id' => [Rule::requiredIf($qualification?->requires_training ?? false), 'nullable', 'integer', 'exists:employee_trainings,id', Rule::prohibitedIf($qualification && ! $qualification->requires_training)],
            'file' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(config('credentials.max_upload_kib'))],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->has('qualification_id')) {
                $qualification = Qualification::with('safetyDomain')->findOrFail($this->integer('qualification_id'));
                if (! $qualification->safetyDomain->is_active) {
                    $validator->errors()->add('qualification_id', 'Choose a qualification in an active safety domain.');
                }
                if ($this->filled('employee_training_id') && ! $validator->errors()->has('employee_training_id')) {
                    $enrollment = EmployeeTraining::with('trainingCourse')->findOrFail($this->integer('employee_training_id'));
                    if ($enrollment->employee_id !== $this->user()->id || $enrollment->trainingCourse->qualification_id !== $qualification->id) {
                        $validator->errors()->add('employee_training_id', 'Choose your own enrollment for this qualification.');
                    }
                }
            }
            if (! $validator->errors()->has('file') && $this->file('file')) {
                $file = $this->file('file');
                if (in_array($file->getMimeType(), ['image/jpeg', 'image/png'], true) && @getimagesize($file->getRealPath()) === false) {
                    $validator->errors()->add('file', 'The image content could not be read. Upload a valid JPEG or PNG.');
                }
            }
        }];
    }
}
