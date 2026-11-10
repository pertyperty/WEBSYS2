<?php

namespace Tests\Feature;

use App\Actions\AssessEmployeeCompliance;
use App\Enums\CredentialStatus;
use App\Enums\TrainingStatus;
use App\Enums\UserRole;
use App\Models\CredentialDocument;
use App\Models\EmployeeTraining;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\TrainingCourse;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ComplianceAssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_required_qualifications_count_and_all_must_be_satisfied(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Manila'));
        [$employee, $required] = $this->employeeWithRequirement();
        $optional = Qualification::factory()->create();
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $optional->id, 'status' => CredentialStatus::Approved]);
        $result = $this->assess($employee);
        $this->assertSame('not_eligible', $result['status']);
        $this->assertSame(0.0, $result['percentage']);
        $this->assertSame(1, $result['required_count']);
        $this->assertArrayNotHasKey($optional->id, $result['items']);
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $required->id, 'status' => CredentialStatus::Approved]);
        $result = $this->assess($employee);
        $this->assertSame('eligible', $result['status']);
        $this->assertSame(100.0, $result['percentage']);
    }

    #[DataProvider('expiryBoundaries')]
    public function test_expiry_and_warning_use_inclusive_manila_calendar_dates(string $expiry, string $status, bool $warning): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 15:59:59', 'UTC'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'issued_at' => '2026-01-01', 'expires_at' => $expiry]);
        $result = $this->assess($employee);
        $this->assertSame($status, $result['status']);
        $this->assertSame($warning, $result['items'][$qualification->id]['expiring_soon']);
        $this->assertSame('2026-11-10', $result['assessed_on']);
        if ($expiry === '2026-11-10') {
            $this->travelTo(CarbonImmutable::parse('2026-11-10 16:00:00', 'UTC'));
            $this->assertSame('expired', $this->assess($employee)['items'][$qualification->id]['state']);
        }
    }

    public static function expiryBoundaries(): array
    {
        return [
            'yesterday' => ['2026-11-09', 'not_eligible', false],
            'today' => ['2026-11-10', 'eligible', true],
            'day thirty' => ['2026-12-10', 'eligible', true],
            'day thirty one' => ['2026-12-11', 'eligible', false],
        ];
    }

    public function test_pending_rejected_and_expired_renewals_preserve_an_older_valid_approval(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Manila'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        $valid = CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'issued_at' => '2026-01-01']);
        foreach ([CredentialStatus::Pending, CredentialStatus::Rejected, CredentialStatus::Approved] as $status) {
            CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => $status, 'issued_at' => '2026-02-01', 'expires_at' => '2026-11-09']);
        }
        $result = $this->assess($employee);
        $this->assertSame('eligible', $result['status']);
        $this->assertSame($valid->id, $result['items'][$qualification->id]['document_id']);
        $this->assertSame(1, $result['items'][$qualification->id]['pending_count']);
    }

    public function test_newest_valid_issue_date_then_id_selects_effective_evidence(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Manila'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        $documents = CredentialDocument::factory()->count(2)->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'issued_at' => '2026-02-01']);
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'issued_at' => '2026-01-01']);
        $this->assertSame($documents->last()->id, $this->assess($employee)['items'][$qualification->id]['document_id']);
    }

    public function test_unsatisfied_evidence_uses_pending_then_latest_rejection_then_expired_then_missing(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Manila'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        $this->assertSame('missing', $this->assess($employee)['items'][$qualification->id]['state']);
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'expires_at' => '2026-11-09']);
        $this->assertSame('expired', $this->assess($employee)['items'][$qualification->id]['state']);
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Rejected]);
        $this->assertSame('rejected', $this->assess($employee)['items'][$qualification->id]['state']);
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id]);
        $this->assertSame('pending', $this->assess($employee)['items'][$qualification->id]['state']);
    }

    public function test_nonexpiring_evidence_and_future_issue_dates_respect_qualification_rules(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Manila'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        $document = CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'expires_at' => null]);
        $this->assertSame('not_eligible', $this->assess($employee)['status']);
        $qualification->update(['requires_expiry' => false]);
        $this->assertSame('eligible', $this->assess($employee)['status']);
        $document->forceFill(['issued_at' => '2026-11-11'])->save();
        $this->assertSame('not_eligible', $this->assess($employee)['status']);
    }

    public function test_training_requires_matching_owned_completed_enrollment_linked_to_evidence(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Manila'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        $qualification->update(['requires_training' => true]);
        $course = TrainingCourse::factory()->published()->create(['qualification_id' => $qualification->id]);
        $enrollment = EmployeeTraining::factory()->create(['employee_id' => $employee->id, 'training_course_id' => $course->id]);
        $document = CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'employee_training_id' => $enrollment->id, 'status' => CredentialStatus::Approved]);
        $this->assertFalse($this->assess($employee)['items'][$qualification->id]['training_completed']);
        $this->assertSame('not_eligible', $this->assess($employee)['status']);
        $enrollment->forceFill(['status' => TrainingStatus::Completed, 'completed_at' => '2026-11-11'])->save();
        $this->assertSame('not_eligible', $this->assess($employee)['status']);
        $enrollment->forceFill(['completed_at' => '2026-11-10'])->save();
        $this->assertSame('eligible', $this->assess($employee)['status']);
        $document->forceFill(['employee_training_id' => null])->save();
        $result = $this->assess($employee);
        $this->assertTrue($result['items'][$qualification->id]['training_completed']);
        $this->assertSame('not_eligible', $result['status']);
        $other = EmployeeTraining::factory()->create(['training_course_id' => $course->id, 'status' => TrainingStatus::Completed, 'completed_at' => '2026-11-10']);
        $document->forceFill(['employee_training_id' => $other->id])->save();
        $this->assertSame('not_eligible', $this->assess($employee)['status']);
    }

    public function test_missing_assignments_requirements_or_inactive_configuration_never_grant_eligibility(): void
    {
        $unassigned = User::factory()->create();
        $this->assertNull($this->assess($unassigned)['percentage']);
        [$employee, $qualification] = $this->employeeWithRequirement();
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved]);
        $employee->forceFill(['is_active' => false])->save();
        $this->assertSame('not_assessable', $this->assess($employee)['status']);
        $employee->forceFill(['is_active' => true, 'role' => UserRole::Admin])->save();
        $this->assertSame('not_assessable', $this->assess($employee)['status']);
        $employee->forceFill(['role' => UserRole::Staff])->save();
        foreach ([$employee->facility, $employee->facility->region, $employee->jobRole, $qualification, $qualification->safetyDomain] as $configuration) {
            $configuration->update(['is_active' => false]);
            $result = $this->assess($employee);
            $this->assertSame('not_assessable', $result['status']);
            $this->assertNull($result['percentage']);
            $configuration->update(['is_active' => true]);
        }
        $employee->jobRole->qualifications()->detach();
        $this->assertSame('not_assessable', $this->assess($employee)['status']);
        $this->assertNull($this->assess($employee)['percentage']);
    }

    public function test_cached_award_pointer_does_not_override_expired_evidence(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Manila'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        $expired = CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'expires_at' => '2026-11-09']);
        $employee->qualifications()->attach($qualification, ['credential_document_id' => $expired->id]);
        $this->assertSame('not_eligible', $this->assess($employee)['status']);
    }

    public function test_eager_loaded_assessments_do_not_query_for_each_employee_or_requirement(): void
    {
        [$employee, $qualification] = $this->employeeWithRequirement();
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id]);
        $second = User::factory()->create(['facility_id' => $employee->facility_id, 'job_role_id' => $employee->job_role_id]);
        $employees = User::whereIn('id', [$employee->id, $second->id])->with(AssessEmployeeCompliance::RELATIONS)->get();
        DB::enableQueryLog();
        DB::flushQueryLog();
        foreach ($employees as $record) {
            app(AssessEmployeeCompliance::class)->handle($record);
        }
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(0, $queries);
    }

    public function test_partial_percentage_is_rounded_for_display_without_granting_eligibility(): void
    {
        [$employee, $qualification] = $this->employeeWithRequirement();
        $additional = Qualification::factory()->count(2)->create();
        $employee->jobRole->qualifications()->attach($additional->modelKeys());
        foreach ([$qualification, $additional->first()] as $satisfied) {
            CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $satisfied->id, 'status' => CredentialStatus::Approved]);
        }
        $result = $this->assess($employee);
        $this->assertSame(66.67, $result['percentage']);
        $this->assertSame(2, $result['satisfied_count']);
        $this->assertSame(3, $result['required_count']);
        $this->assertSame('not_eligible', $result['status']);
    }

    /** @return array{User, Qualification} */
    private function employeeWithRequirement(): array
    {
        $employee = User::factory()->for(Facility::factory())->for(JobRole::factory())->create();
        $qualification = Qualification::factory()->create();
        $employee->jobRole->qualifications()->attach($qualification);

        return [$employee, $qualification];
    }

    /** @return array<string, mixed> */
    private function assess(User $employee): array
    {
        return app(AssessEmployeeCompliance::class)->handle($employee->fresh());
    }
}
