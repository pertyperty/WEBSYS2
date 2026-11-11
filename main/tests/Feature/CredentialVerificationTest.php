<?php

namespace Tests\Feature;

use App\Actions\VerifyCredentialReference;
use App\Enums\CredentialEventType;
use App\Enums\CredentialStatus;
use App\Enums\TrainingStatus;
use App\Models\CredentialDocument;
use App\Models\EmployeeTraining;
use App\Models\Qualification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CredentialVerificationTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['2026-11-11', 'valid'])]
    #[TestWith(['2026-11-10', 'expired'])]
    #[TestWith(['2026-12-11', 'valid'])]
    public function test_reference_validity_uses_inclusive_manila_dates(string $expiry, string $expected): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 23:59:00', 'Asia/Manila'));
        $document = CredentialDocument::factory()->create(['status' => CredentialStatus::Approved, 'issued_at' => '2026-10-01', 'expires_at' => $expiry]);

        $result = app(VerifyCredentialReference::class)->handle($document);

        $this->assertSame($expected, $result['validity']);
        $this->assertSame('2026-11-11', $result['evaluated_on']);
    }

    public function test_pending_reference_is_not_substituted_with_an_older_valid_approval(): void
    {
        $this->freezeTime();
        $approved = CredentialDocument::factory()->create(['status' => CredentialStatus::Approved]);
        $renewal = CredentialDocument::factory()->create(['employee_id' => $approved->employee_id, 'qualification_id' => $approved->qualification_id]);

        $result = app(VerifyCredentialReference::class)->handle($renewal);

        $this->assertSame($renewal->reference, $result['reference']);
        $this->assertSame('pending', $result['review_status']);
        $this->assertSame('not_valid', $result['validity']);
        $this->assertNull($result['reviewed_at']);
        $this->assertSame('valid', app(VerifyCredentialReference::class)->handle($approved)['validity']);
    }

    public function test_response_uses_the_actual_review_event_time_without_private_feedback(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        $document = CredentialDocument::factory()->create(['status' => CredentialStatus::Rejected]);
        $document->events()->create(['actor_id' => $document->employee_id, 'type' => CredentialEventType::Rejected, 'note' => 'PRIVATE REJECTION'])->forceFill(['created_at' => '2026-11-10 02:00:00'])->save();
        $document->update(['issued_at' => '2026-11-09']);

        $result = app(VerifyCredentialReference::class)->handle($document);

        $this->assertSame('2026-11-10T02:00:00.000000Z', $result['reviewed_at']);
        $this->assertSame('not_valid', $result['validity']);
        $this->assertArrayNotHasKey('note', $result);
        $this->assertStringNotContainsString('PRIVATE REJECTION', json_encode($result));
    }

    public function test_non_expiring_evidence_future_issuance_and_inactive_qualification_follow_shared_rules(): void
    {
        $this->freezeTime();
        $qualification = Qualification::factory()->create(['requires_expiry' => false]);
        $document = CredentialDocument::factory()->create(['qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'expires_at' => null]);
        $verify = app(VerifyCredentialReference::class);
        $this->assertSame('valid', $verify->handle($document)['validity']);
        $document->update(['issued_at' => now('Asia/Manila')->addDay()->toDateString()]);
        $this->assertSame('not_valid', $verify->handle($document->fresh())['validity']);
        $document->update(['issued_at' => now('Asia/Manila')->toDateString()]);
        $qualification->update(['is_active' => false]);
        $this->assertSame('not_valid', $verify->handle($document->fresh())['validity']);
    }

    public function test_training_must_be_complete_for_the_referenced_document(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        $enrollment = EmployeeTraining::factory()->create();
        $qualification = $enrollment->trainingCourse->qualification;
        $qualification->update(['requires_training' => true]);
        $document = CredentialDocument::factory()->create(['employee_id' => $enrollment->employee_id, 'employee_training_id' => $enrollment->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved]);
        $verify = app(VerifyCredentialReference::class);
        $this->assertSame('not_valid', $verify->handle($document)['validity']);

        $enrollment->forceFill(['status' => TrainingStatus::Completed, 'completed_at' => '2026-11-11'])->save();

        $this->assertSame('valid', $verify->handle($document->fresh())['validity']);
        $this->assertDatabaseCount('compliance_assessments', 0);
    }
}
