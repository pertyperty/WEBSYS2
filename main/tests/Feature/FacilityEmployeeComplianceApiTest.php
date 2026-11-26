<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CredentialDocument;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\Region;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class FacilityEmployeeComplianceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_facility_employee_list_returns_private_compliance_summaries_for_only_active_staff(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-12 12:00', 'Asia/Manila'));
        $facility = Facility::factory()->create();
        $job = JobRole::factory()->create();
        $qualification = Qualification::factory()->create();
        $job->qualifications()->attach($qualification);
        $eligible = User::factory()->assignedTo($facility, $job)->create();
        CredentialDocument::factory()->for($eligible, 'employee')->for($qualification)->approved()->create();
        $ineligible = User::factory()->assignedTo($facility, $job)->create();
        $inactive = User::factory()->assignedTo($facility, $job)->inactive()->create();
        $manager = User::factory()->role(UserRole::FacilityManager)->assignedTo($facility)->create();
        $otherFacilityEmployee = User::factory()->assignedTo(Facility::factory()->for($facility->region)->create(), $job)->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();

        $response = $this->requestEmployees($facility->id, $this->tokenFor($auditor));

        $response->assertOk()->assertJsonPath('data.0.employee_reference', $eligible->verification_reference)
            ->assertJsonPath('data.0.status', 'eligible')
            ->assertJsonPath('data.0.duty_status', 'Cleared for duty')
            ->assertJsonPath('data.0.required_count', 1)
            ->assertJsonPath('data.0.satisfied_count', 1)
            ->assertJsonPath('data.0.qualification_percentage', 100)
            ->assertJsonPath('data.0.evaluated_on', '2026-11-12')
            ->assertJsonPath('data.1.employee_reference', $ineligible->verification_reference)
            ->assertJsonPath('data.1.status', 'not_eligible')
            ->assertJsonPath('data.1.satisfied_count', 0)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [[
                'employee_reference', 'status', 'duty_status', 'required_count',
                'satisfied_count', 'qualification_percentage', 'evaluated_on',
            ]]]);
        $this->assertSame([
            'employee_reference', 'status', 'duty_status', 'required_count',
            'satisfied_count', 'qualification_percentage', 'evaluated_on',
        ], array_keys($response->json('data.0')));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        foreach ([$eligible, $ineligible, $inactive, $manager, $otherFacilityEmployee] as $employee) {
            $this->assertStringNotContainsString($employee->name, $response->getContent());
            $this->assertStringNotContainsString($employee->email, $response->getContent());
        }
        $this->assertDatabaseCount('compliance_assessments', 0);
    }

    public function test_employee_list_paginates_by_id_and_limits_page_size_to_100(): void
    {
        $facility = Facility::factory()->create();
        $employees = User::factory()->count(101)->assignedTo($facility)->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();
        $token = $this->tokenFor($auditor);

        $defaultPage = $this->requestEmployees($facility->id, $token);
        $defaultPage->assertOk()->assertJsonPath('meta.per_page', 25)
            ->assertJsonPath('meta.total', 101)->assertJsonCount(25, 'data');
        $this->assertSame(
            $employees->take(25)->pluck('verification_reference')->all(),
            array_column($defaultPage->json('data'), 'employee_reference'),
        );

        $maximumPage = $this->requestEmployees($facility->id, $token, ['per_page' => 100]);
        $maximumPage->assertOk()->assertJsonPath('meta.per_page', 100)
            ->assertJsonCount(100, 'data');
        $this->requestEmployees($facility->id, $token, ['per_page' => 101])->assertUnprocessable();
        $this->requestEmployees($facility->id, $token, ['per_page' => 'invalid'])->assertUnprocessable();
    }

    public function test_employee_list_returns_404_for_unknown_and_other_region_facilities(): void
    {
        $facility = Facility::factory()->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for(Region::factory())->create();
        $token = $this->tokenFor($auditor);

        $this->requestEmployees(99999999, $token)->assertNotFound();
        $this->requestEmployees($facility->id, $token)->assertNotFound();
    }

    public function test_employee_list_returns_401_without_or_with_an_invalid_token(): void
    {
        $facility = Facility::factory()->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();

        $this->requestEmployees($facility->id)->assertUnauthorized();
        $this->requestEmployees($facility->id, 'invalid-token')->assertUnauthorized();
        $this->requestEmployees($facility->id, $this->tokenFor($auditor))->assertOk();
    }

    #[TestWith(['admin'])]
    #[TestWith(['compliance_officer'])]
    #[TestWith(['regional_director'])]
    #[TestWith(['facility_manager'])]
    #[TestWith(['staff'])]
    public function test_employee_list_returns_403_for_non_auditor_roles_even_with_compliance_tokens(string $role): void
    {
        $facility = Facility::factory()->create();
        $caller = User::factory()->role(UserRole::from($role))->for($facility->region)->create();

        $this->requestEmployees($facility->id, $this->tokenFor($caller))->assertForbidden();
    }

    #[TestWith([[]])]
    #[TestWith([['*']])]
    #[TestWith([['verification:read']])]
    public function test_employee_list_requires_the_explicit_compliance_read_ability(array $abilities): void
    {
        $facility = Facility::factory()->create();
        $auditor = User::factory()->role(UserRole::Auditor)->for($facility->region)->create();

        $this->requestEmployees($facility->id, $this->tokenFor($auditor, $abilities))->assertForbidden();
    }

    /** @param list<string> $abilities */
    private function tokenFor(User $user, array $abilities = ['compliance:read']): string
    {
        $token = $user->createToken('Compliance client', $abilities, now()->addDays(2));
        $token->accessToken->forceFill(['region_id' => $user->region_id])->save();

        return $token->plainTextToken;
    }

    /** @param array<string, int|string> $query */
    private function requestEmployees(int $facility, ?string $token = null, array $query = []): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->getJson('/api/v1/facilities/'.$facility.'/employees'.($query ? '?'.http_build_query($query) : ''), $token
            ? ['Authorization' => 'Bearer '.$token] : []);
    }
}
