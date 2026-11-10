<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class JobRequirementsTest extends TestCase
{
    use RefreshDatabase;

    public static function maintainers(): array
    {
        return ['admin' => [UserRole::Admin], 'officer' => [UserRole::ComplianceOfficer]];
    }

    #[DataProvider('maintainers')]
    public function test_maintainers_can_replace_and_clear_mandatory_job_requirements(UserRole $role): void
    {
        $job = JobRole::factory()->create();
        $old = Qualification::factory()->create();
        $new = Qualification::factory()->create();
        $job->qualifications()->attach($old);
        $this->actingAs(User::factory()->create(['role' => $role]));
        $this->get('/job-roles')->assertOk()->assertSee($job->name);
        $this->get("/job-roles/{$job->id}/requirements")->assertOk()->assertSee($new->name)->assertSee($new->safetyDomain->name);
        $this->put("/job-roles/{$job->id}/requirements", ['qualification_ids' => [$new->id]])->assertRedirect("/job-roles/{$job->id}/requirements")->assertSessionHasNoErrors();
        $this->assertSame([$new->id], $job->qualifications()->pluck('qualifications.id')->all());
        $this->assertSame([$job->id], $new->jobRoles()->pluck('job_roles.id')->all());

        $this->put("/job-roles/{$job->id}/requirements", [])->assertSessionHasNoErrors();
        $this->assertSame(0, $job->qualifications()->count());
    }

    public function test_staff_cannot_edit_requirements_and_guests_must_sign_in(): void
    {
        $job = JobRole::factory()->create();
        $qualification = Qualification::factory()->create();
        $this->get("/job-roles/{$job->id}/requirements")->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->get('/job-roles')->assertForbidden();
        $this->get("/job-roles/{$job->id}/requirements")->assertForbidden();
        $this->put("/job-roles/{$job->id}/requirements", ['qualification_ids' => [$qualification->id]])->assertForbidden();
        $this->assertSame(0, $job->qualifications()->count());
    }

    public function test_invalid_duplicate_and_inactive_qualifications_leave_existing_requirements_unchanged(): void
    {
        $job = JobRole::factory()->create();
        $active = Qualification::factory()->create();
        $inactive = Qualification::factory()->create(['is_active' => false]);
        $job->qualifications()->attach($active);
        $this->actingAs(User::factory()->create(['role' => UserRole::ComplianceOfficer]));
        $url = "/job-roles/{$job->id}/requirements";
        $this->put($url, ['qualification_ids' => [$inactive->id]])->assertSessionHasErrors('qualification_ids.0');
        $this->put($url, ['qualification_ids' => [999999]])->assertSessionHasErrors('qualification_ids.0');
        $this->put($url, ['qualification_ids' => [$active->id, $active->id]])->assertSessionHasErrors(['qualification_ids.0', 'qualification_ids.1']);
        $this->from($url)->put($url, ['qualification_ids' => 'invalid'])->assertRedirect($url)->assertSessionHasErrors('qualification_ids');
        $this->get($url)->assertOk();
        $this->assertSame([$active->id], $job->qualifications()->pluck('qualifications.id')->all());
    }

    public function test_inactive_jobs_allow_removing_requirements_but_not_adding_them(): void
    {
        $job = JobRole::factory()->create(['is_active' => false]);
        $qualification = Qualification::factory()->create();
        $job->qualifications()->attach($qualification);
        $this->actingAs(User::factory()->create(['role' => UserRole::ComplianceOfficer]));
        $this->put("/job-roles/{$job->id}/requirements", ['qualification_ids' => [$qualification->id]])->assertSessionHasErrors('qualification_ids');
        $this->put("/job-roles/{$job->id}/requirements", ['qualification_ids' => []])->assertSessionHasNoErrors();
        $this->assertSame(0, $job->qualifications()->count());
    }

    public function test_job_requirements_do_not_award_employee_qualifications(): void
    {
        $job = JobRole::factory()->create();
        $qualification = Qualification::factory()->create();
        $job->qualifications()->attach($qualification);
        $employee = User::factory()->for($job)->for(Facility::factory())->create();
        $this->assertSame([$qualification->id], $employee->jobRole->qualifications()->pluck('qualifications.id')->all());
        $this->assertSame(0, $employee->qualifications()->count());

        $employee->qualifications()->attach($qualification);
        $this->assertSame([$qualification->id], $employee->qualifications()->pluck('qualifications.id')->all());
        $this->assertSame([$employee->id], $qualification->employees()->pluck('users.id')->all());
    }

    public function test_demo_seeding_is_repeatable_and_does_not_overwrite_qualification_configuration(): void
    {
        $this->seed(DatabaseSeeder::class);
        $driving = Qualification::where('name', 'Defensive Driving')->firstOrFail();
        $driving->update(['requires_expiry' => false]);
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('safety_domains', 2);
        $this->assertDatabaseCount('qualifications', 2);
        $this->assertDatabaseCount('job_role_qualification', 2);
        $this->assertFalse($driving->fresh()->requires_expiry);
    }

    public function test_demo_seeding_does_not_reassign_inactive_qualifications(): void
    {
        $this->seed(DatabaseSeeder::class);
        $driving = Qualification::where('name', 'Defensive Driving')->firstOrFail();
        $driver = JobRole::where('name', 'Driver')->firstOrFail();
        $driver->qualifications()->detach($driving);
        $driving->update(['is_active' => false]);
        $driving->safetyDomain->update(['is_active' => false]);
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseMissing('job_role_qualification', ['job_role_id' => $driver->id, 'qualification_id' => $driving->id]);
        $this->assertFalse($driving->fresh()->is_active);
    }

    public function test_demo_seeding_does_not_assign_requirements_to_inactive_jobs(): void
    {
        $this->seed(DatabaseSeeder::class);
        $driver = JobRole::where('name', 'Driver')->firstOrFail();
        $driver->qualifications()->detach();
        $driver->update(['is_active' => false]);
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(0, $driver->qualifications()->count());
        $this->assertFalse($driver->fresh()->is_active);
    }
}
