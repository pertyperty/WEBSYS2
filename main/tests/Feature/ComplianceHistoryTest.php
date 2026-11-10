<?php

namespace Tests\Feature;

use App\Actions\RecordComplianceAssessment;
use App\Actions\ReviewCredential;
use App\Actions\SubmitCredential;
use App\Enums\CredentialStatus;
use App\Enums\UserRole;
use App\Models\ComplianceAssessment;
use App\Models\CredentialDocument;
use App\Models\EmployeeTraining;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\TrainingCourse;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ComplianceHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_deduplicates_unchanged_days_and_keeps_returns_to_prior_state(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Manila'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        $history = app(RecordComplianceAssessment::class);
        $history->handle($employee, 'read');
        $first = ComplianceAssessment::firstOrFail();
        $original = $first->snapshot;
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        $current = $history->handle($employee, 'read');
        $this->assertSame('2026-11-11', $current['assessed_on']);
        $this->assertDatabaseCount('compliance_assessments', 1);
        $employee->forceFill(['is_active' => false])->save();
        $history->handle($employee, 'assignment');
        $employee->forceFill(['is_active' => true])->save();
        $history->handle($employee, 'assignment');
        $this->assertDatabaseCount('compliance_assessments', 3);
        $this->assertSame($original, $first->fresh()->snapshot);
        $this->assertSame($first->fingerprint, ComplianceAssessment::latest('id')->first()->fingerprint);
    }

    public function test_daily_reconciliation_records_warning_and_expiry_transitions_idempotently(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Manila'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'expires_at' => '2026-12-11']);
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->assertDatabaseCount('compliance_assessments', 1);
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->assertDatabaseCount('compliance_assessments', 2);
        $this->assertTrue(ComplianceAssessment::latest('id')->first()->snapshot['items'][$qualification->id]['expiring_soon']);
        $this->travelTo(CarbonImmutable::parse('2026-12-12 00:00', 'Asia/Manila'));
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->assertDatabaseCount('compliance_assessments', 3);
        $latest = ComplianceAssessment::latest('id')->first();
        $this->assertSame('not_eligible', $latest->status->value);
        $this->assertNull($latest->actor_id);
        $this->assertSame('date_reconciliation', $latest->source);
    }

    public function test_assignment_requirement_and_qualification_changes_record_material_history(): void
    {
        [$employee, $qualification] = $this->employeeWithRequirement();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->patch("/admin/users/{$employee->id}/assignment", [
            'role' => 'staff', 'is_active' => true, 'facility_id' => $employee->facility_id, 'job_role_id' => $employee->job_role_id,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('compliance_assessments', ['employee_id' => $employee->id, 'actor_id' => $admin->id, 'source' => 'assignment']);
        $this->put("/job-roles/{$employee->job_role_id}/requirements", ['qualification_ids' => []])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('compliance_assessments', ['employee_id' => $employee->id, 'source' => 'requirements', 'status' => 'not_assessable']);
        $this->put("/job-roles/{$employee->job_role_id}/requirements", ['qualification_ids' => [$qualification->id]])->assertSessionHasNoErrors();
        $this->put("/qualifications/{$qualification->id}", [
            'name' => $qualification->name, 'safety_domain_id' => $qualification->safety_domain_id,
            'requires_expiry' => false, 'requires_training' => false, 'is_active' => true,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('compliance_assessments', ['employee_id' => $employee->id, 'source' => 'qualification']);
        $this->assertFalse(ComplianceAssessment::latest('id')->first()->snapshot['items'][$qualification->id]['requires_expiry']);
    }

    public function test_submission_review_and_training_completion_record_history_without_private_content(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Manila'));
        Storage::fake('local');
        [$employee, $qualification] = $this->employeeWithRequirement();
        $document = app(SubmitCredential::class)->handle($employee, UploadedFile::fake()->create('synthetic.pdf', 1, 'application/pdf'), [
            'qualification_id' => $qualification->id, 'issued_at' => '2026-11-10', 'expires_at' => '2027-11-10',
        ]);
        Storage::disk('local')->assertExists($document->private_path);
        $this->assertDatabaseHas('compliance_assessments', ['employee_id' => $employee->id, 'source' => 'submission']);
        $officer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $this->actingAs($officer)->post("/credentials/{$document->id}/reviews", ['decision' => 'approved', 'note' => 'PRIVATE REVIEW NOTE'])->assertSessionHasNoErrors();
        $latest = ComplianceAssessment::latest('id')->first();
        $this->assertSame('review', $latest->source);
        $this->assertSame('eligible', $latest->status->value);
        $this->assertSame($officer->id, $latest->actor_id);
        $this->assertStringNotContainsString('PRIVATE REVIEW NOTE', json_encode($latest->snapshot));
        $this->assertStringNotContainsString($document->private_path, json_encode($latest->snapshot));
        $qualification->update(['requires_training' => true]);
        $course = TrainingCourse::factory()->published()->create(['qualification_id' => $qualification->id]);
        $enrollment = EmployeeTraining::factory()->create(['employee_id' => $employee->id, 'training_course_id' => $course->id, 'enrolled_at' => '2026-11-10 00:00:00']);
        $this->patch("/training-enrollments/{$enrollment->id}/completion", ['completed_at' => '2026-11-10', 'feedback' => 'PRIVATE TRAINING NOTE'])->assertSessionHasNoErrors();
        $latest = ComplianceAssessment::latest('id')->first();
        $this->assertSame('training_completion', $latest->source);
        $this->assertTrue($latest->snapshot['items'][$qualification->id]['training_completed']);
        $this->assertSame('not_eligible', $latest->status->value);
        $this->assertStringNotContainsString('PRIVATE TRAINING NOTE', json_encode($latest->snapshot));
    }

    public function test_history_uses_current_scope_after_employee_transfer_and_preserves_old_context(): void
    {
        [$employee] = $this->employeeWithRequirement();
        $oldFacility = $employee->facility;
        $oldManager = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $oldFacility->id]);
        app(RecordComplianceAssessment::class)->handle($employee, 'assignment');
        $this->actingAs($oldManager)->get("/employees/{$employee->id}/compliance-history")->assertOk()->assertSee($oldFacility->name);
        $newFacility = Facility::factory()->create();
        $employee->forceFill(['facility_id' => $newFacility->id])->save();
        $this->get("/employees/{$employee->id}/compliance-history")->assertNotFound();
        $newManager = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $newFacility->id]);
        $this->actingAs($newManager)->get("/employees/{$employee->id}/compliance-history")->assertOk()->assertSee($newFacility->name)->assertSee($oldFacility->name);
        $this->actingAs(User::factory()->for(Facility::factory())->for(JobRole::factory())->create())->get("/employees/{$employee->id}/compliance-history")->assertNotFound();
    }

    public function test_read_reconciles_live_expiry_even_when_scheduler_has_not_run(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Manila'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'expires_at' => '2026-11-10']);
        $this->actingAs($employee)->get("/employees/{$employee->id}")->assertOk()->assertSee('Eligible');
        $this->travelTo(CarbonImmutable::parse('2026-11-11 00:00', 'Asia/Manila'));
        $this->get("/employees/{$employee->id}")->assertOk()->assertSee('Not eligible')->assertSee('Expired');
        $this->assertDatabaseCount('compliance_assessments', 2);
    }

    public function test_failed_assessment_history_rolls_back_review_status_events_and_award(): void
    {
        Storage::fake('local');
        [$employee, $qualification] = $this->employeeWithRequirement();
        $document = CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id]);
        Storage::disk('local')->put($document->private_path, 'synthetic evidence');
        Event::listen('eloquent.creating: '.ComplianceAssessment::class, function (): void {
            throw new RuntimeException('History unavailable');
        });
        $officer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        try {
            app(ReviewCredential::class)->handle($officer, $document, CredentialStatus::Approved, null);
            $this->fail('The history error must abort the review.');
        } catch (RuntimeException $exception) {
            $this->assertSame('History unavailable', $exception->getMessage());
        }
        $this->assertSame(CredentialStatus::Pending, $document->fresh()->status);
        $this->assertDatabaseCount('credential_events', 0);
        $this->assertDatabaseCount('employee_qualification', 0);
        $this->assertDatabaseCount('compliance_assessments', 0);
        Storage::disk('local')->assertExists($document->private_path);
    }

    /** @return array{User, Qualification} */
    private function employeeWithRequirement(): array
    {
        $employee = User::factory()->for(Facility::factory())->for(JobRole::factory())->create();
        $qualification = Qualification::factory()->create();
        $employee->jobRole->qualifications()->attach($qualification);

        return [$employee, $qualification];
    }
}
