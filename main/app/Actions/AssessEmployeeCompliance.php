<?php

namespace App\Actions;

use App\Enums\ComplianceStatus;
use App\Enums\CredentialStatus;
use App\Enums\QualificationState;
use App\Enums\TrainingStatus;
use App\Enums\UserRole;
use App\Models\CredentialDocument;
use App\Models\EmployeeTraining;
use App\Models\Qualification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AssessEmployeeCompliance
{
    public const array RELATIONS = [
        'facility.region', 'jobRole.qualifications.safetyDomain',
        'credentialDocuments.employeeTraining.trainingCourse', 'employeeTrainings.trainingCourse',
    ];

    public function __construct(private SelectEffectiveCredential $selection) {}

    /**
     * @return array{employee_id: int, employee_name: string, role: string, is_active: bool, facility_id: ?int, facility_name: ?string, region_id: ?int, region_name: ?string, job_role_id: ?int, job_role_name: ?string, status: string, reason: ?string, required_count: int, satisfied_count: int, percentage: ?float, assessed_on: string, items: array<int, array{qualification_id: int, name: string, domain: string, requires_training: bool, requires_expiry: bool, state: string, training_completed: bool, document_id: ?int, expires_at: ?string, expiring_soon: bool, pending_count: int}>}
     */
    public function handle(User $employee, ?CarbonImmutable $asOf = null): array
    {
        $asOf = ($asOf ?? CarbonImmutable::now('Asia/Manila'))->setTimezone('Asia/Manila')->startOfDay();
        $employee->loadMissing(self::RELATIONS);
        $requirements = $employee->jobRole?->qualifications->sortBy('id') ?? collect();
        $documents = $employee->credentialDocuments->groupBy('qualification_id');
        $items = [];
        foreach ($requirements as $qualification) {
            $items[$qualification->id] = $this->assessQualification($employee, $qualification, $documents->get($qualification->id, collect()), $asOf);
        }
        $reason = $this->notAssessableReason($employee, $requirements);
        $required = count($items);
        $satisfied = collect($items)->where('state', QualificationState::Satisfied->value)->count();
        $status = $reason !== null ? ComplianceStatus::NotAssessable
            : ($required === $satisfied ? ComplianceStatus::Eligible : ComplianceStatus::NotEligible);

        return [
            'employee_id' => $employee->id, 'employee_name' => $employee->name,
            'role' => $employee->role->value, 'is_active' => $employee->is_active,
            'facility_id' => $employee->facility_id, 'facility_name' => $employee->facility?->name,
            'region_id' => $employee->facility?->region_id, 'region_name' => $employee->facility?->region?->name,
            'job_role_id' => $employee->job_role_id, 'job_role_name' => $employee->jobRole?->name,
            'status' => $status->value, 'reason' => $reason,
            'required_count' => $required, 'satisfied_count' => $satisfied,
            'percentage' => $reason === null ? round($satisfied * 100 / $required, 2) : null,
            'assessed_on' => $asOf->toDateString(), 'items' => $items,
        ];
    }

    /**
     * @param  Collection<int, CredentialDocument>  $documents
     * @return array{qualification_id: int, name: string, domain: string, requires_training: bool, requires_expiry: bool, state: string, training_completed: bool, document_id: ?int, expires_at: ?string, expiring_soon: bool, pending_count: int}
     */
    private function assessQualification(User $employee, Qualification $qualification, Collection $documents, CarbonImmutable $asOf): array
    {
        $effective = $this->selection->choose($employee, $qualification, $documents, $asOf);
        $pending = $documents->where('status', CredentialStatus::Pending)->count();
        $latestReviewed = $documents->reject(fn (CredentialDocument $document): bool => $document->status === CredentialStatus::Pending)
            ->sortBy([['updated_at', 'desc'], ['id', 'desc']])->first();
        $expired = $documents->contains(fn (CredentialDocument $document): bool => $document->status === CredentialStatus::Approved
            && $document->expires_at !== null && $document->expires_at->toDateString() < $asOf->toDateString());
        $state = match (true) {
            $effective !== null => QualificationState::Satisfied,
            $pending > 0 => QualificationState::Pending,
            $latestReviewed?->status === CredentialStatus::Rejected => QualificationState::Rejected,
            $expired => QualificationState::Expired,
            default => QualificationState::Missing,
        };
        $trainingCompleted = $employee->employeeTrainings->contains(fn (EmployeeTraining $enrollment): bool => $enrollment->employee_id === $employee->id && $enrollment->trainingCourse->qualification_id === $qualification->id
            && $enrollment->status === TrainingStatus::Completed && $enrollment->completed_at !== null
            && $enrollment->completed_at->toDateString() <= $asOf->toDateString());

        return [
            'qualification_id' => $qualification->id, 'name' => $qualification->name, 'domain' => $qualification->safetyDomain->name,
            'requires_training' => $qualification->requires_training, 'requires_expiry' => $qualification->requires_expiry,
            'state' => $state->value, 'training_completed' => $trainingCompleted,
            'document_id' => $effective?->id, 'expires_at' => $effective?->expires_at?->toDateString(),
            'expiring_soon' => $effective?->expires_at !== null && $effective->expires_at->toDateString() <= $asOf->addDays(30)->toDateString(),
            'pending_count' => $pending,
        ];
    }

    /** @param Collection<int, Qualification> $requirements */
    private function notAssessableReason(User $employee, Collection $requirements): ?string
    {
        return match (true) {
            $employee->role !== UserRole::Staff => 'This account is not a staff employee.',
            ! $employee->is_active => 'This employee account is inactive.',
            ! $employee->facility || ! $employee->jobRole => 'A facility and job assignment are required.',
            ! $employee->facility->is_active || ! $employee->facility->region->is_active || ! $employee->jobRole->is_active => 'An assigned facility, region, or job is inactive.',
            $requirements->isEmpty() => 'No requirements configured. This does not mean the employee is compliant.',
            $requirements->contains(fn (Qualification $qualification): bool => ! $qualification->is_active || ! $qualification->safetyDomain->is_active) => 'A required qualification or safety domain is inactive; review the requirement configuration.',
            default => null,
        };
    }
}
