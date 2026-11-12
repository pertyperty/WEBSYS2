<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\EmployeeTraining;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\TrainingCourse;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TrainingMatrixFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_filters_combine_search_status_and_facility_without_leaking_other_teams(): void
    {
        $facility = Facility::factory()->create();
        $manager = User::factory()->role(UserRole::FacilityManager)->assignedTo($facility)->create();
        $staff = User::factory()->assignedTo($facility, JobRole::factory()->create())->create(['name' => 'Cold chain operator']);
        $enrollment = EmployeeTraining::factory()->for($staff, 'employee')->create();
        EmployeeTraining::factory()->for($staff, 'employee')->completed(User::factory()->role(UserRole::ComplianceOfficer)->create())->create();
        $elsewhere = EmployeeTraining::factory()->create();

        $this->actingAs($manager)->get('/training-enrollments?search=Cold&status=enrolled&facility_id='.$facility->id)->assertOk()
            ->assertViewHas('enrollments', fn ($rows): bool => $rows->modelKeys() === [$enrollment->id])
            ->assertViewHas('facilities', fn ($rows): bool => $rows->modelKeys() === [$facility->id]);
        $this->get('/training-enrollments?facility_id='.$elsewhere->employee->facility_id)->assertOk()
            ->assertViewHas('enrollments', fn ($rows): bool => $rows->isEmpty())->assertSee('No enrollments match your filters');
    }

    #[TestWith(['Cold chain handling'])]
    #[TestWith(['Handling clearance'])]
    public function test_staff_can_find_their_course_by_title_or_qualification_without_seeing_other_enrollments(string $search): void
    {
        $qualification = Qualification::factory()->requiringTraining()->create(['name' => 'Handling clearance']);
        $course = TrainingCourse::factory()->for($qualification)->published()->create(['title' => 'Cold chain handling']);
        $enrollment = EmployeeTraining::factory()->for($course)->create();
        EmployeeTraining::factory()->for($course)->create();

        $this->actingAs($enrollment->employee)->get('/training-enrollments?'.http_build_query(['search' => $search]))->assertOk()
            ->assertViewHas('enrollments', fn ($rows): bool => $rows->modelKeys() === [$enrollment->id]);
    }

    public function test_matrix_combines_name_facility_and_job_filters_with_scoped_options(): void
    {
        $facility = Facility::factory()->create();
        $job = JobRole::factory()->create();
        $otherJob = JobRole::factory()->create();
        $manager = User::factory()->role(UserRole::FacilityManager)->assignedTo($facility)->create();
        $target = User::factory()->assignedTo($facility, $job)->create(['name' => 'Ana Driver']);
        User::factory()->assignedTo($facility, $otherJob)->create(['name' => 'Ana Warehouse']);
        $outsider = User::factory()->assignedTo(Facility::factory()->create(), JobRole::factory()->create())->create(['name' => 'Ana Outside']);

        $this->actingAs($manager)->get('/qualification-matrix?search=Ana&facility_id='.$facility->id.'&job_role_id='.$job->id)->assertOk()
            ->assertViewHas('employees', fn ($rows): bool => $rows->modelKeys() === [$target->id])
            ->assertViewHas('jobs', fn ($rows): bool => $rows->pluck('id')->sort()->values()->all() === [$job->id, $otherJob->id])
            ->assertViewHas('facilities', fn ($rows): bool => $rows->modelKeys() === [$facility->id]);
        $this->get('/qualification-matrix?job_role_id='.$outsider->job_role_id)->assertOk()
            ->assertViewHas('employees', fn ($rows): bool => $rows->isEmpty())->assertSee('No employees match your filters');
    }

    #[TestWith(['0'])]
    #[TestWith(['100%'])]
    #[TestWith(['team_one'])]
    public function test_training_and_matrix_search_treat_wildcards_and_zero_literally(string $term): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        $employee = User::factory()->assignedTo(Facility::factory()->create(), JobRole::factory()->create())->create(['name' => 'Worker '.$term]);
        $course = TrainingCourse::factory()->published()->create(['title' => 'Course '.$term]);
        $enrollment = EmployeeTraining::factory()->for($employee, 'employee')->for($course)->create();
        $different = $term === '0' ? 'without digits' : str_replace(['%', '_'], ['x', 'X'], $term);
        $distractor = User::factory()->create(['name' => 'Worker '.$different]);
        EmployeeTraining::factory()->for($distractor, 'employee')->for(TrainingCourse::factory()->published()->create(['title' => 'Course '.$different]))->create();

        $this->actingAs($admin)->get('/training-enrollments?'.http_build_query(['search' => $term]))->assertOk()
            ->assertViewHas('enrollments', fn ($rows): bool => $rows->modelKeys() === [$enrollment->id]);
        $this->get('/qualification-matrix?'.http_build_query(['search' => $term]))->assertOk()
            ->assertViewHas('employees', fn ($rows): bool => $rows->modelKeys() === [$employee->id]);
    }

    public function test_filtered_training_totals_and_pagination_cover_all_matching_rows(): void
    {
        $staff = User::factory()->assignedTo(Facility::factory()->create(), JobRole::factory()->create())->create();
        $qualification = Qualification::factory()->requiringTraining()->create(['name' => 'Handling clearance']);
        TrainingCourse::factory()->count(16)->for($qualification)->published()->create()
            ->each(fn (TrainingCourse $course) => EmployeeTraining::factory()->for($staff, 'employee')->for($course)->create());

        $this->actingAs($staff)->get('/training-enrollments?search=Handling&status=enrolled')->assertOk()
            ->assertSee('16 enrollments')->assertSee('search=Handling&amp;status=enrolled&amp;page=2', false);
        $this->get('/training-enrollments?search=Handling&status=enrolled&page=2')->assertOk()
            ->assertViewHas('enrollments', fn ($rows): bool => $rows->count() === 1 && $rows->total() === 16);
    }

    public function test_matrix_filters_are_applied_before_pagination_and_keep_requirements_for_displayed_employees(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        $job = JobRole::factory()->create();
        $qualification = Qualification::factory()->create(['name' => 'Driver clearance']);
        $job->qualifications()->attach($qualification);
        User::factory()->count(16)->assignedTo(Facility::factory()->create(), $job)->create(['name' => 'Dispatch driver']);
        User::factory()->create(['name' => 'Dispatch outsider']);

        $this->actingAs($admin)->get('/qualification-matrix?search=Dispatch&job_role_id='.$job->id.'&page=2')->assertOk()
            ->assertViewHas('employees', fn ($rows): bool => $rows->count() === 1 && $rows->total() === 16)
            ->assertViewHas('qualifications', fn ($rows): bool => $rows->pluck('qualification_id')->all() === [$qualification->id])
            ->assertSee('Driver clearance')->assertDontSee('Dispatch outsider');
    }

    public function test_validation_returns_completion_details_to_the_correct_form_only(): void
    {
        $officer = User::factory()->role(UserRole::ComplianceOfficer)->create();
        $enrollment = EmployeeTraining::factory()->create();
        EmployeeTraining::factory()->create();

        $this->actingAs($officer)->from('/training-enrollments')->patch('/training-enrollments/'.$enrollment->id.'/completion', [
            '_completion_enrollment' => $enrollment->id, 'completed_at' => '2999-01-01', 'feedback' => 'Keep these assessment notes',
        ])->assertRedirect('/training-enrollments')->assertSessionHasErrors('completed_at');
        $response = $this->get('/training-enrollments')->assertOk()->assertSee('Keep these assessment notes');
        $this->assertSame(1, substr_count($response->getContent(), 'Keep these assessment notes'));
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $document->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);
        $this->assertSame(1, $xpath->query('//details[@open]//form[@data-filter-edit-form]')->length);
        $this->assertSame((string) $enrollment->id, $xpath->query('//details[@open]//input[@name="_completion_enrollment"]')->item(0)->getAttribute('value'));
        $this->assertNull($enrollment->fresh()->completed_at);
    }

    public function test_staff_guidance_distinguishes_training_completion_from_evidence_approval(): void
    {
        $enrollment = EmployeeTraining::factory()->create();

        $this->actingAs($enrollment->employee)->get('/training-enrollments')->assertOk()->assertSee('ask a Compliance Officer');
        $enrollment->forceFill(['status' => 'completed', 'completed_at' => today()])->save();
        $this->get('/training-enrollments')->assertOk()->assertSee('Completion alone does not meet')->assertSee('View my evidence')->assertSee('Submit evidence');
        $this->get('/training-courses/'.$enrollment->training_course_id)->assertOk()->assertSee('select this qualification and completed course');
    }
}
