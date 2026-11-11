<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Enums\UserRole;
use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\CredentialDocument;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuditWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_officer_captures_immutable_readiness_without_private_content(): void
    {
        $officer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $employee = User::factory()->for(Facility::factory())->for(JobRole::factory())->create();
        $qualification = Qualification::factory()->create();
        $employee->jobRole->qualifications()->attach($qualification);
        $document = CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id]);

        $response = $this->actingAs($officer)->post('/audits', ['title' => 'November readiness', 'region_id' => $employee->facility->region_id, 'facility_id' => $employee->facility_id, 'snapshot' => ['status' => 'eligible'], 'author_id' => $employee->id]);

        $audit = Audit::firstOrFail();
        $response->assertRedirect(route('audits.show', $audit));
        $original = $audit->snapshot;
        $this->assertSame($officer->id, $audit->author_id);
        $this->assertSame('not_eligible', $original['employees'][0]['status']);
        $this->assertStringNotContainsString($document->private_path, json_encode($original));
        $document->forceFill(['status' => CredentialStatus::Approved])->save();
        $employee->jobRole->qualifications()->detach();
        $this->get('/audits/'.$audit->id)->assertOk()->assertSee('November readiness');
        $this->assertSame($original, $audit->fresh()->snapshot);
        $this->put('/audits/'.$audit->id, ['title' => 'Changed'])->assertMethodNotAllowed();
    }

    public function test_scope_distinguishes_regional_snapshot_from_facility_corrective_work(): void
    {
        $audit = Audit::factory()->create();
        $regional = Audit::factory()->create(['region_id' => $audit->region_id, 'facility_id' => null]);
        $finding = AuditFinding::factory()->create(['audit_id' => $regional->id, 'facility_id' => $audit->facility_id, 'region_id' => $audit->region_id]);
        $manager = $finding->assignee;
        $other = Audit::factory()->create();
        $this->actingAs($manager)->get('/audits/'.$audit->id)->assertOk();
        $this->get('/audits/'.$regional->id)->assertNotFound();
        $this->get('/audits/'.$other->id)->assertNotFound();
        $this->get('/findings/'.$finding->id)->assertOk();
        $director = User::factory()->create(['role' => UserRole::RegionalDirector, 'region_id' => $audit->region_id]);
        $this->actingAs($director)->get('/audits/'.$regional->id)->assertOk();
        $this->get('/audits/'.$other->id)->assertNotFound();
        $director->forceFill(['region_id' => null])->save();
        $this->get('/audits/'.$regional->id)->assertNotFound();
    }

    public function test_findings_require_an_active_manager_inside_the_audit_scope(): void
    {
        $audit = Audit::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $audit->facility_id]);
        $outside = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => Facility::factory()]);
        $data = ['title' => 'Renew missing qualifications', 'description' => 'Arrange renewal and submit evidence.', 'facility_id' => $audit->facility_id, 'assignee_id' => $outside->id, 'due_on' => now('Asia/Manila')->addDays(3)->toDateString()];
        $this->actingAs($audit->author)->post('/audits/'.$audit->id.'/findings', $data)->assertSessionHasErrors('assignee_id');
        $this->assertDatabaseCount('audit_findings', 0);
        $data['assignee_id'] = $manager->id;

        $response = $this->post('/audits/'.$audit->id.'/findings', $data);

        $finding = AuditFinding::firstOrFail();
        $response->assertRedirect(route('findings.show', $finding));
        $this->assertSame($manager->id, $finding->assignee_id);
        $this->assertSame('open', $finding->status);
    }

    public function test_corrective_work_keeps_activity_and_requires_separate_officer_verification(): void
    {
        $finding = AuditFinding::factory()->create();
        $this->actingAs($finding->assignee)->post('/findings/'.$finding->id.'/updates', ['status' => 'resolved', 'note' => 'Self approved'])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('finding_updates', 0);
        $this->post('/findings/'.$finding->id.'/updates', ['status' => 'pending_verification', 'note' => 'Renewal arranged.'])->assertRedirect();
        $this->assertSame('pending_verification', $finding->fresh()->status);
        $this->actingAs($finding->audit->author)->post('/findings/'.$finding->id.'/updates', ['status' => 'resolved', 'note' => 'Verified the corrective work.'])->assertRedirect();
        $this->assertSame('resolved', $finding->fresh()->status);
        $this->assertNotNull($finding->fresh()->resolved_at);
        $this->assertSame(2, $finding->updates()->count());
        $this->actingAs($finding->assignee)->post('/findings/'.$finding->id.'/updates', ['status' => 'in_progress', 'note' => 'Change history'])->assertSessionHasErrors('status');
        $this->assertSame(2, $finding->updates()->count());
        $this->actingAs($finding->audit->author)->post('/findings/'.$finding->id.'/updates', ['status' => 'open', 'note' => 'New issue needs work.'])->assertRedirect();
        $this->assertNull($finding->fresh()->resolved_at);
        $this->assertSame(3, $finding->updates()->count());
    }

    public function test_other_managers_cannot_write_and_transfer_revokes_assignee_write_access(): void
    {
        $finding = AuditFinding::factory()->create();
        $peer = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $finding->facility_id]);
        $this->actingAs($peer)->post('/findings/'.$finding->id.'/updates', ['status' => 'in_progress', 'note' => 'Unauthorized'])->assertForbidden();
        $finding->assignee->forceFill(['facility_id' => Facility::factory()->create()->id])->save();
        $this->actingAs($finding->assignee->fresh())->post('/findings/'.$finding->id.'/updates', ['status' => 'in_progress', 'note' => 'Unauthorized'])->assertNotFound();
        $this->assertDatabaseCount('finding_updates', 0);
    }

    public function test_audit_permissions_and_invalid_scopes_are_enforced(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertSame($role === UserRole::ComplianceOfficer, Gate::forUser($user)->allows('conduct-audits'));
            $this->assertSame(in_array($role, [UserRole::Admin, UserRole::ComplianceOfficer, UserRole::FacilityManager, UserRole::RegionalDirector]), Gate::forUser($user)->allows('view-audits'));
        }
        $this->get('/audits')->assertRedirect('/login');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->get('/audits/create')->assertForbidden();
        $this->post('/audits', ['title' => 'Forbidden'])->assertForbidden();
        $officer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $facility = Facility::factory()->create();
        $other = Facility::factory()->create();
        $this->actingAs($officer)->get('/audits/create')->assertOk();
        $this->post('/audits', ['title' => 'Invalid scope', 'region_id' => $facility->region_id, 'facility_id' => $other->id])->assertSessionHasErrors('facility_id');
        $this->post('/audits', ['title' => 'Empty scope', 'region_id' => $facility->region_id])->assertSessionHasErrors('region_id');
        $this->assertDatabaseCount('audits', 0);
    }

    public function test_officer_can_reassign_work_with_a_retained_assignment_note(): void
    {
        $finding = AuditFinding::factory()->create();
        $replacement = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $finding->facility_id]);
        $this->actingAs($finding->assignee)->post('/findings/'.$finding->id.'/updates', ['status' => 'in_progress', 'note' => 'Attempt reassignment', 'assignee_id' => $replacement->id])->assertSessionHasErrors('assignee_id');
        $this->assertDatabaseCount('finding_updates', 0);
        $this->actingAs($finding->audit->author)->post('/findings/'.$finding->id.'/updates', ['status' => 'in_progress', 'note' => 'Covering a transfer.', 'assignee_id' => $replacement->id])->assertRedirect();
        $this->assertSame($replacement->id, $finding->fresh()->assignee_id);
        $this->assertSame($replacement->name, $finding->updates()->first()->assignee_name);
        $this->actingAs($replacement)->post('/findings/'.$finding->id.'/updates', ['status' => 'pending_verification', 'note' => 'Work finished.'])->assertRedirect();
    }

    public function test_officer_cannot_verify_their_own_latest_corrective_submission(): void
    {
        $finding = AuditFinding::factory()->create();
        $officer = $finding->audit->author;
        $this->actingAs($officer)->post('/findings/'.$finding->id.'/updates', ['status' => 'in_progress', 'note' => 'Started.'])->assertRedirect();
        $this->post('/findings/'.$finding->id.'/updates', ['status' => 'pending_verification', 'note' => 'Ready for another officer.'])->assertRedirect();
        $this->post('/findings/'.$finding->id.'/updates', ['status' => 'resolved', 'note' => 'Self verification.'])->assertSessionHasErrors('status');
        $this->assertSame('pending_verification', $finding->fresh()->status);
        $otherOfficer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $this->actingAs($otherOfficer)->post('/findings/'.$finding->id.'/updates', ['status' => 'resolved', 'note' => 'Independently verified.'])->assertRedirect();
        $this->assertSame('resolved', $finding->fresh()->status);
    }

    public function test_finding_notes_and_titles_are_escaped_and_overdue_filter_uses_manila_dates(): void
    {
        $finding = AuditFinding::factory()->create(['title' => '<script>alert(1)</script>', 'due_on' => now('Asia/Manila')->subDay()->toDateString()]);
        $this->actingAs($finding->assignee)->get('/findings?status=overdue')->assertOk()->assertSee($finding->title)->assertDontSee($finding->title, false);
        $this->post('/findings/'.$finding->id.'/updates', ['status' => 'in_progress', 'note' => '<script>alert(2)</script>'])->assertRedirect();
        $this->get('/findings/'.$finding->id)->assertOk()->assertSee('<script>alert(2)</script>')->assertDontSee('<script>alert(2)</script>', false);
    }
}
