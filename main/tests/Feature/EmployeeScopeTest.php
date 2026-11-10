<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmployeeScopeTest extends TestCase
{
    use RefreshDatabase;

    public static function roles(): array
    {
        return [
            'admin' => [UserRole::Admin, ['own', 'neighbor', 'outside', 'unassigned']],
            'officer' => [UserRole::ComplianceOfficer, ['own', 'neighbor', 'outside', 'unassigned']],
            'director' => [UserRole::RegionalDirector, ['own', 'neighbor']],
            'manager' => [UserRole::FacilityManager, ['own']],
            'staff' => [UserRole::Staff, ['own']],
            'auditor' => [UserRole::Auditor, []],
        ];
    }

    #[DataProvider('roles')]
    public function test_employee_query_limits_every_role_to_its_scope(UserRole $role, array $visible): void
    {
        $records = $this->employees();
        $viewer = $records['own'];
        $viewer->forceFill([
            'role' => $role,
            'region_id' => in_array($role, [UserRole::RegionalDirector, UserRole::Auditor], true) ? $records['region']->id : null,
            'facility_id' => in_array($role, [UserRole::Staff, UserRole::FacilityManager], true) ? $records['facility']->id : null,
        ]);
        $expected = array_map(fn (string $key): int => $records[$key]->id, $visible);
        $this->assertSame($expected, User::visibleTo($viewer)->orderBy('id')->pluck('id')->all());
        $viewer->is_active = false;
        $this->assertSame([], User::visibleTo($viewer)->pluck('id')->all());
    }

    public function test_facility_manager_cannot_widen_scope_with_urls_or_filters(): void
    {
        $records = $this->employees();
        $manager = User::factory()->for($records['facility'])->create(['role' => UserRole::FacilityManager]);
        $this->actingAs($manager)->get('/employees?facility_id='.$records['outside']->facility_id)
            ->assertOk()->assertSee('Own employee')->assertDontSee('Neighbor employee')->assertDontSee('Outside employee');
        $this->get('/employees/'.$records['own']->id)->assertOk()->assertSee($records['own']->jobRole->name);
        $this->get('/employees/'.$records['neighbor']->id)->assertNotFound();
        $this->get('/employees/'.$records['outside']->id)->assertNotFound();
        $this->get('/employees/'.$manager->id)->assertNotFound();
    }

    public function test_regional_director_can_view_neighbor_facility_but_not_another_region(): void
    {
        $records = $this->employees();
        $director = User::factory()->for($records['region'])->create(['role' => UserRole::RegionalDirector]);
        $this->actingAs($director)->get('/employees')->assertOk()->assertSee('Own employee')->assertSee('Neighbor employee')->assertDontSee('Outside employee')->assertDontSee('Unassigned employee');
        $this->get('/employees/'.$records['neighbor']->id)->assertOk();
        $this->get('/employees/'.$records['outside']->id)->assertNotFound();
    }

    public function test_staff_can_view_own_requirements_but_not_other_employee_records(): void
    {
        $records = $this->employees();
        $qualification = Qualification::factory()->create(['name' => 'Mandatory transport training', 'requires_training' => true]);
        $records['own']->jobRole->qualifications()->attach($qualification);
        $this->actingAs($records['own'])->get('/employees')->assertOk()->assertSee('Own employee')->assertDontSee('Neighbor employee');
        $this->get('/employees/'.$records['own']->id)->assertOk()->assertSee('Mandatory transport training')->assertSee('Training completion required')->assertSee('These are requirements, not awarded qualifications.');
        $this->get('/employees/'.$records['neighbor']->id)->assertNotFound();
    }

    public function test_unassigned_staff_and_guests_cannot_bypass_onboarding(): void
    {
        $employee = User::factory()->create();
        $this->get('/employees')->assertRedirect('/login');
        $this->get('/employees/'.$employee->id)->assertRedirect('/login');
        $this->actingAs($employee)->get('/employees')->assertRedirect('/awaiting-assignment');
        $this->get('/employees/'.$employee->id)->assertRedirect('/awaiting-assignment');
    }

    public function test_unscoped_managers_and_directors_see_no_staff_instead_of_global_access(): void
    {
        $this->employees();
        foreach ([UserRole::FacilityManager, UserRole::RegionalDirector] as $role) {
            $viewer = User::factory()->create(['role' => $role]);
            $this->actingAs($viewer)->get('/employees')->assertOk()->assertSee('No employees in your assigned scope.')->assertDontSee('Own employee');
        }
    }

    public function test_employee_reassignment_immediately_removes_old_managers_access(): void
    {
        $records = $this->employees();
        $manager = User::factory()->for($records['facility'])->create(['role' => UserRole::FacilityManager]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->patch('/admin/users/'.$records['own']->id.'/assignment', [
            'role' => 'staff', 'is_active' => true, 'facility_id' => $records['outside']->facility_id, 'job_role_id' => $records['own']->job_role_id,
        ])->assertSessionHasNoErrors();
        $this->actingAs($manager)->get('/employees/'.$records['own']->id)->assertNotFound();
        $this->get('/employees')->assertOk()->assertDontSee('Own employee');
    }

    public function test_missing_requirements_do_not_claim_compliance_and_employee_names_are_escaped(): void
    {
        $records = $this->employees();
        $records['own']->update(['name' => '<script>alert(1)</script>']);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->get('/employees/'.$records['own']->id)->assertOk()
            ->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('No requirements configured. This does not mean the employee is compliant.');
        $this->get('/employees/999999')->assertNotFound();
    }

    /** @return array<string, User|Region|Facility> */
    private function employees(): array
    {
        $region = Region::factory()->create();
        $facility = Facility::factory()->for($region)->create();
        $neighbor = Facility::factory()->for($region)->create();
        $outside = Facility::factory()->create();
        $job = JobRole::factory()->create();

        return [
            'region' => $region,
            'facility' => $facility,
            'own' => User::factory()->for($facility)->for($job)->create(['name' => 'Own employee']),
            'neighbor' => User::factory()->for($neighbor)->for($job)->create(['name' => 'Neighbor employee']),
            'outside' => User::factory()->for($outside)->for($job)->create(['name' => 'Outside employee']),
            'unassigned' => User::factory()->create(['name' => 'Unassigned employee']),
        ];
    }
}
