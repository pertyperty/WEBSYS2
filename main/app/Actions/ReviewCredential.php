<?php

namespace App\Actions;

use App\Enums\CredentialEventType;
use App\Enums\CredentialStatus;
use App\Enums\TrainingStatus;
use App\Models\CredentialDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;

class ReviewCredential
{
    public function __construct(private SelectEffectiveCredential $selection, private RecordComplianceAssessment $history) {}

    public function handle(User $reviewer, CredentialDocument $document, CredentialStatus $decision, ?string $note): void
    {
        Gate::forUser($reviewer)->authorize('review-credentials');
        DB::transaction(function () use ($reviewer, $document, $decision, $note): void {
            $employee = User::lockForUpdate()->findOrFail($document->employee_id);
            $locked = CredentialDocument::with(['qualification.safetyDomain', 'employeeTraining.trainingCourse'])->lockForUpdate()->findOrFail($document->id);
            if ($locked->employee_id === $reviewer->id) {
                throw ValidationException::withMessages(['decision' => 'You cannot review your own evidence.']);
            }
            if ($locked->status !== CredentialStatus::Pending) {
                throw ValidationException::withMessages(['decision' => 'This submission has already been reviewed. Submit a new document for changes.']);
            }
            if (! in_array($decision, [CredentialStatus::Approved, CredentialStatus::Rejected], true) || ($decision === CredentialStatus::Rejected && ! filled($note))) {
                throw ValidationException::withMessages(['note' => 'A rejection requires a reason.']);
            }
            if ($decision === CredentialStatus::Approved) {
                try {
                    $available = Storage::disk('local')->exists($locked->private_path);
                } catch (FilesystemException $exception) {
                    report($exception);
                    $available = false;
                }
                if (! $available) {
                    throw ValidationException::withMessages(['decision' => 'Restore or resubmit the missing evidence file before approval.']);
                }
            }
            if ($decision === CredentialStatus::Approved && $locked->qualification->requires_training) {
                $enrollment = $locked->employeeTraining;
                if (! $enrollment || $enrollment->employee_id !== $employee->id
                    || $enrollment->trainingCourse->qualification_id !== $locked->qualification_id
                    || $enrollment->status !== TrainingStatus::Completed || ! $enrollment->completed_at
                    || $enrollment->completed_at->toDateString() > now('Asia/Manila')->toDateString()) {
                    throw ValidationException::withMessages(['decision' => 'Approval requires a matching completed training enrollment.']);
                }
            }
            $locked->forceFill(['status' => $decision])->save();
            $locked->events()->create(['actor_id' => $reviewer->id, 'type' => CredentialEventType::from($decision->value), 'note' => $note]);

            $effective = $this->selection->find($employee, $locked->qualification);
            if ($effective) {
                $employee->qualifications()->syncWithoutDetaching([$locked->qualification_id => ['credential_document_id' => $effective->id]]);
            } else {
                $employee->qualifications()->detach($locked->qualification_id);
            }
            $this->history->handle($employee, 'review', $reviewer);
        });
    }
}
