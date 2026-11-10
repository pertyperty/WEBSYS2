<?php

namespace App\Actions;

use App\Enums\CredentialStatus;
use App\Enums\TrainingStatus;
use App\Models\CredentialDocument;
use App\Models\Qualification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class SelectEffectiveCredential
{
    public function find(User $employee, Qualification $qualification): ?CredentialDocument
    {
        $documents = $employee->credentialDocuments()->where('qualification_id', $qualification->id)
            ->with('employeeTraining.trainingCourse')->get();

        return $this->choose($employee, $qualification, $documents, CarbonImmutable::now('Asia/Manila')->startOfDay());
    }

    /** @param Collection<int, CredentialDocument> $documents */
    public function choose(User $employee, Qualification $qualification, Collection $documents, CarbonImmutable $asOf): ?CredentialDocument
    {
        return $documents->filter(fn (CredentialDocument $document): bool => $document->employee_id === $employee->id
            && $this->isQualifying($document, $qualification, $asOf))
            ->sortBy([['issued_at', 'desc'], ['id', 'desc']])
            ->first();
    }

    public function isQualifying(CredentialDocument $document, Qualification $qualification, CarbonImmutable $asOf): bool
    {
        $today = $asOf->setTimezone('Asia/Manila')->toDateString();
        if ($document->qualification_id !== $qualification->id || $document->status !== CredentialStatus::Approved
            || ! $qualification->is_active || ! $qualification->safetyDomain->is_active
            || ! $document->issued_at || $document->issued_at->toDateString() > $today
            || ($document->expires_at ? $document->expires_at->toDateString() < $today : $qualification->requires_expiry)) {
            return false;
        }
        if (! $qualification->requires_training) {
            return true;
        }
        $enrollment = $document->employeeTraining;

        return $enrollment !== null && $enrollment->employee_id === $document->employee_id
            && $enrollment->trainingCourse->qualification_id === $qualification->id
            && $enrollment->status === TrainingStatus::Completed && $enrollment->completed_at !== null
            && $enrollment->completed_at->toDateString() <= $today;
    }
}
