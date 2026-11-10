<?php

namespace Tests\Feature;

use App\Actions\RecordComplianceAssessment;
use App\Enums\CredentialStatus;
use App\Enums\TrainingStatus;
use App\Enums\UserRole;
use App\Models\CredentialDocument;
use App\Models\EmployeeTraining;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_separates_assessable_population_and_counts_optional_pending_evidence(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Manila'));
        $facility = Facility::factory()->create();
        $job = JobRole::factory()->create();
        $qualification = Qualification::factory()->create();
        $job->qualifications()->attach($qualification);
        $eligible = User::factory()->create(['facility_id' => $facility->id, 'job_role_id' => $job->id]);
        $ineligible = User::factory()->create(['facility_id' => $facility->id, 'job_role_id' => $job->id]);
        User::factory()->create();
        User::factory()->create(['facility_id' => $facility->id, 'job_role_id' => $job->id, 'is_active' => false]);
        CredentialDocument::factory()->create(['employee_id' => $eligible->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'expires_at' => '2026-12-10']);
        CredentialDocument::factory()->create(['employee_id' => $eligible->id]);
        EmployeeTraining::factory()->create(['employee_id' => $eligible->id]);
        EmployeeTraining::factory()->create(['employee_id' => $ineligible->id, 'status' => TrainingStatus::Completed, 'completed_at' => '2026-11-10']);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertViewHas('totals', fn (array $totals): bool => $totals === [
            'employees' => 4, 'assessable' => 2, 'eligible' => 1, 'not_eligible' => 1, 'not_assessable' => 2,
            'eligible_rate' => 50.0, 'missing' => 1, 'expired' => 0, 'expiring_soon' => 1,
            'training_incomplete' => 0, 'pending_documents' => 1, 'enrolled' => 1, 'completed' => 1,
        ])->assertSee('1 eligible / 2 assessable staff');
        $this->assertDatabaseCount('compliance_assessments', 0);
    }

    public function test_empty_dashboard_has_no_percentage_instead_of_full_compliance(): void
    {
        $manager = User::factory()->create(['role' => UserRole::FacilityManager]);
        $this->actingAs($manager)->get('/dashboard')->assertOk()
            ->assertViewHas('totals', fn (array $totals): bool => $totals['eligible_rate'] === null && $totals['employees'] === 0);
    }

    public function test_dashboard_and_matrix_respect_facility_region_and_own_staff_scope(): void
    {
        $facility = Facility::factory()->create();
        $siblingFacility = Facility::factory()->create(['region_id' => $facility->region_id]);
        $job = JobRole::factory()->create();
        $qualification = Qualification::factory()->create();
        $job->qualifications()->attach($qualification);
        $own = User::factory()->create(['name' => 'Own Staff', 'facility_id' => $facility->id, 'job_role_id' => $job->id]);
        $regional = User::factory()->create(['name' => 'Regional Staff', 'facility_id' => $siblingFacility->id, 'job_role_id' => $job->id]);
        $outside = User::factory()->for(Facility::factory())->for(JobRole::factory())->create(['name' => 'Outside Staff']);
        $manager = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $facility->id]);
        $director = User::factory()->create(['role' => UserRole::RegionalDirector, 'region_id' => $facility->region_id]);
        foreach ([[$manager, 1], [$director, 2], [$own, 1]] as [$viewer, $expected]) {
            $this->actingAs($viewer)->get('/dashboard')->assertOk()->assertViewHas('totals', fn (array $totals): bool => $totals['employees'] === $expected);
            $response = $this->get('/qualification-matrix')->assertOk()->assertSee('Own Staff')->assertDontSee('Outside Staff');
            if ($expected === 2) {
                $response->assertSee('Regional Staff');
            } else {
                $response->assertDontSee('Regional Staff');
            }
        }
        foreach ([UserRole::Admin, UserRole::ComplianceOfficer] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/qualification-matrix')->assertOk()->assertSee('Outside Staff');
        }
    }

    public function test_matrix_marks_unrequired_cells_and_escapes_employee_and_qualification_names(): void
    {
        $facility = Facility::factory()->create();
        $job = JobRole::factory()->create();
        $qualification = Qualification::factory()->create(['name' => '<script>qualification()</script>']);
        $job->qualifications()->attach($qualification);
        $employee = User::factory()->create(['name' => '<script>employee()</script>', 'facility_id' => $facility->id, 'job_role_id' => $job->id]);
        User::factory()->for(JobRole::factory())->create(['facility_id' => $facility->id]);
        $manager = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $facility->id]);
        $this->actingAs($manager)->get('/qualification-matrix')->assertOk()->assertSee('N/A')
            ->assertSee($employee->name)->assertSee($qualification->name)
            ->assertDontSee($employee->name, false)->assertDontSee($qualification->name, false);
    }

    public function test_manager_summaries_and_history_do_not_expose_files_or_private_notes(): void
    {
        $facility = Facility::factory()->create();
        $employee = User::factory()->for(JobRole::factory())->create(['facility_id' => $facility->id]);
        $qualification = Qualification::factory()->create();
        $employee->jobRole->qualifications()->attach($qualification);
        $document = CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id]);
        $document->events()->create(['actor_id' => $employee->id, 'type' => 'submitted', 'note' => 'CONFIDENTIAL EVIDENCE NOTE']);
        $manager = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $facility->id]);
        app(RecordComplianceAssessment::class)->handle($employee, 'submission');
        foreach (['/dashboard', '/qualification-matrix', "/employees/{$employee->id}", "/employees/{$employee->id}/compliance-history"] as $url) {
            $this->actingAs($manager)->get($url)->assertOk()->assertDontSee($document->private_path)
                ->assertDontSee('CONFIDENTIAL EVIDENCE NOTE')->assertDontSee("/credentials/{$document->id}/download");
        }
        $this->get("/credentials/{$document->id}/download")->assertForbidden();
    }

    public function test_new_compliance_pages_require_authentication_assignment_and_browser_role(): void
    {
        $employee = User::factory()->for(Facility::factory())->for(JobRole::factory())->create();
        foreach (['/qualification-matrix', "/employees/{$employee->id}/compliance-history"] as $url) {
            $this->get($url)->assertRedirect('/login');
            $this->actingAs(User::factory()->create())->get($url)->assertRedirect('/awaiting-assignment');
            $this->actingAs(User::factory()->create(['role' => UserRole::Auditor]))->get($url)->assertRedirect('/login');
            $this->app['auth']->forgetGuards();
        }
        $inactive = User::factory()->create(['role' => UserRole::FacilityManager, 'is_active' => false]);
        $this->actingAs($inactive)->get('/qualification-matrix')->assertRedirect('/login');
    }
}
