<?php

namespace App\Actions;

use App\Enums\CredentialEventType;
use App\Models\CredentialDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SubmitCredential
{
    /**
     * @param  array{qualification_id: int|string, issued_at: string, expires_at?: string|null, employee_training_id?: int|string|null}  $data
     */
    public function handle(User $employee, UploadedFile $file, array $data): CredentialDocument
    {
        Gate::forUser($employee)->authorize('submit-credentials');
        $path = null;

        try {
            $path = $file->store('credentials', 'local');
            if (! $path) {
                throw ValidationException::withMessages(['file' => 'The private file could not be stored. Please try again.']);
            }

            return DB::transaction(function () use ($employee, $file, $data, $path): CredentialDocument {
                $document = CredentialDocument::create([
                    'employee_id' => $employee->id,
                    'facility_id' => $employee->facility_id,
                    'job_role_id' => $employee->job_role_id,
                    'qualification_id' => $data['qualification_id'],
                    'employee_training_id' => $data['employee_training_id'] ?? null,
                    'issued_at' => $data['issued_at'],
                    'expires_at' => $data['expires_at'] ?? null,
                    'reference' => (string) Str::uuid(),
                    'private_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
                $document->events()->create(['actor_id' => $employee->id, 'type' => CredentialEventType::Submitted]);

                return $document;
            });
        } catch (Throwable $exception) {
            if ($path) {
                try {
                    Storage::disk('local')->delete($path);
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }
            report($exception);
            throw ValidationException::withMessages(['file' => 'The submission could not be saved. Please try again.']);
        }
    }
}
