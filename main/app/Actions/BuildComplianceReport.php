<?php

namespace App\Actions;

use App\Models\AuditFinding;
use App\Models\EmployeeTraining;
use App\Models\User;
use Carbon\CarbonImmutable;
use Generator;

class BuildComplianceReport
{
    public function __construct(private AssessEmployeeCompliance $assessment, private ReconcileExpiryAlerts $alerts) {}

    /** @return list<string> */
    public function headers(string $type): array
    {
        return match ($type) {
            'expiry' => ['Employee', 'Region', 'Facility', 'Qualification', 'Expiry date', 'Status', 'Assessed on'],
            'training' => ['Employee', 'Region', 'Facility', 'Course', 'Status', 'Enrolled on', 'Completed on'],
            'findings' => ['Finding', 'Facility', 'Assigned manager', 'Due date', 'Status', 'Overdue', 'Resolved on'],
            default => ['Employee', 'Region', 'Facility', 'Job', 'Status', 'Requirements met', 'Requirements', 'Percentage', 'Assessed on'],
        };
    }

    /**
     * @param  array{type?: ?string, facility_id?: int|string|null, q?: ?string}  $filters
     * @return Generator<int, list<string|int|float|null>>
     */
    public function rows(User $viewer, array $filters): Generator
    {
        $type = $filters['type'] ?? 'readiness';
        $date = CarbonImmutable::now('Asia/Manila')->startOfDay();
        $employees = User::visibleTo($viewer)->when($filters['facility_id'] ?? null, fn ($query, $facility) => $query->where('facility_id', $facility))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'));
        if ($type === 'findings') {
            $findings = AuditFinding::visibleTo($viewer)->with(['facility', 'assignee'])
                ->when($filters['facility_id'] ?? null, fn ($query, $facility) => $query->where('facility_id', $facility))
                ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('title', 'like', '%'.$search.'%'));
            foreach ($findings->lazyById(100) as $finding) {
                yield [$finding->title, $finding->facility->name, $finding->assignee->name, $finding->due_on->toDateString(),
                    $finding->status, $finding->status !== 'resolved' && $finding->due_on->toDateString() < $date->toDateString() ? 'Yes' : 'No', $finding->resolved_at?->toDateString()];
            }

            return;
        }
        if ($type === 'training') {
            foreach (EmployeeTraining::whereIn('employee_id', $employees->select('id'))->with(['employee.facility.region', 'trainingCourse'])->lazyById(100) as $enrollment) {
                yield [$enrollment->employee->name, $enrollment->employee->facility?->region?->name, $enrollment->employee->facility?->name,
                    $enrollment->trainingCourse->title, $enrollment->status->value, $enrollment->enrolled_at->toDateString(), $enrollment->completed_at?->toDateString()];
            }

            return;
        }
        foreach ($employees->with(AssessEmployeeCompliance::RELATIONS)->lazyById(100) as $employee) {
            $result = $this->assessment->handle($employee, $date);
            if ($type === 'expiry') {
                foreach ($this->alerts->currentEvents($employee, $date) as $event) {
                    yield [$employee->name, $result['region_name'], $result['facility_name'], $event['qualification_name'],
                        $event['expires_at'], $event['event'], $date->toDateString()];
                }
            } else {
                yield [$employee->name, $result['region_name'], $result['facility_name'], $result['job_role_name'], $result['status'],
                    $result['satisfied_count'], $result['required_count'], $result['percentage'], $date->toDateString()];
            }
        }
    }

    /** @param list<string|int|float|null> $row
     * @return list<string>
     */
    public function safeCsvRow(array $row): array
    {
        return array_map(function (string|int|float|null $cell): string {
            $text = (string) $cell;

            return preg_match('/^[\s\x{FEFF}]*[=+@\-]/u', $text) || preg_match('/^[\t\r\n]/', $text) ? "'".$text : $text;
        }, $row);
    }
}
