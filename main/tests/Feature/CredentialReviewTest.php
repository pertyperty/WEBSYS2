<?php

namespace Tests\Feature;

use App\Actions\SelectEffectiveCredential;
use App\Enums\CourseStatus;
use App\Enums\CredentialEventType;
use App\Enums\CredentialStatus;
use App\Enums\UserRole;
use App\Models\CredentialDocument;
use App\Models\CredentialEvent;
use App\Models\EmployeeTraining;
use App\Models\Qualification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class CredentialReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_officer_approval_records_an_event_and_effective_evidence_without_changing_metadata(): void
    {
        Storage::fake('local');
        $document = $this->stored(CredentialDocument::factory()->create());
        $document->events()->create(['actor_id' => $document->employee_id, 'type' => CredentialEventType::Submitted]);
        $officer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $this->actingAs($officer)->post("/credentials/{$document->id}/reviews", [
            'decision' => 'approved', 'issued_at' => '1900-01-01', 'employee_id' => $officer->id,
        ])->assertRedirect("/credentials/{$document->id}")->assertSessionHasNoErrors();
        $reviewed = $document->fresh();
        $this->assertSame(CredentialStatus::Approved, $reviewed->status);
        $this->assertSame($document->issued_at->toDateString(), $reviewed->issued_at->toDateString());
        $this->assertSame($document->private_path, $reviewed->private_path);
        $this->assertSame($document->employee_id, $reviewed->employee_id);
        $this->assertSame([CredentialEventType::Submitted, CredentialEventType::Approved], $reviewed->events->pluck('type')->all());
        $this->assertSame($officer->id, $reviewed->events->last()->actor_id);
        $this->assertDatabaseHas('employee_qualification', ['employee_id' => $document->employee_id, 'qualification_id' => $document->qualification_id, 'credential_document_id' => $document->id]);
        $this->patch("/credentials/{$document->id}", ['issued_at' => '1900-01-01'])->assertStatus(405);
        $this->delete("/credentials/{$document->id}")->assertStatus(405);
    }

    public function test_a_second_officer_cannot_overwrite_a_recorded_decision(): void
    {
        Storage::fake('local');
        $document = $this->stored(CredentialDocument::factory()->create());
        $first = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $second = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $this->actingAs($first)->post("/credentials/{$document->id}/reviews", ['decision' => 'approved'])->assertSessionHasNoErrors();
        $this->actingAs($second)->post("/credentials/{$document->id}/reviews", ['decision' => 'rejected', 'note' => 'Stale review'])->assertSessionHasErrors('decision');
        $this->assertSame(CredentialStatus::Approved, $document->fresh()->status);
        $this->assertSame(1, $document->events()->count());
        $this->assertSame($first->id, $document->events()->first()->actor_id);
    }

    public function test_admin_staff_and_self_review_cannot_approve_evidence(): void
    {
        Storage::fake('local');
        $document = $this->stored(CredentialDocument::factory()->create());
        $this->actingAs($document->employee)->post("/credentials/{$document->id}/reviews", ['decision' => 'approved'])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))->post("/credentials/{$document->id}/reviews", ['decision' => 'approved'])->assertForbidden();
        $employee = $document->employee;
        $employee->forceFill(['role' => UserRole::ComplianceOfficer, 'facility_id' => null, 'job_role_id' => null])->save();
        $this->actingAs($employee)->post("/credentials/{$document->id}/reviews", ['decision' => 'approved'])->assertSessionHasErrors(['decision' => 'You cannot review your own evidence.']);
        $this->assertSame(CredentialStatus::Pending, $document->fresh()->status);
        $this->assertSame(0, $document->events()->count());
    }

    public function test_rejection_requires_a_reason_and_feedback_is_retained_and_escaped(): void
    {
        Storage::fake('local');
        $document = $this->stored(CredentialDocument::factory()->create());
        $this->actingAs(User::factory()->create(['role' => UserRole::ComplianceOfficer]));
        $url = "/credentials/{$document->id}/reviews";
        $this->post($url, ['decision' => 'pending'])->assertSessionHasErrors('decision');
        $this->post($url, ['decision' => 'rejected'])->assertSessionHasErrors('note');
        $this->post($url, ['decision' => 'rejected', 'note' => '   '])->assertSessionHasErrors('note');
        $this->post($url, ['decision' => 'approved', 'note' => str_repeat('x', 2001)])->assertSessionHasErrors('note');
        $this->post($url, ['decision' => 'rejected', 'note' => '<script>alert(1)</script>'])->assertSessionHasNoErrors();
        $this->assertSame(CredentialStatus::Rejected, $document->fresh()->status);
        $this->assertSame('<script>alert(1)</script>', $document->events()->first()->note);
        $this->get("/credentials/{$document->id}")->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
        $this->assertDatabaseCount('employee_qualification', 0);
    }

    public function test_training_evidence_waits_for_matching_completed_enrollment_and_survives_course_archival(): void
    {
        Storage::fake('local');
        $this->freezeTime();
        $enrollment = EmployeeTraining::factory()->create();
        $document = $this->stored(CredentialDocument::factory()->for($enrollment->employee, 'employee')->for($enrollment->trainingCourse->qualification)->create(['employee_training_id' => $enrollment->id]));
        $officer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $this->actingAs($officer)->post("/credentials/{$document->id}/reviews", ['decision' => 'approved'])->assertSessionHasErrors(['decision' => 'Approval requires a matching completed training enrollment.']);
        $this->assertSame(CredentialStatus::Pending, $document->fresh()->status);
        $this->assertSame(0, $document->events()->count());
        $this->patch("/training-enrollments/{$enrollment->id}/completion", ['completed_at' => now('Asia/Manila')->toDateString()])->assertSessionHasNoErrors();
        $this->post("/credentials/{$document->id}/reviews", ['decision' => 'approved'])->assertSessionHasNoErrors();
        $enrollment->trainingCourse->update(['status' => CourseStatus::Archived]);
        $this->assertSame($document->id, app(SelectEffectiveCredential::class)->find($document->employee, $document->qualification)->id);
    }

    public function test_review_rechecks_enrollment_ownership_even_for_inconsistent_legacy_metadata(): void
    {
        Storage::fake('local');
        $enrollment = EmployeeTraining::factory()->create(['status' => 'completed', 'completed_at' => now('Asia/Manila')->toDateString()]);
        $document = $this->stored(CredentialDocument::factory()->for($enrollment->trainingCourse->qualification)->create(['employee_training_id' => $enrollment->id]));
        $this->actingAs(User::factory()->create(['role' => UserRole::ComplianceOfficer]))->post("/credentials/{$document->id}/reviews", ['decision' => 'approved'])->assertSessionHasErrors('decision');
        $this->assertSame(CredentialStatus::Pending, $document->fresh()->status);
    }

    public function test_pending_and_rejected_renewals_preserve_older_valid_approval(): void
    {
        Storage::fake('local');
        $this->freezeTime();
        $old = $this->stored(CredentialDocument::factory()->create(['status' => CredentialStatus::Approved]));
        $old->employee->qualifications()->attach($old->qualification_id, ['credential_document_id' => $old->id]);
        $renewal = $this->stored(CredentialDocument::factory()->for($old->employee, 'employee')->for($old->qualification)->create());
        $selection = app(SelectEffectiveCredential::class);
        $this->assertSame($old->id, $selection->find($old->employee, $old->qualification)->id);
        $this->assertDatabaseHas('employee_qualification', ['employee_id' => $old->employee_id, 'credential_document_id' => $old->id]);
        $this->actingAs(User::factory()->create(['role' => UserRole::ComplianceOfficer]))->post("/credentials/{$renewal->id}/reviews", ['decision' => 'rejected', 'note' => 'Renewal needs correction'])->assertSessionHasNoErrors();
        $this->assertSame($old->id, $selection->find($old->employee, $old->qualification)->id);
        $this->assertDatabaseHas('employee_qualification', ['employee_id' => $old->employee_id, 'credential_document_id' => $old->id]);
    }

    public function test_expired_newer_approval_does_not_mask_valid_old_evidence_and_valid_renewal_can_replace_it(): void
    {
        Storage::fake('local');
        $this->freezeTime();
        $old = $this->stored(CredentialDocument::factory()->create(['status' => CredentialStatus::Approved, 'issued_at' => now('Asia/Manila')->subMonth()->toDateString()]));
        $expired = $this->stored(CredentialDocument::factory()->for($old->employee, 'employee')->for($old->qualification)->create([
            'issued_at' => now('Asia/Manila')->subDays(2)->toDateString(), 'expires_at' => now('Asia/Manila')->subDay()->toDateString(),
        ]));
        $this->actingAs(User::factory()->create(['role' => UserRole::ComplianceOfficer]))->post("/credentials/{$expired->id}/reviews", ['decision' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame(CredentialStatus::Approved, $expired->fresh()->status);
        $this->assertDatabaseHas('employee_qualification', ['employee_id' => $old->employee_id, 'credential_document_id' => $old->id]);
        $renewal = $this->stored(CredentialDocument::factory()->for($old->employee, 'employee')->for($old->qualification)->create());
        $this->post("/credentials/{$renewal->id}/reviews", ['decision' => 'approved'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employee_qualification', ['employee_id' => $old->employee_id, 'credential_document_id' => $renewal->id]);
        $this->assertSame(CredentialStatus::Approved, $old->fresh()->status);
    }

    public function test_validity_includes_the_entire_manila_expiry_date_and_excludes_future_issue_dates(): void
    {
        Storage::fake('local');
        $this->travelTo(CarbonImmutable::parse('2026-11-10 15:59:00', 'UTC'));
        $document = $this->stored(CredentialDocument::factory()->create(['status' => CredentialStatus::Approved, 'issued_at' => '2026-11-01', 'expires_at' => '2026-11-10']));
        $selection = app(SelectEffectiveCredential::class);
        $document->employee->qualifications()->attach($document->qualification_id, ['credential_document_id' => $document->id]);
        $this->assertSame($document->id, $selection->find($document->employee, $document->qualification)->id);
        $this->travelTo(CarbonImmutable::parse('2026-11-10 16:00:00', 'UTC'));
        $this->assertNull($selection->find($document->employee, $document->qualification));
        $this->actingAs($document->employee)->get("/credentials/{$document->id}")->assertOk()->assertSee('No approved evidence currently qualifies for this qualification.');
        $document->forceFill(['issued_at' => '2026-11-12', 'expires_at' => '2027-11-12'])->save();
        $this->assertNull($selection->find($document->employee, $document->qualification));
    }

    public function test_non_expiring_evidence_is_valid_only_when_the_qualification_permits_it(): void
    {
        Storage::fake('local');
        $document = $this->stored(CredentialDocument::factory()->for(Qualification::factory()->state(['requires_expiry' => false]))->create(['status' => CredentialStatus::Approved, 'expires_at' => null]));
        $selection = app(SelectEffectiveCredential::class);
        $this->assertSame($document->id, $selection->find($document->employee, $document->qualification)->id);
        $document->qualification->update(['requires_expiry' => true]);
        $this->assertNull($selection->find($document->employee, $document->qualification));
    }

    public function test_missing_files_cannot_be_approved_but_can_be_rejected_with_a_reason(): void
    {
        Storage::fake('local');
        $document = CredentialDocument::factory()->create();
        $this->actingAs(User::factory()->create(['role' => UserRole::ComplianceOfficer]));
        $this->post("/credentials/{$document->id}/reviews", ['decision' => 'approved'])->assertSessionHasErrors(['decision' => 'Restore or resubmit the missing evidence file before approval.']);
        $this->assertSame(CredentialStatus::Pending, $document->fresh()->status);
        $this->post("/credentials/{$document->id}/reviews", ['decision' => 'rejected', 'note' => 'Please resubmit the unavailable file.'])->assertSessionHasNoErrors();
        $this->assertSame(CredentialStatus::Rejected, $document->fresh()->status);
    }

    public function test_failed_review_event_rolls_back_the_decision_and_effective_link(): void
    {
        Storage::fake('local');
        $document = $this->stored(CredentialDocument::factory()->create());
        $officer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        Exceptions::fake();
        $eventName = 'eloquent.creating: '.CredentialEvent::class;
        Event::listen($eventName, function (): void {
            throw new RuntimeException('Simulated history write failure');
        });
        try {
            $this->actingAs($officer)->post("/credentials/{$document->id}/reviews", ['decision' => 'approved'])->assertInternalServerError();
            $this->assertSame(CredentialStatus::Pending, $document->fresh()->status);
            $this->assertSame(0, $document->events()->count());
            $this->assertDatabaseCount('employee_qualification', 0);
            Storage::disk('local')->assertExists($document->private_path);
            Exceptions::assertReported(RuntimeException::class);
        } finally {
            Event::forget($eventName);
        }
    }

    public function test_inactive_qualification_configuration_cannot_count_as_effective_evidence(): void
    {
        Storage::fake('local');
        $document = $this->stored(CredentialDocument::factory()->create(['status' => CredentialStatus::Approved]));
        $qualification = $document->qualification;
        $selection = app(SelectEffectiveCredential::class);
        $qualification->update(['is_active' => false]);
        $this->assertNull($selection->find($document->employee, $qualification));
        $qualification->update(['is_active' => true]);
        $qualification->safetyDomain->update(['is_active' => false]);
        $this->assertNull($selection->find($document->employee, $qualification));
    }

    private function stored(CredentialDocument $document): CredentialDocument
    {
        Storage::disk('local')->put($document->private_path, "%PDF-1.4\n%%EOF\n");

        return $document;
    }
}
