<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_non_administrators_cannot_manage_assignments(): void
    {
        $target = User::factory()->create();
        $this->get('/admin/users')->assertRedirect('/login');
        $this->patch("/admin/users/{$target->id}/assignment", ['role' => 'admin', 'is_active' => true])->assertRedirect('/login');

        foreach ([UserRole::Staff, UserRole::ComplianceOfficer, UserRole::FacilityManager, UserRole::RegionalDirector] as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $this->actingAs($actor)->get('/admin/users')->assertForbidden();
            $this->patch("/admin/users/{$target->id}/assignment", ['role' => 'admin', 'is_active' => true])->assertForbidden();
        }
        $this->assertSame(UserRole::Staff, $target->fresh()->role);
    }

    public function test_administrator_can_assign_staff_and_staff_can_access_foundation_dashboard(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $staff = User::factory()->create();
        [$region, $facility, $job] = $this->organization();
        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee($staff->name);
        $this->patch("/admin/users/{$staff->id}/assignment", [
            'role' => 'staff', 'is_active' => true, 'facility_id' => $facility->id, 'job_role_id' => $job->id,
        ])->assertSessionHasNoErrors();

        $this->actingAs($staff->fresh())->get('/dashboard')->assertOk()->assertSee($facility->name)->assertSee($job->name);
        $this->assertSame([$staff->id], $region->employees()->pluck('users.id')->all());
    }

    public function test_unassigned_staff_cannot_bypass_onboarding_by_visiting_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect('/awaiting-assignment');
    }

    public function test_staff_assignments_must_be_complete_and_reference_existing_records(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $staff = User::factory()->create();
        [, $facility] = $this->organization();
        $this->actingAs($admin)->patch("/admin/users/{$staff->id}/assignment", [
            'role' => 'staff', 'is_active' => true, 'facility_id' => $facility->id,
        ])->assertSessionHasErrors('facility_id');
        $this->patch("/admin/users/{$staff->id}/assignment", [
            'role' => 'staff', 'is_active' => true, 'facility_id' => 9999, 'job_role_id' => 9999,
        ])->assertSessionHasErrors(['facility_id', 'job_role_id']);
        $this->assertNull($staff->fresh()->facility_id);
    }

    public function test_role_specific_scopes_are_validated(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $target = User::factory()->create();
        [$region, $facility] = $this->organization();
        $this->actingAs($admin);

        foreach (['facility_manager', 'regional_director', 'auditor'] as $role) {
            $this->patch("/admin/users/{$target->id}/assignment", ['role' => $role, 'is_active' => true])->assertSessionHasErrors();
        }
        $this->patch("/admin/users/{$target->id}/assignment", [
            'role' => 'facility_manager', 'is_active' => true, 'facility_id' => $facility->id,
        ])->assertSessionHasNoErrors();
        $this->patch("/admin/users/{$target->id}/assignment", [
            'role' => 'regional_director', 'is_active' => true, 'facility_id' => null, 'region_id' => $region->id,
        ])->assertSessionHasNoErrors();
        $this->patch("/admin/users/{$target->id}/assignment", [
            'role' => 'admin', 'is_active' => true, 'region_id' => $region->id,
        ])->assertSessionHasErrors('role');
        $this->patch("/admin/users/{$target->id}/assignment", [
            'role' => 'staff', 'is_active' => true,
        ])->assertSessionHasNoErrors();
        $this->assertNull($target->fresh()->region_id);
        $this->assertNull($target->fresh()->facility_id);
    }

    public function test_administrator_cannot_demote_or_deactivate_self(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->patch("/admin/users/{$admin->id}/assignment", ['role' => 'staff', 'is_active' => true])->assertSessionHasErrors('role');
        $this->patch("/admin/users/{$admin->id}/assignment", ['role' => 'admin', 'is_active' => false])->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_region_relationship_excludes_other_regions_and_non_staff(): void
    {
        [$region, $facility, $job] = $this->organization();
        $staff = User::factory()->create(['facility_id' => $facility->id, 'job_role_id' => $job->id]);
        User::factory()->create(['facility_id' => $facility->id, 'role' => UserRole::FacilityManager]);
        $other = Region::factory()->create();
        $otherFacility = Facility::factory()->for($other)->create();
        User::factory()->create(['facility_id' => $otherFacility->id, 'job_role_id' => $job->id]);
        $this->assertSame([$staff->id], $region->employees()->pluck('users.id')->all());
    }

    public function test_private_disk_cannot_serve_files_through_storage_route(): void
    {
        $this->assertFalse(config('filesystems.disks.local.serve'));
        $this->assertStringEndsWith('app/private', str_replace('\\', '/', config('filesystems.disks.local.root')));
        $this->get('/storage/private-credential.pdf')->assertNotFound();
    }

    private function organization(): array
    {
        $region = Region::factory()->create();
        $facility = Facility::factory()->for($region)->create();
        $job = JobRole::factory()->create();

        return [$region, $facility, $job];
    }
}
