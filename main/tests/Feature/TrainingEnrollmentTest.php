<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\TrainingStatus;
use App\Enums\UserRole;
use App\Models\EmployeeTraining;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Region;
use App\Models\TrainingCourse;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TrainingEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_enrollment_is_owned_idempotent_and_does_not_award_qualifications(): void
    {
        $this->freezeTime();
        $course = TrainingCourse::factory()->published()->create();
        $staff = $this->staff();
        $other = $this->staff();
        $this->actingAs($staff)->post("/training-courses/{$course->id}/enrollments", [
            'employee_id' => $other->id, 'status' => 'completed', 'completed_by' => $other->id,
        ])->assertRedirect('/training-enrollments')->assertSessionHasNoErrors();
        $this->post("/training-courses/{$course->id}/enrollments")->assertSessionHasNoErrors();
        $this->assertDatabaseCount('employee_trainings', 1);
        $record = EmployeeTraining::firstOrFail();
        $this->assertSame($staff->id, $record->employee_id);
        $this->assertSame(TrainingStatus::Enrolled, $record->status);
        $this->assertNull($record->completed_by);
        $this->assertNull($record->completed_at);
        $this->assertSame(now()->toDateTimeString(), $record->enrolled_at->toDateTimeString());
        $this->assertSame([$course->id], $staff->trainingCourses()->pluck('training_courses.id')->all());
        $this->assertSame([$staff->id], $course->employees()->pluck('users.id')->all());
        $this->assertDatabaseCount('employee_qualification', 0);
    }

    public function test_guests_unassigned_staff_and_admins_cannot_enroll(): void
    {
        $course = TrainingCourse::factory()->published()->create();
        $url = "/training-courses/{$course->id}/enrollments";
        $this->post($url)->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->post($url)->assertForbidden();
        $this->get('/training-courses')->assertRedirect('/awaiting-assignment');
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))->post($url)->assertForbidden();
        $this->assertDatabaseCount('employee_trainings', 0);
    }

    public static function closedCourses(): array
    {
        return [
            'draft' => [CourseStatus::Draft, true, true, true],
            'archived' => [CourseStatus::Archived, true, true, true],
            'inactive qualification' => [CourseStatus::Published, false, true, true],
            'training disabled' => [CourseStatus::Published, true, false, true],
            'inactive domain' => [CourseStatus::Published, true, true, false],
        ];
    }

    #[DataProvider('closedCourses')]
    public function test_closed_courses_reject_direct_enrollment_requests(CourseStatus $status, bool $qualificationActive, bool $trainingRequired, bool $domainActive): void
    {
        $course = TrainingCourse::factory()->create(['status' => $status]);
        $course->qualification->update(['is_active' => $qualificationActive, 'requires_training' => $trainingRequired]);
        $course->qualification->safetyDomain->update(['is_active' => $domainActive]);
        $this->actingAs($this->staff())->post("/training-courses/{$course->id}/enrollments")->assertSessionHasErrors(['course' => 'This course is not open for enrollment.']);
        $this->assertDatabaseCount('employee_trainings', 0);
    }

    public function test_only_officers_record_completion_and_the_first_record_is_preserved(): void
    {
        $this->freezeTime();
        $enrollment = EmployeeTraining::factory()->create();
        $officer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $url = "/training-enrollments/{$enrollment->id}/completion";
        $payload = ['completed_at' => now('Asia/Manila')->toDateString(), 'feedback' => 'Practical training completed.', 'completed_by' => $enrollment->employee_id];
        $this->actingAs($enrollment->employee)->patch($url, $payload)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))->patch($url, $payload)->assertForbidden();
        $this->actingAs($officer)->patch($url, $payload)->assertRedirect('/training-enrollments')->assertSessionHasNoErrors();
        $completed = $enrollment->fresh();
        $this->assertSame(TrainingStatus::Completed, $completed->status);
        $this->assertSame($officer->id, $completed->completed_by);
        $this->assertSame($payload['completed_at'], $completed->completed_at->toDateString());
        $this->assertSame('Practical training completed.', $completed->feedback);
        $this->patch($url, array_merge($payload, ['feedback' => 'Overwritten']))->assertSessionHasErrors('completed_at');
        $this->assertSame('Practical training completed.', $enrollment->fresh()->feedback);
        $this->actingAs($enrollment->employee)->post('/training-courses/'.$enrollment->training_course_id.'/enrollments')->assertSessionHasNoErrors();
        $this->assertSame(TrainingStatus::Completed, $enrollment->fresh()->status);
        $this->assertSame($officer->id, $enrollment->fresh()->completed_by);
        $this->assertDatabaseCount('employee_trainings', 1);
        $this->assertDatabaseCount('employee_qualification', 0);
    }

    public function test_an_officer_cannot_complete_their_own_historical_enrollment(): void
    {
        $enrollment = EmployeeTraining::factory()->create();
        $employee = $enrollment->employee;
        $employee->forceFill(['role' => UserRole::ComplianceOfficer, 'facility_id' => null, 'job_role_id' => null])->save();
        $this->actingAs($employee)->patch("/training-enrollments/{$enrollment->id}/completion", ['completed_at' => now('Asia/Manila')->toDateString()])
            ->assertSessionHasErrors(['completed_at' => 'You cannot record your own training completion.']);
        $this->assertSame(TrainingStatus::Enrolled, $enrollment->fresh()->status);
    }

    public function test_completion_dates_follow_manila_calendar_boundaries_and_reject_bad_feedback(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-09 17:30:00', 'UTC'));
        $enrollment = EmployeeTraining::factory()->create(['enrolled_at' => CarbonImmutable::parse('2026-11-09 16:30:00', 'UTC')]);
        $this->actingAs(User::factory()->create(['role' => UserRole::ComplianceOfficer]));
        $url = "/training-enrollments/{$enrollment->id}/completion";
        $this->patch($url, [])->assertSessionHasErrors('completed_at');
        $this->patch($url, ['completed_at' => '2026-11-09'])->assertSessionHasErrors('completed_at');
        $this->patch($url, ['completed_at' => '2026-11-11'])->assertSessionHasErrors('completed_at');
        $this->patch($url, ['completed_at' => 'not-a-date'])->assertSessionHasErrors('completed_at');
        $this->patch($url, ['completed_at' => '2026-11-10', 'feedback' => str_repeat('x', 2001)])->assertSessionHasErrors('feedback');
        $this->assertSame(TrainingStatus::Enrolled, $enrollment->fresh()->status);
        $this->patch($url, ['completed_at' => '2026-11-10'])->assertSessionHasNoErrors();
        $this->assertSame('2026-11-10', $enrollment->fresh()->completed_at->toDateString());
    }

    public function test_completed_history_and_feedback_survive_course_archival(): void
    {
        $this->freezeTime();
        $enrollment = EmployeeTraining::factory()->create();
        $officer = User::factory()->create(['role' => UserRole::ComplianceOfficer, 'name' => 'Training Officer']);
        $this->actingAs($officer)->patch("/training-enrollments/{$enrollment->id}/completion", [
            'completed_at' => now('Asia/Manila')->toDateString(), 'feedback' => '<script>alert(1)</script>',
        ])->assertSessionHasNoErrors();
        $course = $enrollment->trainingCourse;
        $this->put("/training-courses/{$course->id}", ['title' => $course->title, 'qualification_id' => $course->qualification_id, 'status' => 'archived'])->assertSessionHasNoErrors();
        $this->actingAs($enrollment->employee)->get('/training-enrollments')->assertOk()->assertSee('Training Officer')
            ->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)->assertSee('Completed');
        $this->get("/training-courses/{$course->id}")->assertOk()->assertSee('Your status: Completed');
        $this->assertSame(TrainingStatus::Completed, $enrollment->fresh()->status);
        $this->assertSame($officer->id, $enrollment->fresh()->completed_by);
    }

    public static function scopedViewers(): array
    {
        return [
            'admin' => [UserRole::Admin, 3], 'officer' => [UserRole::ComplianceOfficer, 3],
            'manager' => [UserRole::FacilityManager, 1], 'director' => [UserRole::RegionalDirector, 2], 'staff' => [UserRole::Staff, 1],
        ];
    }

    #[DataProvider('scopedViewers')]
    public function test_training_lists_summaries_and_course_counts_use_the_same_scope(UserRole $role, int $total): void
    {
        $region = Region::factory()->create();
        $facility = Facility::factory()->for($region)->create();
        $neighbor = Facility::factory()->for($region)->create();
        $course = TrainingCourse::factory()->published()->create();
        $own = $this->staff($facility);
        EmployeeTraining::factory()->for($own, 'employee')->for($course)->create(['status' => TrainingStatus::Completed, 'completed_at' => now('Asia/Manila')->toDateString()]);
        EmployeeTraining::factory()->for($this->staff($neighbor), 'employee')->for($course)->create();
        EmployeeTraining::factory()->for($course)->create();
        $viewer = $role === UserRole::Staff ? $own : User::factory()->create([
            'role' => $role, 'facility_id' => $role === UserRole::FacilityManager ? $facility->id : null,
            'region_id' => $role === UserRole::RegionalDirector ? $region->id : null,
        ]);
        $this->actingAs($viewer)->get('/training-enrollments?employee_id=999999')->assertOk()->assertViewHas('total', $total)->assertViewHas('completed', 1);
        $response = $this->get('/training-courses')->assertOk();
        $this->assertSame($total, $response->viewData('courses')->first()->enrollments_count);
    }

    public function test_managers_cannot_discover_archived_courses_from_another_facilitys_history(): void
    {
        $own = EmployeeTraining::factory()->create();
        $outside = EmployeeTraining::factory()->create();
        $own->trainingCourse->update(['status' => CourseStatus::Archived]);
        $outside->trainingCourse->update(['status' => CourseStatus::Archived]);
        $manager = User::factory()->for($own->employee->facility)->create(['role' => UserRole::FacilityManager]);
        $this->actingAs($manager)->get('/training-enrollments')->assertOk()->assertSee($own->employee->name)->assertDontSee($outside->employee->name);
        $this->get('/training-courses/'.$own->training_course_id)->assertOk();
        $this->get('/training-courses/'.$outside->training_course_id)->assertNotFound();
        $this->get('/training-courses/999999')->assertNotFound();
    }

    public function test_unassigned_manager_scope_never_falls_back_to_global_training_access(): void
    {
        EmployeeTraining::factory()->create();
        $this->actingAs(User::factory()->create(['role' => UserRole::FacilityManager]));
        $this->get('/training-enrollments')->assertOk()->assertViewHas('total', 0)->assertViewHas('completed', 0);
        $response = $this->get('/training-courses')->assertOk();
        $this->assertSame(0, $response->viewData('courses')->first()->enrollments_count);
    }

    private function staff(?Facility $facility = null): User
    {
        return User::factory()->for($facility ?? Facility::factory())->for(JobRole::factory())->create();
    }
}
