<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\EmployeeTraining;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\TrainingCourse;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TrainingCourseTest extends TestCase
{
    use RefreshDatabase;

    public static function permissions(): array
    {
        return [
            'admin' => [UserRole::Admin, true, false, false, true],
            'officer' => [UserRole::ComplianceOfficer, true, false, true, true],
            'manager' => [UserRole::FacilityManager, false, false, false, true],
            'director' => [UserRole::RegionalDirector, false, false, false, true],
            'staff' => [UserRole::Staff, false, true, false, true],
            'auditor' => [UserRole::Auditor, false, false, false, false],
        ];
    }

    #[DataProvider('permissions')]
    public function test_training_gates_preserve_role_separation(UserRole $role, bool $manage, bool $enroll, bool $complete, bool $view): void
    {
        $actor = User::factory()->for(Facility::factory())->for(JobRole::factory())->create(['role' => $role]);
        $gate = Gate::forUser($actor);
        $this->assertSame($manage, $gate->allows('manage-courses'));
        $this->assertSame($enroll, $gate->allows('enroll-training'));
        $this->assertSame($complete, $gate->allows('complete-training'));
        $this->assertSame($view, $gate->allows('view-training'));
        $actor->is_active = false;
        foreach (['manage-courses', 'enroll-training', 'complete-training', 'view-training'] as $ability) {
            $this->assertFalse($gate->allows($ability));
        }
    }

    public static function publishers(): array
    {
        return ['admin' => [UserRole::Admin], 'officer' => [UserRole::ComplianceOfficer]];
    }

    #[DataProvider('publishers')]
    public function test_publishers_can_create_publish_edit_and_archive_an_offering(UserRole $role): void
    {
        $qualification = Qualification::factory()->create(['requires_training' => true]);
        $payload = ['title' => 'Transport course', 'description' => 'Safe driving', 'qualification_id' => $qualification->id, 'status' => 'draft'];
        $this->actingAs(User::factory()->create(['role' => $role]));
        $this->get('/training-courses/create')->assertOk();
        $this->post('/training-courses', $payload)->assertSessionHasNoErrors();
        $course = TrainingCourse::where('title', 'Transport course')->firstOrFail();
        $this->assertSame(CourseStatus::Draft, $course->status);
        $this->get('/training-courses')->assertOk()->assertSee('Transport course');
        $this->get("/training-courses/{$course->id}/edit")->assertOk();
        $payload['status'] = 'published';
        $this->put("/training-courses/{$course->id}", $payload)->assertRedirect("/training-courses/{$course->id}")->assertSessionHasNoErrors();
        $this->assertSame(CourseStatus::Published, $course->fresh()->status);
        $payload['status'] = 'archived';
        $this->put("/training-courses/{$course->id}", $payload)->assertSessionHasNoErrors();
        $this->assertSame(CourseStatus::Archived, $course->fresh()->status);
        $payload['status'] = 'published';
        $this->put("/training-courses/{$course->id}", $payload)->assertSessionHasErrors(['status' => 'Archived offerings stay archived. Create a new course for renewals.']);
        $this->delete("/training-courses/{$course->id}")->assertStatus(405);
    }

    public function test_guests_and_staff_cannot_manage_courses(): void
    {
        $course = TrainingCourse::factory()->published()->create();
        $this->get('/training-courses')->assertRedirect('/login');
        $this->post('/training-courses', [])->assertRedirect('/login');
        $staff = User::factory()->for(Facility::factory())->for(JobRole::factory())->create();
        $this->actingAs($staff)->get('/training-courses/create')->assertForbidden();
        $this->post('/training-courses', [])->assertForbidden();
        $this->put("/training-courses/{$course->id}", ['title' => 'Tampered'])->assertForbidden();
        $this->assertNotSame('Tampered', $course->fresh()->title);
    }

    public static function invalidQualifications(): array
    {
        return ['inactive qualification' => [['is_active' => false], true], 'not training required' => [['requires_training' => false], true], 'inactive domain' => [[], false]];
    }

    #[DataProvider('invalidQualifications')]
    public function test_courses_require_an_active_training_qualification_and_domain(array $attributes, bool $domainActive): void
    {
        $qualification = Qualification::factory()->create(array_merge(['requires_training' => true], $attributes));
        $qualification->safetyDomain->update(['is_active' => $domainActive]);
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->post('/training-courses', ['title' => 'Invalid offering', 'qualification_id' => $qualification->id, 'status' => 'published'])
            ->assertSessionHasErrors(['qualification_id' => 'Choose an active training-required qualification in an active safety domain.']);
        $this->assertDatabaseCount('training_courses', 0);
    }

    public function test_course_forms_validate_required_fields_status_and_description_length(): void
    {
        $qualification = Qualification::factory()->create(['requires_training' => true]);
        $this->actingAs(User::factory()->create(['role' => UserRole::ComplianceOfficer]));
        $this->post('/training-courses', [])->assertSessionHasErrors(['title', 'qualification_id', 'status']);
        $this->post('/training-courses', ['title' => 'Invalid', 'description' => str_repeat('x', 5001), 'qualification_id' => 999999, 'status' => 'unknown'])->assertSessionHasErrors(['description', 'qualification_id', 'status']);
        $this->post('/training-courses', ['title' => 'Archived new', 'qualification_id' => $qualification->id, 'status' => 'archived'])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('training_courses', 0);
    }

    public function test_course_mapping_can_change_before_enrollment_but_not_after(): void
    {
        $course = TrainingCourse::factory()->published()->create();
        $new = Qualification::factory()->create(['requires_training' => true]);
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $payload = ['title' => $course->title, 'qualification_id' => $new->id, 'status' => 'published'];
        $this->put("/training-courses/{$course->id}", $payload)->assertSessionHasNoErrors();
        $this->assertSame($new->id, $course->fresh()->qualification_id);
        EmployeeTraining::factory()->for($course)->create();
        $payload['qualification_id'] = $course->qualification_id;
        $this->put("/training-courses/{$course->id}", $payload)->assertSessionHasErrors('qualification_id');
        $this->assertSame($new->id, $course->fresh()->qualification_id);
    }

    public function test_training_qualification_flags_and_history_cannot_be_invalidated_by_configuration(): void
    {
        $course = TrainingCourse::factory()->published()->create();
        $qualification = $course->qualification;
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $payload = ['name' => $qualification->name, 'safety_domain_id' => $qualification->safety_domain_id, 'requires_training' => false, 'requires_expiry' => true, 'is_active' => false];
        $this->put("/qualifications/{$qualification->id}", $payload)->assertSessionHasErrors(['requires_training', 'is_active']);
        $course->update(['status' => CourseStatus::Archived]);
        $payload['requires_training'] = true;
        $this->put("/qualifications/{$qualification->id}", $payload)->assertSessionHasNoErrors();
        $this->assertFalse($qualification->fresh()->is_active);
        $this->put("/training-courses/{$course->id}", ['title' => 'Corrected archived title', 'qualification_id' => $qualification->id, 'status' => 'archived'])->assertSessionHasNoErrors();
        $this->assertSame('Corrected archived title', $course->fresh()->title);
    }

    public function test_unpublished_courses_are_hidden_unless_the_viewer_has_enrollment_history(): void
    {
        $draft = TrainingCourse::factory()->create(['title' => 'Private draft']);
        $enrollment = EmployeeTraining::factory()->create();
        $staff = $enrollment->employee;
        $course = $enrollment->trainingCourse;
        $course->update(['status' => CourseStatus::Archived]);
        $this->actingAs($staff)->get('/training-courses')->assertOk()->assertSee($course->title)->assertDontSee('Private draft');
        $this->get("/training-courses/{$draft->id}")->assertNotFound();
        $this->get("/training-courses/{$course->id}")->assertOk()->assertSee('Your status: Enrolled');
        $other = User::factory()->for(Facility::factory())->for(JobRole::factory())->create();
        $this->actingAs($other)->get("/training-courses/{$course->id}")->assertNotFound();
    }

    public function test_course_names_and_descriptions_are_escaped(): void
    {
        $course = TrainingCourse::factory()->published()->create(['title' => '<script>alert(1)</script>', 'description' => '<img src=x onerror=alert(1)>']);
        $this->actingAs(User::factory()->create(['role' => UserRole::ComplianceOfficer]))->get("/training-courses/{$course->id}")
            ->assertOk()->assertSee($course->title)->assertDontSee($course->title, false)->assertSee($course->description)->assertDontSee($course->description, false);
    }

    public function test_demo_course_seeding_is_repeatable_and_does_not_republish_archived_offerings(): void
    {
        $this->seed(CatalogSeeder::class);
        $course = TrainingCourse::firstOrFail();
        $course->update(['status' => CourseStatus::Archived]);
        $this->seed(CatalogSeeder::class);
        $this->assertDatabaseCount('training_courses', 2);
        $this->assertSame(CourseStatus::Archived, $course->fresh()->status);
        $this->assertDatabaseCount('employee_trainings', 0);
    }
}
