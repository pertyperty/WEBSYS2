<?php

namespace App\Actions;

use App\Enums\CredentialStatus;
use App\Models\CredentialDocument;
use Carbon\CarbonImmutable;

class VerifyCredentialReference
{
    public function __construct(private SelectEffectiveCredential $selection) {}

    /** @return array{reference: string, employee_reference: string, qualification: string, review_status: string, validity: string, issued_on: string, expires_on: ?string, reviewed_at: ?string, evaluated_on: string} */
    public function handle(CredentialDocument $document, ?CarbonImmutable $asOf = null): array
    {
        $asOf = ($asOf ?? CarbonImmutable::now('Asia/Manila'))->setTimezone('Asia/Manila')->startOfDay();
        $document->loadMissing(['employee', 'qualification.safetyDomain', 'employeeTraining.trainingCourse', 'latestReview']);
        $validity = match (true) {
            $this->selection->isQualifying($document, $document->qualification, $asOf) => 'valid',
            $document->status === CredentialStatus::Approved && $document->expires_at !== null
                && $document->expires_at->toDateString() < $asOf->toDateString() => 'expired',
            default => 'not_valid',
        };

        return [
            'reference' => $document->reference,
            'employee_reference' => $document->employee->verification_reference,
            'qualification' => $document->qualification->name,
            'review_status' => $document->status->value,
            'validity' => $validity,
            'issued_on' => $document->issued_at->toDateString(),
            'expires_on' => $document->expires_at?->toDateString(),
            'reviewed_at' => $document->latestReview?->created_at->toISOString(),
            'evaluated_on' => $asOf->toDateString(),
        ];
    }
}
