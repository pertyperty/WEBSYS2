<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Enums\UserRole;
use App\Models\CredentialDocument;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CredentialAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function permissions(): array
    {
        return [
            'admin' => [UserRole::Admin, true, false, false],
            'officer' => [UserRole::ComplianceOfficer, true, false, true],
            'staff' => [UserRole::Staff, true, true, false],
            'manager' => [UserRole::FacilityManager, false, false, false],
            'director' => [UserRole::RegionalDirector, false, false, false],
            'auditor' => [UserRole::Auditor, false, false, false],
        ];
    }

    #[DataProvider('permissions')]
    public function test_credential_gates_enforce_privacy_and_separate_submission_from_review(UserRole $role, bool $view, bool $submit, bool $review): void
    {
        $actor = User::factory()->for(Facility::factory())->for(JobRole::factory())->create(['role' => $role]);
        $gate = Gate::forUser($actor);
        $this->assertSame($view, $gate->allows('view-credentials'));
        $this->assertSame($submit, $gate->allows('submit-credentials'));
        $this->assertSame($review, $gate->allows('review-credentials'));
        $actor->is_active = false;
        $this->assertFalse($gate->allows('view-credentials'));
        $this->assertFalse($gate->allows('submit-credentials'));
        $this->assertFalse($gate->allows('review-credentials'));
    }

    public function test_staff_cannot_read_or_download_another_employees_evidence(): void
    {
        Storage::fake('local');
        $own = CredentialDocument::factory()->create();
        $outside = CredentialDocument::factory()->create();
        $this->actingAs($own->employee)->get('/credentials')->assertOk()->assertSee($own->reference)->assertDontSee($outside->reference);
        $this->get("/credentials/{$own->id}")->assertOk()->assertDontSee($own->private_path);
        $this->get("/credentials/{$outside->id}")->assertNotFound();
        $this->get("/credentials/{$outside->id}/download")->assertNotFound();
        $this->get('/credentials/999999')->assertNotFound();
    }

    public function test_managers_and_directors_cannot_read_private_details_even_inside_their_scope(): void
    {
        $document = CredentialDocument::factory()->create();
        $manager = User::factory()->for($document->employee->facility)->create(['role' => UserRole::FacilityManager]);
        $director = User::factory()->for($document->employee->facility->region)->create(['role' => UserRole::RegionalDirector]);
        foreach ([$manager, $director] as $viewer) {
            $this->actingAs($viewer)->get('/credentials')->assertForbidden();
            $this->get("/credentials/{$document->id}")->assertForbidden();
            $this->get("/credentials/{$document->id}/download")->assertForbidden();
        }
    }

    public static function oversightRoles(): array
    {
        return ['admin' => [UserRole::Admin], 'officer' => [UserRole::ComplianceOfficer]];
    }

    #[DataProvider('oversightRoles')]
    public function test_oversight_downloads_are_authorized_attachments_with_safe_headers(UserRole $role): void
    {
        Storage::fake('local');
        $document = CredentialDocument::factory()->create();
        $bytes = "%PDF-1.4\n%%EOF\n";
        Storage::disk('local')->put($document->private_path, $bytes);
        $this->actingAs(User::factory()->create(['role' => $role]))->get("/credentials/{$document->id}")->assertOk()->assertSee('Download private evidence')->assertDontSee($document->private_path);
        $response = $this->get("/credentials/{$document->id}/download?path=public/other.pdf")
            ->assertOk()->assertDownload('credential-'.$document->reference.'.pdf')->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')->assertStreamedContent($bytes);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('sandbox', $response->headers->get('Content-Security-Policy'));
    }

    public function test_guests_unassigned_staff_and_auditors_cannot_bypass_credential_access(): void
    {
        $document = CredentialDocument::factory()->create();
        $this->get('/credentials')->assertRedirect('/login');
        $this->get("/credentials/{$document->id}/download")->assertRedirect('/login');
        $this->post('/credentials', [])->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/credentials/create')->assertRedirect('/awaiting-assignment');
        $this->post('/credentials', [])->assertRedirect('/awaiting-assignment');
        $this->actingAs(User::factory()->create(['role' => UserRole::Auditor]))->get('/credentials')->assertRedirect('/login');
        $this->assertDatabaseCount('credential_documents', 1);
    }

    public function test_missing_private_files_are_explained_without_disclosing_storage_paths(): void
    {
        Storage::fake('local');
        $document = CredentialDocument::factory()->create();
        $this->actingAs($document->employee)->get("/credentials/{$document->id}")->assertOk()
            ->assertSee('The stored file is unavailable. Contact an administrator.')->assertDontSee('Download private evidence')->assertDontSee($document->private_path);
        $this->get("/credentials/{$document->id}/download")->assertNotFound()->assertDontSee($document->private_path);
        $this->get('/storage/'.$document->private_path)->assertNotFound();
    }

    public function test_status_filter_and_pagination_do_not_widen_employee_scope(): void
    {
        $approved = CredentialDocument::factory()->create(['status' => CredentialStatus::Approved]);
        CredentialDocument::factory()->count(15)->for($approved->employee, 'employee')->create();
        $outside = CredentialDocument::factory()->create(['status' => CredentialStatus::Approved]);
        $this->actingAs($approved->employee)->get('/credentials?status=approved')->assertOk()->assertSee($approved->reference)->assertDontSee($outside->reference);
        $response = $this->get('/credentials')->assertOk();
        $this->assertCount(15, $response->viewData('documents')->items());
        $this->assertSame(16, $response->viewData('documents')->total());
        $this->get('/credentials?status=unknown')->assertSessionHasErrors('status');
    }

    public function test_submission_retains_its_original_assignment_context_after_transfer(): void
    {
        Storage::fake('local');
        $document = CredentialDocument::factory()->create();
        $oldFacility = $document->facility;
        $employee = $document->employee;
        $employee->forceFill(['facility_id' => Facility::factory()->create()->id, 'job_role_id' => JobRole::factory()->create()->id])->save();
        $this->actingAs($employee)->get("/credentials/{$document->id}")->assertOk()->assertSee($oldFacility->name);
        $this->assertSame($oldFacility->id, $document->fresh()->facility_id);
    }
}
