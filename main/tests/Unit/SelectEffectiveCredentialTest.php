<?php

namespace Tests\Unit;

use App\Actions\IsCredentialDateValid;
use App\Actions\SelectEffectiveCredential;
use App\Enums\CredentialStatus;
use App\Enums\TrainingStatus;
use App\Models\CredentialDocument;
use App\Models\EmployeeTraining;
use App\Models\Qualification;
use App\Models\SafetyDomain;
use App\Models\TrainingCourse;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SelectEffectiveCredentialTest extends TestCase
{
    private function action(): SelectEffectiveCredential
    {
        return new SelectEffectiveCredential(new IsCredentialDateValid());
    }

    private function employee(int $id = 1): User
    {
        $employee = new User();
        $employee->setAttribute('id', $id);

        return $employee;
    }

    private function qualification(
        int $id = 10,
        bool $active = true,
        bool $requiresExpiry = true,
        bool $requiresTraining = false,
        bool $domainActive = true
    ): Qualification {
        $qualification = new Qualification();

        $qualification->setRawAttributes([
            'id' => $id,
            'is_active' => $active,
            'requires_expiry' => $requiresExpiry,
            'requires_training' => $requiresTraining,
        ]);

        $domain = new SafetyDomain();
        $domain->setRawAttributes([
            'id' => 1,
            'is_active' => $domainActive,
        ]);

        $qualification->setRelation('safetyDomain', $domain);

        return $qualification;
    }

    private function document(
        int $id = 1,
        int $employeeId = 1,
        int $qualificationId = 10,
        CredentialStatus $status = CredentialStatus::Approved,
        string $issuedAt = '2026-01-01',
        string $expiresAt = '2026-12-31',
        ?EmployeeTraining $training = null
    ): CredentialDocument {
        $document = new CredentialDocument();

        $document->setRawAttributes([
            'id' => $id,
            'employee_id' => $employeeId,
            'qualification_id' => $qualificationId,
            'status' => $status->value,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
        ]);

        $document->setRelation('employeeTraining', $training);

        return $document;
    }

    private function completedTraining(
        int $employeeId = 1,
        int $qualificationId = 10,
        TrainingStatus $status = TrainingStatus::Completed,
        ?string $completedAt = '2026-01-01',
        ?int $courseQualificationId = null
    ): EmployeeTraining {
        $course = new TrainingCourse();

        $course->setRawAttributes([
            'id' => 1,
            'qualification_id' => $courseQualificationId ?? $qualificationId,
        ]);

        $training = new EmployeeTraining();

        $training->setRawAttributes([
            'id' => 1,
            'employee_id' => $employeeId,
            'status' => $status->value,
            'completed_at' => $completedAt,
        ]);

        $training->setRelation('trainingCourse', $course);

        return $training;
    }

    private function asOf(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-06-15', 'Asia/Manila')
            ->startOfDay();
    }

    public function test_it_selects_the_newest_valid_approved_credential(): void
    {
        $employee = $this->employee();
        $qualification = $this->qualification();

        $older = $this->document(
            id: 1,
            issuedAt: '2026-01-01'
        );

        $newer = $this->document(
            id: 2,
            issuedAt: '2026-03-01'
        );

        $result = $this->action()->choose(
            $employee,
            $qualification,
            collect([$older, $newer]),
            $this->asOf()
        );

        $this->assertSame($newer, $result);
    }

    public function test_it_uses_the_highest_id_when_issue_dates_are_equal(): void
    {
        $employee = $this->employee();
        $qualification = $this->qualification();

        $first = $this->document(id: 1);
        $second = $this->document(id: 2);

        $result = $this->action()->choose(
            $employee,
            $qualification,
            collect([$first, $second]),
            $this->asOf()
        );

        $this->assertSame($second, $result);
    }

    public function test_it_ignores_credentials_belonging_to_another_employee(): void
    {
        $employee = $this->employee(1);
        $qualification = $this->qualification();

        $otherEmployeeDocument = $this->document(
            employeeId: 2
        );

        $result = $this->action()->choose(
            $employee,
            $qualification,
            collect([$otherEmployeeDocument]),
            $this->asOf()
        );

        $this->assertNull($result);
    }

    public function test_it_ignores_pending_and_rejected_credentials(): void
    {
        $employee = $this->employee();
        $qualification = $this->qualification();

        $pending = $this->document(
            id: 1,
            status: CredentialStatus::Pending
        );

        $rejected = $this->document(
            id: 2,
            status: CredentialStatus::Rejected
        );

        $result = $this->action()->choose(
            $employee,
            $qualification,
            collect([$pending, $rejected]),
            $this->asOf()
        );

        $this->assertNull($result);
    }

    public function test_it_ignores_expired_credentials(): void
    {
        $employee = $this->employee();
        $qualification = $this->qualification();

        $expired = $this->document(
            expiresAt: '2026-06-14'
        );

        $result = $this->action()->choose(
            $employee,
            $qualification,
            collect([$expired]),
            $this->asOf()
        );

        $this->assertNull($result);
    }

    public function test_it_rejects_credentials_for_inactive_qualifications_or_domains(): void
    {
        $employee = $this->employee();
        $document = $this->document();

        $inactiveQualification = $this->qualification(active: false);

        $inactiveDomain = $this->qualification(domainActive: false);

        $this->assertFalse(
            $this->action()->isQualifying(
                $document,
                $inactiveQualification,
                $this->asOf()
            )
        );

        $this->assertFalse(
            $this->action()->isQualifying(
                $document,
                $inactiveDomain,
                $this->asOf()
            )
        );
    }

    public function test_it_requires_completed_matching_training_when_required(): void
    {
        $employee = $this->employee();
        $qualification = $this->qualification(
            requiresTraining: true
        );

        $training = $this->completedTraining();

        $document = $this->document(
            training: $training
        );

        $result = $this->action()->choose(
            $employee,
            $qualification,
            collect([$document]),
            $this->asOf()
        );

        $this->assertSame($document, $result);
    }

    public function test_it_rejects_training_for_another_employee_or_qualification(): void
    {
        $qualification = $this->qualification(
            requiresTraining: true
        );

        $wrongEmployeeTraining = $this->completedTraining(
            employeeId: 2
        );

        $wrongQualificationTraining = $this->completedTraining(
            courseQualificationId: 99
        );

        $wrongEmployeeDocument = $this->document(
            training: $wrongEmployeeTraining
        );

        $wrongQualificationDocument = $this->document(
            training: $wrongQualificationTraining
        );

        $this->assertFalse(
            $this->action()->isQualifying(
                $wrongEmployeeDocument,
                $qualification,
                $this->asOf()
            )
        );

        $this->assertFalse(
            $this->action()->isQualifying(
                $wrongQualificationDocument,
                $qualification,
                $this->asOf()
            )
        );
    }

    public function test_it_rejects_training_completed_in_the_future(): void
    {
        $qualification = $this->qualification(
            requiresTraining: true
        );

        $futureTraining = $this->completedTraining(
            completedAt: '2026-06-16'
        );

        $document = $this->document(
            training: $futureTraining
        );

        $this->assertFalse(
            $this->action()->isQualifying(
                $document,
                $qualification,
                $this->asOf()
            )
        );
    }

    public function test_it_returns_null_when_no_credential_qualifies(): void
    {
        $employee = $this->employee();
        $qualification = $this->qualification();

        $invalidDocument = $this->document(
            status: CredentialStatus::Pending
        );

        $result = $this->action()->choose(
            $employee,
            $qualification,
            collect([$invalidDocument]),
            $this->asOf()
        );

        $this->assertNull($result);
    }
}
