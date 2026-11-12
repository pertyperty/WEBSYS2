<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CredentialDocument;
use App\Models\EmployeeTraining;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\Region;
use App\Models\TrainingCourse;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ComplianceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_summary_uses_live_training_and_evidence_without_exposing_personal_data(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-12 12:00', 'Asia/Manila'));
        $facility = Facility::factory()->create();
        $job = JobRole::factory()->create();
        $qualification = Qualification::factory()->requiringTraining()->create();
        $job->qualifications()->attach($qualification);
        $employee = User::factory()->assignedTo($facility, $job)->create();
        $officer = User::factory()->role(UserRole::ComplianceOfficer)->create();
        $course = TrainingCourse::factory()->for($qualification)->published()->create();
        $enrollment = EmployeeTraining::factory()->for($employee, 'employee')->for($course, 'trainingCourse')->completed($officer)->create();
        $document = CredentialDocument::factory()->for($employee, 'employee')->for($qualification)->for($enrollment, 'employeeTraining')
            ->approved()->expiringOn(CarbonImmutable::parse('2026-11-12'))->create();
        CredentialDocument::factory()->for($employee, 'employee')->for($qualification)->for($enrollment, 'employeeTraining')->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();
        $token = $this->tokenFor($auditor);

        $response = $this->requestSummary('employees/'.$employee->verification_reference, $token);

        $response->assertOk()->assertExactJson(['data' => [
            'employee_reference' => $employee->verification_reference,
            'status' => 'eligible', 'required_count' => 1, 'satisfied_count' => 1,
            'qualification_percentage' => 100, 'evaluated_on' => '2026-11-12',
            'qualifications' => [[
                'qualification' => $qualification->name, 'domain' => $qualification->safetyDomain->name,
                'state' => 'satisfied', 'training_completed' => true,
                'expires_on' => '2026-11-12', 'expiring_soon' => true,
            ]],
        ]]);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        foreach ([$employee->name, $employee->email, $document->private_path, $employee->password] as $privateValue) {
            $this->assertStringNotContainsString($privateValue, $response->getContent());
        }
        $this->travelTo(CarbonImmutable::parse('2026-11-13 00:00', 'Asia/Manila'));
        $this->requestSummary('employees/'.$employee->verification_reference, $token)->assertOk()
            ->assertJsonPath('data.status', 'not_eligible')->assertJsonPath('data.satisfied_count', 0);
        $this->assertDatabaseCount('compliance_assessments', 0);
    }

    public function test_facility_rate_counts_only_assessable_active_staff_in_that_facility(): void
    {
        $this->freezeTime();
        $facility = Facility::factory()->create();
        $job = JobRole::factory()->create();
        $qualification = Qualification::factory()->create();
        $job->qualifications()->attach($qualification);
        $eligible = User::factory()->assignedTo($facility, $job)->create();
        CredentialDocument::factory()->for($eligible, 'employee')->for($qualification)->approved()->create();
        User::factory()->assignedTo($facility, $job)->create();
        User::factory()->assignedTo($facility)->create();
        User::factory()->assignedTo($facility, $job)->inactive()->create();
        User::factory()->role(UserRole::FacilityManager)->assignedTo($facility)->create();
        User::factory()->assignedTo(Facility::factory()->for($facility->region)->create(), $job)->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();

        $response = $this->requestSummary('facilities/'.$facility->id, $this->tokenFor($auditor));

        $response->assertOk()->assertExactJson(['data' => [
            'facility_id' => $facility->id, 'active_staff_count' => 3, 'assessable_count' => 2,
            'eligible_count' => 1, 'not_eligible_count' => 1, 'not_assessable_count' => 1,
            'compliance_rate' => 50, 'evaluated_on' => now('Asia/Manila')->toDateString(),
        ]]);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertDatabaseCount('compliance_assessments', 0);
    }

    public function test_no_configured_requirements_produce_null_percentages_instead_of_false_compliance(): void
    {
        $this->freezeTime();
        $facility = Facility::factory()->create();
        $employee = User::factory()->assignedTo($facility, JobRole::factory()->create())->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();
        $token = $this->tokenFor($auditor);

        $this->requestSummary('employees/'.$employee->verification_reference, $token)->assertOk()
            ->assertJsonPath('data.status', 'not_assessable')->assertJsonPath('data.qualification_percentage', null);
        $this->requestSummary('facilities/'.$facility->id, $token)->assertOk()
            ->assertJsonPath('data.active_staff_count', 1)->assertJsonPath('data.assessable_count', 0)
            ->assertJsonPath('data.not_assessable_count', 1)->assertJsonPath('data.compliance_rate', null);
    }

    public function test_empty_facility_has_a_null_compliance_rate(): void
    {
        $facility = Facility::factory()->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();

        $this->requestSummary('facilities/'.$facility->id, $this->tokenFor($auditor))->assertOk()
            ->assertJsonPath('data.active_staff_count', 0)->assertJsonPath('data.compliance_rate', null);
    }

    #[TestWith(['employees'])]
    #[TestWith(['facilities'])]
    public function test_both_summaries_return_401_for_missing_invalid_expired_revoked_or_browser_only_authentication(string $kind): void
    {
        $this->freezeTime();
        $facility = Facility::factory()->create();
        $employee = User::factory()->assignedTo($facility)->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();
        $path = $kind.'/'.($kind === 'employees' ? $employee->verification_reference : $facility->id);
        $this->requestSummary($path)->assertUnauthorized();
        $this->requestSummary($path, 'invalid-token')->assertUnauthorized();
        $token = $this->tokenFor($auditor);
        $auditor->tokens()->update(['expires_at' => now()->subSecond()]);
        $this->requestSummary($path, $token)->assertUnauthorized();
        $auditor->tokens()->delete();
        $this->requestSummary($path, $token)->assertUnauthorized();
        $this->actingAs($auditor)->getJson('/api/v1/'.$path.'/compliance')->assertUnauthorized();
    }

    #[TestWith(['admin'])]
    #[TestWith(['compliance_officer'])]
    #[TestWith(['regional_director'])]
    #[TestWith(['facility_manager'])]
    #[TestWith(['staff'])]
    public function test_both_summaries_return_403_for_other_roles_even_with_real_compliance_tokens(string $role): void
    {
        $facility = Facility::factory()->create();
        $employee = User::factory()->assignedTo($facility)->create();
        $caller = User::factory()->role(UserRole::from($role))->for($facility->region)->create();
        $token = $this->tokenFor($caller);

        $this->requestSummary('employees/'.$employee->verification_reference, $token)->assertForbidden();
        $this->requestSummary('facilities/'.$facility->id, $token)->assertForbidden();
    }

    #[TestWith([[]])]
    #[TestWith([['*']])]
    #[TestWith([['verification:read']])]
    public function test_both_summaries_return_403_without_the_explicit_compliance_ability(array $abilities): void
    {
        $facility = Facility::factory()->create();
        $employee = User::factory()->assignedTo($facility)->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();
        $token = $this->tokenFor($auditor, $abilities);

        $this->requestSummary('employees/'.$employee->verification_reference, $token)->assertForbidden();
        $this->requestSummary('facilities/'.$facility->id, $token)->assertForbidden();
    }

    public function test_summaries_return_404_for_unknown_or_other_region_identifiers(): void
    {
        $facility = Facility::factory()->create();
        $employee = User::factory()->assignedTo($facility)->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for(Region::factory())->create();
        $token = $this->tokenFor($auditor);

        $this->requestSummary('employees/'.$employee->verification_reference, $token)->assertNotFound();
        $this->requestSummary('employees/not-a-reference', $token)->assertNotFound();
        $this->requestSummary('employees/'.$employee->id, $token)->assertNotFound();
        $this->requestSummary('facilities/'.$facility->id, $token)->assertNotFound();
        $this->requestSummary('facilities/99999999', $token)->assertNotFound();
    }

    public function test_current_facility_assignment_controls_employee_scope_after_a_transfer(): void
    {
        $facility = Facility::factory()->create();
        $employee = User::factory()->assignedTo($facility)->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();
        $token = $this->tokenFor($auditor);
        $this->requestSummary('employees/'.$employee->verification_reference, $token)->assertOk();
        $employee->forceFill(['facility_id' => Facility::factory()->create()->id])->save();

        $this->requestSummary('employees/'.$employee->verification_reference, $token)->assertNotFound();
    }

    public function test_inactive_staff_and_nonstaff_are_hidden_from_employee_lookup(): void
    {
        $facility = Facility::factory()->create();
        $inactive = User::factory()->assignedTo($facility)->inactive()->create();
        $manager = User::factory()->role(UserRole::FacilityManager)->assignedTo($facility)->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();
        $token = $this->tokenFor($auditor);

        $this->requestSummary('employees/'.$inactive->verification_reference, $token)->assertNotFound();
        $this->requestSummary('employees/'.$manager->verification_reference, $token)->assertNotFound();
    }

    public function test_deactivated_facilities_are_hidden_from_both_summaries(): void
    {
        $facility = Facility::factory()->create(['is_active' => false]);
        $employee = User::factory()->assignedTo($facility)->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();
        $token = $this->tokenFor($auditor);

        $this->requestSummary('employees/'.$employee->verification_reference, $token)->assertNotFound();
        $this->requestSummary('facilities/'.$facility->id, $token)->assertNotFound();
    }

    #[TestWith(['account'])]
    #[TestWith(['region'])]
    #[TestWith(['token_region'])]
    public function test_deactivation_or_token_region_drift_returns_403_on_both_summaries(string $change): void
    {
        $facility = Facility::factory()->create();
        $employee = User::factory()->assignedTo($facility)->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();
        $token = $this->tokenFor($auditor);
        match ($change) {
            'account' => $auditor->forceFill(['is_active' => false])->save(),
            'region' => $facility->region->update(['is_active' => false]),
            'token_region' => $auditor->tokens()->update(['region_id' => Region::factory()->create()->id]),
        };

        $this->requestSummary('employees/'.$employee->verification_reference, $token)->assertForbidden();
        $this->requestSummary('facilities/'.$facility->id, $token)->assertForbidden();
    }

    public function test_nonexpiring_or_exactly_expired_tokens_return_401_on_both_summaries(): void
    {
        $this->freezeTime();
        $facility = Facility::factory()->create();
        $employee = User::factory()->assignedTo($facility)->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();
        $token = $auditor->createToken('Invalid lifetime', ['compliance:read']);
        $token->accessToken->forceFill(['region_id' => $auditor->region_id])->save();

        $this->requestSummary('employees/'.$employee->verification_reference, $token->plainTextToken)->assertUnauthorized();
        $this->requestSummary('facilities/'.$facility->id, $token->plainTextToken)->assertUnauthorized();
        $token->accessToken->forceFill(['expires_at' => now()])->save();
        $this->requestSummary('employees/'.$employee->verification_reference, $token->plainTextToken)->assertUnauthorized();
        $this->requestSummary('facilities/'.$facility->id, $token->plainTextToken)->assertUnauthorized();
    }

    public function test_account_rate_limit_is_shared_across_all_three_api_endpoints_and_tokens(): void
    {
        $this->freezeTime();
        $facility = Facility::factory()->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();
        $token = $this->tokenFor($auditor, ['verification:read', 'compliance:read']);
        for ($request = 0; $request < 59; $request++) {
            $this->requestSummary('employees/unknown-reference', $token)->assertNotFound();
        }
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/verifications/unknown-reference', ['Authorization' => 'Bearer '.$token])->assertNotFound();

        $this->requestSummary('facilities/'.$facility->id, $this->tokenFor($auditor))->assertTooManyRequests()->assertHeader('Retry-After');
    }

    public function test_facility_summary_counts_every_chunk_without_including_other_facilities(): void
    {
        $facility = Facility::factory()->create();
        User::factory()->count(101)->assignedTo($facility)->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();

        $this->requestSummary('facilities/'.$facility->id, $this->tokenFor($auditor))->assertOk()
            ->assertJsonPath('data.active_staff_count', 101)->assertJsonPath('data.not_assessable_count', 101)
            ->assertJsonPath('data.compliance_rate', null);
    }

    /** @param list<string> $abilities */
    private function tokenFor(User $user, array $abilities = ['compliance:read']): string
    {
        $token = $user->createToken('Compliance client', $abilities, now()->addDays(2));
        $token->accessToken->forceFill(['region_id' => $user->region_id])->save();

        return $token->plainTextToken;
    }

    private function requestSummary(string $path, ?string $token = null): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->getJson('/api/v1/'.$path.'/compliance', $token ? ['Authorization' => 'Bearer '.$token] : []);
    }
}
