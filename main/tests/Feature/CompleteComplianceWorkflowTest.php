<?php

namespace Tests\Feature;

use App\Actions\AssessEmployeeCompliance;
use App\Enums\UserRole;
use App\Models\Audit;
use App\Models\AuditFinding;
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
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class CompleteComplianceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_staff_progresses_through_training_review_audit_reporting_and_regional_verification(): void
    {
        Storage::fake('local');
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        $facility = Facility::factory()->create();
        $job = JobRole::factory()->create();
        $qualification = Qualification::factory()->create(['requires_training' => true, 'requires_expiry' => true]);
        $job->qualifications()->attach($qualification);
        $course = TrainingCourse::factory()->published()->create(['qualification_id' => $qualification->id]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $officer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $manager = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $facility->id]);
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => $facility->region_id]);

        $this->post('/register', ['name' => 'New employee', 'email' => 'workflow@example.test', 'password' => 'WorkflowPassword123', 'password_confirmation' => 'WorkflowPassword123'])->assertSessionHasNoErrors()->assertRedirect('/dashboard');
        $staff = User::where('email', 'workflow@example.test')->firstOrFail();
        $this->get('/dashboard')->assertRedirect('/awaiting-assignment');
        $this->post('/training-courses/'.$course->id.'/enrollments')->assertForbidden();
        $this->actingAs($admin)->patch('/admin/users/'.$staff->id.'/assignment', ['role' => 'staff', 'is_active' => true, 'facility_id' => $facility->id, 'job_role_id' => $job->id])->assertSessionHasNoErrors()->assertRedirect();
        $staff->refresh();
        $this->actingAs($staff)->post('/training-courses/'.$course->id.'/enrollments')->assertSessionHasNoErrors()->assertRedirect();
        $enrollment = EmployeeTraining::where('employee_id', $staff->id)->sole();
        $payload = ['qualification_id' => $qualification->id, 'employee_training_id' => $enrollment->id, 'issued_at' => '2026-11-11', 'expires_at' => '2026-11-26'];
        $this->post('/credentials', [...$payload, 'file' => $this->evidence()])->assertSessionHasNoErrors()->assertRedirect();
        $document = CredentialDocument::where('employee_id', $staff->id)->sole();
        $this->actingAs($officer)->post('/credentials/'.$document->id.'/reviews', ['decision' => 'approved'])->assertSessionHasErrors('decision');
        $this->patch('/training-enrollments/'.$enrollment->id.'/completion', ['completed_at' => '2026-11-11', 'feedback' => 'Practical checks passed.'])->assertSessionHasNoErrors()->assertRedirect();
        $this->post('/credentials/'.$document->id.'/reviews', ['decision' => 'approved'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('eligible', $this->app->make(AssessEmployeeCompliance::class)->handle($staff->fresh())['status']);
        $this->assertDatabaseHas('employee_qualification', ['employee_id' => $staff->id, 'qualification_id' => $qualification->id, 'credential_document_id' => $document->id]);
        $this->assertSame(2, $document->events()->count());

        $this->post('/audits', ['title' => 'Readiness verification', 'region_id' => $facility->region_id, 'facility_id' => $facility->id])->assertSessionHasNoErrors()->assertRedirect();
        $audit = Audit::sole();
        $originalSnapshot = $audit->snapshot;
        $this->assertSame('eligible', $originalSnapshot['employees'][0]['status']);
        $this->post('/audits/'.$audit->id.'/findings', ['title' => 'Plan the next renewal', 'description' => 'Arrange refresher training before expiry.', 'facility_id' => $facility->id, 'assignee_id' => $manager->id, 'due_on' => '2026-11-20'])->assertSessionHasNoErrors()->assertRedirect();
        $finding = AuditFinding::sole();
        $this->actingAs($manager)->post('/findings/'.$finding->id.'/updates', ['status' => 'pending_verification', 'note' => 'Refresher appointment arranged.'])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($officer)->post('/findings/'.$finding->id.'/updates', ['status' => 'resolved', 'note' => 'Appointment verified.'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('audit_findings', ['id' => $finding->id, 'status' => 'resolved']);
        $this->assertSame(2, $finding->updates()->count());

        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 2);
        $this->actingAs($staff)->get('/notifications')->assertOk()->assertSee($qualification->name);
        $this->actingAs($manager)->get('/reports/download?type=readiness')->assertOk()->assertDownload('checkpoint-readiness-2026-11-11.csv');
        $this->assertStringContainsString($staff->name, $this->get('/reports/download?type=readiness')->streamedContent());
        $this->actingAs($staff)->post('/credentials', [...$payload, 'expires_at' => '2027-11-11', 'file' => $this->evidence()])->assertSessionHasNoErrors()->assertRedirect();
        $pending = CredentialDocument::where('employee_id', $staff->id)->latest('id')->firstOrFail();
        $this->assertSame('eligible', $this->app->make(AssessEmployeeCompliance::class)->handle($staff->fresh())['status']);

        $issued = $this->actingAs($admin)->post('/admin/auditor-tokens', ['auditor_id' => $auditor->id, 'name' => 'Workflow verification', 'expires_on' => '2026-12-11']);
        $issued->assertOk()->assertSessionHasNoErrors()->assertViewIs('admin.auditor-tokens.created');
        $bearer = ['Authorization' => 'Bearer '.$issued->viewData('plainTextToken')];
        $token = PersonalAccessToken::sole();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/verifications/'.$document->reference, $bearer)->assertOk()->assertJsonPath('data.validity', 'valid');
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/verifications/'.$pending->reference, $bearer)->assertOk()->assertJsonPath('data.validity', 'not_valid')->assertJsonPath('data.review_status', 'pending');
        $this->actingAs($admin, 'web')->delete('/admin/auditor-tokens/'.$token->id)->assertRedirect();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/verifications/'.$document->reference, $bearer)->assertUnauthorized();

        $this->travelTo(CarbonImmutable::parse('2026-11-27 12:00', 'Asia/Manila'));
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->assertSame('not_eligible', $this->app->make(AssessEmployeeCompliance::class)->handle($staff->fresh())['status']);
        $this->assertDatabaseCount('notifications', 4);
        $this->assertSame($originalSnapshot, $audit->fresh()->snapshot);
        $this->assertGreaterThan(1, ComplianceAssessment::where('employee_id', $staff->id)->count());
        $this->assertSame(2, $document->events()->count());
    }

    private function evidence(): UploadedFile
    {
        return new UploadedFile(database_path('fixtures/synthetic-evidence.png'), 'evidence.png', 'image/png', null, true);
    }
}
