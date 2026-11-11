<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditFinding;
use App\Models\CredentialDocument;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterfaceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_search_keeps_current_scope_and_preserves_query_on_pagination(): void
    {
        $facility = Facility::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $facility->id]);
        User::factory()->count(16)->for(JobRole::factory())->create(['facility_id' => $facility->id, 'name' => 'Matching employee']);
        User::factory()->for(Facility::factory())->for(JobRole::factory())->create(['name' => 'Matching outside employee']);
        User::factory()->for(JobRole::factory())->create(['facility_id' => $facility->id, 'name' => 'Unrelated person']);

        $this->actingAs($manager)->get('/employees?search=Matching')->assertOk()->assertSee('Matching employee')
            ->assertDontSee('Matching outside employee')->assertDontSee('Unrelated person')->assertSee('search=Matching');
        $this->get('/employees?search='.str_repeat('x', 101))->assertSessionHasErrors('search');
    }

    public function test_admin_can_find_accounts_by_email_without_unrelated_results(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $target = User::factory()->create(['name' => 'Target person', 'email' => 'findme@example.test']);
        User::factory()->create(['name' => 'Unrelated person']);

        $this->actingAs($admin)->get('/admin/users?search=findme%40example.test')->assertOk()
            ->assertSee($target->name)->assertDontSee('Unrelated person');
        $this->get('/admin/users?search[]=invalid')->assertSessionHasErrors('search');
    }

    public function test_staff_overview_links_to_own_next_steps_without_administration(): void
    {
        $employee = User::factory()->for(Facility::factory())->for(JobRole::factory())->create();
        $employee->jobRole->qualifications()->attach(Qualification::factory()->create());

        $this->actingAs($employee)->get('/dashboard')->assertOk()->assertSee('Your next steps')
            ->assertSee(route('employees.show', $employee), false)->assertDontSee('Accounts &amp; access', false)
            ->assertViewHas('ownAssessment', fn (array $result): bool => $result['employee_id'] === $employee->id && $result['status'] === 'not_eligible');
    }

    public function test_form_controls_have_labels_across_current_workflows(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = User::factory()->for(Facility::factory())->for(JobRole::factory())->create();
        $document = CredentialDocument::factory()->create(['employee_id' => $employee->id]);
        $officer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $pages = [
            [$admin, '/admin/users'], [$admin, '/regions/create'], [$admin, '/facilities/create'],
            [$admin, '/job-roles/create'], [$admin, '/safety-domains/create'], [$admin, '/qualifications/create'],
            [$admin, '/training-courses/create'], [$admin, '/employees'], [$admin, '/profile'],
            [$employee, '/credentials/create'], [$employee, '/credentials'], [$officer, '/credentials/'.$document->id],
        ];
        foreach ($pages as [$viewer, $url]) {
            $response = $this->actingAs($viewer)->get($url)->assertOk();
            $this->assertControlsHaveLabels($response->getContent(), $url);
        }
    }

    public function test_public_auth_forms_use_labels_and_product_copy(): void
    {
        foreach (['/login', '/register', '/forgot-password'] as $url) {
            $response = $this->get($url)->assertOk()->assertDontSee('local demo')->assertDontSee('classroom');
            $this->assertControlsHaveLabels($response->getContent(), $url);
        }
    }

    public function test_finding_update_requires_an_explicit_status_and_keeps_the_choice_after_validation(): void
    {
        $finding = AuditFinding::factory()->create(['status' => 'pending_verification']);
        $url = route('findings.show', $finding);
        $response = $this->actingAs($finding->audit->author)->get($url)->assertOk();
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $document->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);
        $selected = $xpath->query('//select[@name="status"]/option[@selected]');

        $this->assertSame(1, $selected->length);
        $this->assertSame('', $selected->item(0)->getAttribute('value'));
        $this->assertTrue($selected->item(0)->hasAttribute('disabled'));

        $this->from($url)->post(route('finding-updates.store', $finding), [
            'status' => 'resolved', 'note' => '',
        ])->assertRedirect($url)->assertSessionHasErrors('note');

        $this->get($url)->assertOk()->assertSee('value="resolved" selected', false);
        $this->assertSame('pending_verification', $finding->fresh()->status);
        $this->assertDatabaseCount('finding_updates', 0);
    }

    public function test_manager_sees_resolved_guidance_instead_of_an_unusable_update_form(): void
    {
        $finding = AuditFinding::factory()->create(['status' => 'resolved']);

        $this->actingAs($finding->assignee)->get(route('findings.show', $finding))->assertOk()
            ->assertSee('A compliance officer must reopen it before further updates.')
            ->assertDontSee('Save update')
            ->assertDontSee('name="status"', false);
    }

    public function test_officer_keeps_the_update_form_to_reopen_resolved_findings(): void
    {
        $finding = AuditFinding::factory()->create(['status' => 'resolved']);

        $this->actingAs($finding->audit->author)->get(route('findings.show', $finding))->assertOk()
            ->assertSee('Reopen')->assertSee('Save update')
            ->assertDontSee('No further action needed');
    }

    private function assertControlsHaveLabels(string $html, string $url): void
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//input[not(@type="hidden") and not(@type="submit") and not(@type="button")]|//select|//textarea') as $field) {
            $id = $field->getAttribute('id');
            $hasLabel = $xpath->query('ancestor::label', $field)->length > 0;
            if ($id !== '') {
                foreach ($xpath->query('//label[@for]') as $label) {
                    $hasLabel = $hasLabel || $label->getAttribute('for') === $id;
                }
            }
            $this->assertTrue($hasLabel, $url.' has an unlabeled '.$field->getAttribute('name').' control.');
        }
    }
}
