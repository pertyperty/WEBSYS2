<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Enums\UserRole;
use App\Models\CredentialDocument;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiryNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_warning_and_expiry_are_delivered_once_to_staff_and_current_managers(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        $manager = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $employee->facility_id]);
        $other = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => Facility::factory()]);
        $document = CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'expires_at' => '2026-12-11']);

        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();

        $this->assertSame(1, $employee->notifications()->count());
        $this->assertSame(1, $manager->notifications()->count());
        $this->assertSame(0, $other->notifications()->count());
        $this->assertSame('expiring_soon', $employee->notifications()->first()->data['event']);
        $this->travelTo(CarbonImmutable::parse('2026-12-11 23:59', 'Asia/Manila'));
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->assertSame(1, $employee->notifications()->count());
        $this->travelTo(CarbonImmutable::parse('2026-12-12 00:00', 'Asia/Manila'));
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->assertSame(2, $employee->notifications()->count());
        $this->assertSame(2, $manager->notifications()->count());
        $this->assertSame($document->id, $employee->notifications()->where('data->event', 'expired')->first()->data['document_id']);
    }

    public function test_pending_renewal_keeps_warning_and_approved_renewal_marks_old_alert_as_history(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'expires_at' => '2026-11-20']);
        $renewal = CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'issued_at' => '2026-11-11', 'expires_at' => '2027-11-11']);
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->actingAs($employee)->get('/notifications')->assertOk()->assertSee('Expiring soon')->assertDontSee('No longer current');

        $renewal->forceFill(['status' => CredentialStatus::Approved])->save();

        $this->get('/notifications')->assertOk()->assertSee('No longer current');
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->assertSame(1, $employee->notifications()->count());
    }

    public function test_removed_requirement_inactive_staff_and_non_required_evidence_do_not_send_alerts(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'expires_at' => '2026-11-20']);
        $employee->forceFill(['is_active' => false])->save();
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 0);
        $employee->forceFill(['is_active' => true])->save();
        $employee->jobRole->qualifications()->detach();
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_notification_read_controls_are_owned_and_transfers_hide_old_manager_alerts(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        [$employee, $qualification] = $this->employeeWithRequirement();
        $manager = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $employee->facility_id]);
        CredentialDocument::factory()->create(['employee_id' => $employee->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'expires_at' => '2026-11-20']);
        $this->artisan('checkpoint:reconcile-compliance')->assertSuccessful();
        $staffAlert = $employee->notifications()->firstOrFail();
        $managerAlert = $manager->notifications()->firstOrFail();
        $this->actingAs($manager)->patch('/notifications/'.$staffAlert->id)->assertNotFound();
        $this->get('/notifications')->assertOk()->assertSee($employee->name);
        $employee->forceFill(['facility_id' => Facility::factory()->create()->id])->save();

        $this->get('/notifications')->assertOk()->assertDontSee($employee->name);
        $this->patch('/notifications/'.$managerAlert->id)->assertNotFound();
        $this->actingAs($employee)->patch('/notifications/'.$staffAlert->id)->assertRedirect();
        $this->assertNotNull($staffAlert->fresh()->read_at);
        $this->get('/notifications?unread=1')->assertOk()->assertDontSee($qualification->name);
        $this->post('/notifications/read')->assertRedirect();
        $this->assertNull($managerAlert->fresh()->read_at);
    }

    public function test_unassigned_staff_cannot_access_notifications(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/notifications')->assertRedirect('/awaiting-assignment');
    }

    /** @return array{User, Qualification} */
    private function employeeWithRequirement(): array
    {
        $employee = User::factory()->for(Facility::factory())->for(JobRole::factory())->create();
        $qualification = Qualification::factory()->create();
        $employee->jobRole->qualifications()->attach($qualification);

        return [$employee, $qualification];
    }
}
