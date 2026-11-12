<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\CredentialDocument;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\SafetyDomain;
use App\Models\TrainingCourse;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SearchFilterWorkflowTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['/employees'])]
    #[TestWith(['/admin/users'])]
    #[TestWith(['/credentials'])]
    #[TestWith(['/findings'])]
    #[TestWith(['/training-courses'])]
    #[TestWith(['/audits'])]
    #[TestWith(['/reports'])]
    public function test_list_pages_keep_labeled_get_filters_and_a_no_javascript_submit(string $path): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();

        $response = $this->actingAs($admin)->get($path, ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'text/html'])
            ->assertOk()->assertSee('data-filter-results', false)->assertSee('data-filter-summary', false)
            ->assertSee('Update results')->assertSee('Clear filters')->assertSee('role="status"', false);

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $document->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);
        $forms = $xpath->query('//form[@data-filter-form]');
        $this->assertSame(1, $forms->length);
        $this->assertSame('GET', $forms->item(0)->getAttribute('method'));
        foreach ($xpath->query('//form[@data-filter-form]//input|//form[@data-filter-form]//select') as $control) {
            $id = $control->getAttribute('id');
            $this->assertNotSame('', $id);
            $this->assertSame(1, $xpath->query('//label[@for="'.$id.'"]')->length);
        }
    }

    public function test_people_search_combines_location_and_job_with_the_existing_scope(): void
    {
        $facility = Facility::factory()->create(['name' => 'Shared cold chain hub']);
        $other = Facility::factory()->create(['name' => 'Other cold chain hub']);
        $job = JobRole::factory()->create(['name' => 'Cold chain driver']);
        $manager = User::factory()->role(UserRole::FacilityManager)->assignedTo($facility)->create();
        $employee = User::factory()->assignedTo($facility, $job)->create();
        User::factory()->assignedTo($other, $job)->create();

        $this->actingAs($manager)->get('/employees?search=cold%20chain&facility_id='.$facility->id)->assertOk()
            ->assertViewHas('employees', fn ($rows): bool => $rows->modelKeys() === [$employee->id])
            ->assertViewHas('facilities', fn ($rows): bool => $rows->modelKeys() === [$facility->id]);
        $this->get('/employees?facility_id='.$other->id)->assertOk()
            ->assertViewHas('employees', fn ($rows): bool => $rows->isEmpty())
            ->assertSee('No employees match your filters');
    }

    public function test_account_search_combines_role_and_active_status(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        $target = User::factory()->role(UserRole::Staff)->inactive()->create(['email' => 'dispatch@example.test']);
        User::factory()->role(UserRole::Staff)->create(['email' => 'dispatch.active@example.test']);
        User::factory()->role(UserRole::FacilityManager)->inactive()->create(['email' => 'dispatch.manager@example.test']);

        $this->actingAs($admin)->get('/admin/users?search=dispatch&role=staff&active=inactive')->assertOk()
            ->assertViewHas('users', fn ($rows): bool => $rows->modelKeys() === [$target->id]);
    }

    #[TestWith(['0', 'Worker without digits'])]
    #[TestWith(['100%', 'Worker 100x'])]
    #[TestWith(['team_one', 'Worker teamXone'])]
    public function test_search_treats_zero_and_sql_wildcards_as_literal_text(string $term, string $distractor): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create(['name' => 'Administrator']);
        $target = User::factory()->create(['name' => 'Worker '.$term]);
        User::factory()->create(['name' => $distractor]);

        $this->actingAs($admin)->get('/employees?'.http_build_query(['search' => $term]))->assertOk()
            ->assertViewHas('employees', fn ($rows): bool => $rows->modelKeys() === [$target->id]);
        $this->get('/reports?'.http_build_query(['q' => $term]))->assertOk()
            ->assertViewHas('rows', fn (array $rows): bool => array_column($rows, 0) === [$target->name]);
    }

    public function test_document_search_and_review_status_never_reveal_another_employees_submissions(): void
    {
        $employee = User::factory()->assignedTo(Facility::factory()->create(), JobRole::factory()->create())->create();
        $qualification = Qualification::factory()->create(['name' => 'Handling clearance']);
        $pending = CredentialDocument::factory()->for($employee, 'employee')->for($qualification)->create();
        CredentialDocument::factory()->for($employee, 'employee')->for($qualification)->approved()->create();
        CredentialDocument::factory()->for($qualification)->create();

        $this->actingAs($employee)->get('/credentials?search=Handling&status=pending')->assertOk()
            ->assertViewHas('documents', fn ($rows): bool => $rows->modelKeys() === [$pending->id]);
        $this->get('/credentials?search='.$pending->reference)->assertOk()
            ->assertViewHas('documents', fn ($rows): bool => $rows->modelKeys() === [$pending->id]);
    }

    public function test_finding_search_keeps_facility_scope_and_respects_overdue_status(): void
    {
        $this->freezeTime();
        $finding = AuditFinding::factory()->create(['title' => 'Handling records', 'due_on' => now()->subDay()]);
        AuditFinding::factory()->for($finding->audit)->for($finding->assignee, 'assignee')->create([
            'title' => 'Handling records resolved', 'status' => 'resolved', 'due_on' => now()->subDay(),
        ]);
        AuditFinding::factory()->create(['title' => 'Handling records elsewhere', 'due_on' => now()->subDay()]);

        $this->actingAs($finding->assignee)->get('/findings?search=Handling&status=overdue')->assertOk()
            ->assertViewHas('findings', fn ($rows): bool => $rows->modelKeys() === [$finding->id]);
    }

    public function test_course_search_matches_its_own_domain_and_cannot_bypass_draft_visibility(): void
    {
        $staff = User::factory()->assignedTo(Facility::factory()->create(), JobRole::factory()->create())->create();
        $domain = SafetyDomain::factory()->create(['name' => 'Cold chain safety']);
        $qualification = Qualification::factory()->for($domain, 'safetyDomain')->requiringTraining()->create();
        $published = TrainingCourse::factory()->for($qualification)->published()->create();
        TrainingCourse::factory()->for($qualification)->create();
        TrainingCourse::factory()->published()->create();

        $this->actingAs($staff)->get('/training-courses?search=Cold%20chain')->assertOk()
            ->assertViewHas('courses', fn ($rows): bool => $rows->modelKeys() === [$published->id]);
        $this->get('/training-courses?search=Cold%20chain&status=draft')->assertOk()
            ->assertViewHas('courses', fn ($rows): bool => $rows->isEmpty());
    }

    public function test_audit_search_uses_historical_locations_without_bypassing_manager_scope(): void
    {
        $facility = Facility::factory()->create();
        $manager = User::factory()->role(UserRole::FacilityManager)->assignedTo($facility)->create();
        $audit = Audit::factory()->create(['facility_id' => $facility->id, 'region_id' => $facility->region_id, 'snapshot' => ['facility_name' => 'Previous hub name', 'employees' => []]]);
        Audit::factory()->create(['snapshot' => ['facility_name' => 'Previous hub name', 'employees' => []]]);

        $this->actingAs($manager)->get('/audits?search=Previous')->assertOk()
            ->assertViewHas('audits', fn ($rows): bool => $rows->modelKeys() === [$audit->id]);
    }

    public function test_combined_search_and_status_survive_pagination(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        $qualification = Qualification::factory()->create(['name' => 'Handling clearance']);
        $employee = User::factory()->assignedTo(Facility::factory()->create(), JobRole::factory()->create())->create();
        CredentialDocument::factory()->count(16)->for($employee, 'employee')->for($qualification)->create();

        $this->actingAs($admin)->get('/credentials?search=Handling&status=pending')->assertOk()
            ->assertSee('search=Handling&amp;status=pending&amp;page=2', false)->assertSee('16 submissions');
        $this->get('/credentials?search=Handling&status=pending&page=2')->assertOk()
            ->assertViewHas('documents', fn ($rows): bool => $rows->count() === 1 && $rows->total() === 16);
    }

    public function test_report_search_label_and_export_link_follow_the_selected_report(): void
    {
        $officer = User::factory()->role(UserRole::ComplianceOfficer)->create();

        $this->actingAs($officer)->get('/reports?type=findings&q=Handling')->assertOk()
            ->assertSee('Finding title</label>', false)->assertSee('type=findings&amp;q=Handling', false)
            ->assertSee('data-filter-download', false);
        $this->get('/reports?type=training')->assertOk()->assertSee('Employee name</label>', false);
    }

    #[TestWith(['/employees'])]
    #[TestWith(['/admin/users'])]
    #[TestWith(['/credentials'])]
    #[TestWith(['/findings'])]
    #[TestWith(['/training-courses'])]
    #[TestWith(['/audits'])]
    public function test_search_rejects_array_and_excessively_long_input(string $path): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();

        $this->actingAs($admin)->get($path.'?search[]=invalid')->assertSessionHasErrors('search');
        $this->get($path.'?search='.str_repeat('a', 101))->assertSessionHasErrors('search');
    }

    #[TestWith(['/admin/users?role=superuser', 'role'])]
    #[TestWith(['/admin/users?active=maybe', 'active'])]
    #[TestWith(['/credentials?status=unknown', 'status'])]
    #[TestWith(['/training-courses?status=unknown', 'status'])]
    #[TestWith(['/employees?facility_id=invalid', 'facility_id'])]
    public function test_filter_values_are_validated(string $url, string $field): void
    {
        $this->actingAs(User::factory()->role(UserRole::Admin)->create())->get($url)->assertSessionHasErrors($field);
    }
}
