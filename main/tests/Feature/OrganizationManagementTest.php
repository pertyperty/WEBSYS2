<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\Region;
use App\Models\SafetyDomain;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    public static function catalogs(): array
    {
        return [
            'regions' => ['regions', Region::class],
            'facilities' => ['facilities', Facility::class],
            'jobs' => ['job-roles', JobRole::class],
            'domains' => ['safety-domains', SafetyDomain::class],
            'qualifications' => ['qualifications', Qualification::class],
        ];
    }

    #[DataProvider('catalogs')]
    public function test_administrator_can_create_edit_deactivate_and_reactivate_catalog_records(string $path, string $model): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $payload = $this->payload($model);
        $this->get("/{$path}/create")->assertOk();
        $this->post("/{$path}", $payload)->assertRedirect("/{$path}")->assertSessionHasNoErrors();
        $record = $model::where('name', 'Demo configuration')->firstOrFail();
        $this->assertTrue($record->is_active);
        $this->get("/{$path}")->assertOk()->assertSee('Demo configuration');
        $this->get("/{$path}/{$record->id}/edit")->assertOk();

        $payload['name'] = 'Updated configuration';
        $payload['is_active'] = false;
        $this->put("/{$path}/{$record->id}", $payload)->assertRedirect("/{$path}")->assertSessionHasNoErrors();
        $this->assertDatabaseHas($record->getTable(), ['id' => $record->id, 'name' => 'Updated configuration', 'is_active' => false]);
        $payload['is_active'] = true;
        $this->put("/{$path}/{$record->id}", $payload)->assertSessionHasNoErrors();
        $this->assertTrue($record->fresh()->is_active);
        $this->delete("/{$path}/{$record->id}")->assertStatus(405);
    }

    public static function permissions(): array
    {
        return [
            'admin' => [UserRole::Admin, true, true, true],
            'officer' => [UserRole::ComplianceOfficer, false, true, true],
            'director' => [UserRole::RegionalDirector, false, false, true],
            'manager' => [UserRole::FacilityManager, false, false, true],
            'staff' => [UserRole::Staff, false, false, true],
            'auditor' => [UserRole::Auditor, false, false, false],
        ];
    }

    #[DataProvider('permissions')]
    public function test_configuration_gates_enforce_the_six_role_matrix(UserRole $role, bool $organization, bool $requirements, bool $employees): void
    {
        $actor = User::factory()->create(['role' => $role]);
        $gate = Gate::forUser($actor);
        $this->assertSame($organization, $gate->allows('manage-organization'));
        $this->assertSame($requirements, $gate->allows('manage-requirements'));
        $this->assertSame($employees, $gate->allows('view-employees'));
        $actor->is_active = false;
        $this->assertFalse($gate->allows('manage-organization'));
        $this->assertFalse($gate->allows('manage-requirements'));
        $this->assertFalse($gate->allows('view-employees'));
    }

    public function test_guests_and_officers_cannot_write_organization_catalogs(): void
    {
        $region = Region::factory()->create();
        $this->get('/regions')->assertRedirect('/login');
        $this->post('/regions', ['name' => 'Unauthorized', 'is_active' => true])->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => UserRole::ComplianceOfficer]));
        $this->get('/regions')->assertForbidden();
        $this->put("/regions/{$region->id}", ['name' => 'Unauthorized', 'is_active' => false])->assertForbidden();
        $this->assertTrue($region->fresh()->is_active);
    }

    #[DataProvider('catalogs')]
    public function test_catalog_forms_reject_missing_required_fields_and_duplicate_names(string $path, string $model): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->post("/{$path}", [])->assertSessionHasErrors(['name', 'is_active']);
        $payload = $this->payload($model);
        $model::create($payload);
        $this->post("/{$path}", $payload)->assertSessionHasErrors(['name' => 'The name has already been taken.']);
        $this->assertSame(1, $model::where('name', 'Demo configuration')->count());
    }

    public function test_facility_and_qualification_names_are_unique_within_their_parent_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $facility = Facility::factory()->create(['name' => 'Shared name']);
        $otherRegion = Region::factory()->create();
        $this->post('/facilities', ['name' => $facility->name, 'region_id' => $otherRegion->id, 'is_active' => true])->assertSessionHasNoErrors();
        $qualification = Qualification::factory()->create(['name' => 'Shared qualification']);
        $otherDomain = SafetyDomain::factory()->create();
        $this->post('/qualifications', ['name' => $qualification->name, 'safety_domain_id' => $otherDomain->id, 'requires_expiry' => false, 'requires_training' => true, 'is_active' => true])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('qualifications', ['name' => 'Shared qualification', 'safety_domain_id' => $otherDomain->id, 'requires_expiry' => false, 'requires_training' => true]);
    }

    public function test_active_children_and_regional_assignments_prevent_deactivating_a_region(): void
    {
        $region = Region::factory()->create();
        $facility = Facility::factory()->for($region)->create();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $payload = ['name' => $region->name, 'is_active' => false];
        $this->put("/regions/{$region->id}", $payload)->assertSessionHasErrors('is_active');
        $facility->update(['is_active' => false]);
        User::factory()->for($region)->create(['role' => UserRole::RegionalDirector]);
        $this->put("/regions/{$region->id}", $payload)->assertSessionHasErrors('is_active');
        $this->assertTrue($region->fresh()->is_active);
    }

    public function test_assigned_facilities_cannot_be_deactivated_or_moved_to_another_region(): void
    {
        $facility = Facility::factory()->create();
        User::factory()->for($facility)->create(['role' => UserRole::FacilityManager]);
        $other = Region::factory()->create();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->put("/facilities/{$facility->id}", ['name' => $facility->name, 'region_id' => $facility->region_id, 'is_active' => false])->assertSessionHasErrors('is_active');
        $this->put("/facilities/{$facility->id}", ['name' => $facility->name, 'region_id' => $other->id, 'is_active' => true])->assertSessionHasErrors('region_id');
        $this->assertTrue($facility->fresh()->is_active);
        $this->assertSame($facility->region_id, $facility->fresh()->region_id);
    }

    public function test_assigned_jobs_and_required_qualifications_cannot_be_deactivated(): void
    {
        $job = JobRole::factory()->create();
        User::factory()->for($job)->create();
        $qualification = Qualification::factory()->create();
        $job->qualifications()->attach($qualification);
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->put("/job-roles/{$job->id}", ['name' => $job->name, 'is_active' => false])->assertSessionHasErrors('is_active');
        $this->put("/qualifications/{$qualification->id}", [
            'name' => $qualification->name, 'safety_domain_id' => $qualification->safety_domain_id,
            'requires_training' => false, 'requires_expiry' => true, 'is_active' => false,
        ])->assertSessionHasErrors('is_active');
        $this->put("/safety-domains/{$qualification->safety_domain_id}", ['name' => $qualification->safetyDomain->name, 'is_active' => false])->assertSessionHasErrors('is_active');
        $this->assertTrue($job->fresh()->is_active);
        $this->assertTrue($qualification->fresh()->is_active);
    }

    public function test_inactive_parents_and_non_boolean_flags_are_rejected(): void
    {
        $region = Region::factory()->create(['is_active' => false]);
        $domain = SafetyDomain::factory()->create(['is_active' => false]);
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->post('/facilities', ['name' => 'Invalid facility', 'region_id' => $region->id, 'is_active' => true])->assertSessionHasErrors('region_id');
        $this->post('/qualifications', [
            'name' => 'Invalid qualification', 'safety_domain_id' => $domain->id,
            'requires_expiry' => 'sometimes', 'requires_training' => 'sometimes', 'is_active' => 'sometimes',
        ])->assertSessionHasErrors(['safety_domain_id', 'requires_expiry', 'requires_training', 'is_active']);
        $this->assertDatabaseMissing('facilities', ['name' => 'Invalid facility']);
        $this->assertDatabaseMissing('qualifications', ['name' => 'Invalid qualification']);
    }

    public function test_inactive_configuration_is_not_available_for_account_assignment(): void
    {
        $region = Region::factory()->create(['is_active' => false]);
        $facility = Facility::factory()->create(['is_active' => false]);
        $job = JobRole::factory()->create(['is_active' => false]);
        $staff = User::factory()->create();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->get('/admin/users')->assertOk()->assertDontSee($region->name)->assertDontSee($facility->name)->assertDontSee($job->name);
        $this->patch("/admin/users/{$staff->id}/assignment", [
            'role' => 'staff', 'is_active' => true, 'facility_id' => $facility->id, 'job_role_id' => $job->id,
        ])->assertSessionHasErrors(['facility_id', 'job_role_id']);
        $this->patch("/admin/users/{$staff->id}/assignment", ['role' => 'regional_director', 'is_active' => true, 'region_id' => $region->id])->assertSessionHasErrors('region_id');
        $this->assertNull($staff->fresh()->facility_id);
    }

    public function test_catalog_names_are_escaped_and_lists_are_paginated(): void
    {
        $region = Region::factory()->create(['name' => '<script>alert(1)</script>']);
        Region::factory()->count(15)->sequence(fn ($sequence) => ['name' => 'Zzz region '.$sequence->index])->create();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $response = $this->get('/regions')->assertOk()->assertSee($region->name)->assertDontSee($region->name, false);
        $this->assertCount(15, $response->viewData('regions')->items());
        $this->assertSame(16, $response->viewData('regions')->total());
    }

    /** @param class-string<Model> $model */
    private function payload(string $model): array
    {
        $parent = match ($model) {
            Facility::class => ['region_id' => Region::factory()->create()->id],
            Qualification::class => ['safety_domain_id' => SafetyDomain::factory()->create()->id, 'requires_expiry' => true, 'requires_training' => false],
            default => [],
        };

        return array_merge(['name' => 'Demo configuration', 'is_active' => true], $parent);
    }
}
