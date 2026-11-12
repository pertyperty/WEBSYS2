<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Region;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AuditorTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_issues_a_region_bound_expiring_token_and_plaintext_is_not_retained(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => Region::factory()]);

        $response = $this->actingAs($admin)->post('/admin/auditor-tokens', [
            'auditor_id' => $auditor->id, 'name' => 'November audit client', 'expires_on' => '2026-12-11', 'abilities' => ['*'], 'region_id' => 999,
        ]);

        $response->assertOk()->assertViewIs('admin.auditor-tokens.created')->assertSessionHasNoErrors();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $token = PersonalAccessToken::sole();
        $plain = $response->viewData('plainTextToken');
        $this->assertSame(hash('sha256', explode('|', $plain, 2)[1]), $token->token);
        $this->assertSame(['verification:read', 'compliance:read'], $token->abilities);
        $this->assertSame($auditor->region_id, $token->region_id);
        $this->assertSame('2026-12-11 16:00:00', $token->expires_at->utc()->format('Y-m-d H:i:s'));
        $this->assertStringNotContainsString($plain, serialize(session()->all()));
        $this->get('/admin/auditor-tokens')->assertOk()->assertSee('November audit client')->assertDontSee($plain)->assertDontSee($token->token);
    }

    #[TestWith(['compliance_officer'])]
    #[TestWith(['regional_director'])]
    #[TestWith(['facility_manager'])]
    #[TestWith(['staff'])]
    public function test_only_admin_can_issue_or_revoke_tokens(string $role): void
    {
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => Region::factory()]);
        $existing = $auditor->createToken('Existing', ['verification:read'], now()->addDay());
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user)->get('/admin/auditor-tokens')->assertForbidden();
        $this->post('/admin/auditor-tokens', ['auditor_id' => $auditor->id, 'name' => 'Forbidden', 'expires_on' => now('Asia/Manila')->toDateString()])->assertForbidden();
        $this->delete('/admin/auditor-tokens/'.$existing->accessToken->id)->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_issuance_requires_an_active_auditor_with_an_active_region(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => Region::factory()]);
        $data = ['auditor_id' => $auditor->id, 'name' => 'Audit client', 'expires_on' => now('Asia/Manila')->addDay()->toDateString()];
        $auditor->forceFill(['is_active' => false])->save();
        $this->actingAs($admin)->post('/admin/auditor-tokens', $data)->assertSessionHasErrors('auditor_id');
        $auditor->forceFill(['is_active' => true, 'region_id' => null])->save();
        $this->post('/admin/auditor-tokens', $data)->assertSessionHasErrors('auditor_id');
        $region = Region::factory()->create(['is_active' => false]);
        $auditor->forceFill(['region_id' => $region->id])->save();
        $this->post('/admin/auditor-tokens', $data)->assertSessionHasErrors('auditor_id');
        $data['auditor_id'] = $admin->id;
        $this->post('/admin/auditor-tokens', $data)->assertSessionHasErrors('auditor_id');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[TestWith(['2026-11-10'])]
    #[TestWith(['2027-02-10'])]
    #[TestWith(['not-a-date'])]
    public function test_expiry_must_be_a_calendar_date_from_today_through_90_days(string $expiry): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => Region::factory()]);
        $this->actingAs($admin)->post('/admin/auditor-tokens', ['auditor_id' => $auditor->id, 'name' => 'Client', 'expires_on' => $expiry])->assertSessionHasErrors('expires_on');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_revocation_removes_the_token_and_future_requests_are_unauthenticated(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => Region::factory()]);
        $token = $auditor->createToken('Client', ['verification:read'], now()->addDay());
        $token->accessToken->forceFill(['region_id' => $auditor->region_id])->save();

        $this->actingAs($admin)->delete('/admin/auditor-tokens/'.$token->accessToken->id)->assertRedirect('/admin/auditor-tokens');

        $this->assertModelMissing($token->accessToken);
        $this->app['auth']->forgetGuards();
        $this->withToken($token->plainTextToken)->getJson('/api/v1/verifications/unknown')->assertUnauthorized();
    }

    #[TestWith(['region'])]
    #[TestWith(['role'])]
    #[TestWith(['deactivate'])]
    public function test_assignment_changes_revoke_old_tokens(string $change): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => Region::factory()]);
        $auditor->createToken('Client', ['verification:read'], now()->addDay());
        $data = ['role' => 'auditor', 'is_active' => true, 'region_id' => $auditor->region_id];
        if ($change === 'region') {
            $data['region_id'] = Region::factory()->create()->id;
        } elseif ($change === 'role') {
            $data['role'] = 'compliance_officer';
            $data['region_id'] = null;
        } else {
            $data['is_active'] = false;
        }

        $this->actingAs($admin)->patch('/admin/users/'.$auditor->id.'/assignment', $data)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_saving_the_same_assignment_keeps_tokens_and_references_are_server_generated(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => Region::factory()]);
        $reference = $auditor->verification_reference;
        $auditor->createToken('Client', ['verification:read'], now()->addDay());
        $this->actingAs($admin)->patch('/admin/users/'.$auditor->id.'/assignment', ['role' => 'auditor', 'is_active' => '1', 'region_id' => (string) $auditor->region_id, 'verification_reference' => 'forged'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertSame($reference, $auditor->fresh()->verification_reference);
        $this->assertArrayNotHasKey('verification_reference', $auditor->toArray());
    }

    public function test_issued_token_is_valid_through_its_manila_expiry_date(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => Region::factory()]);
        $response = $this->actingAs($admin)->post('/admin/auditor-tokens', ['auditor_id' => $auditor->id, 'name' => 'One day', 'expires_on' => '2026-11-11'])->assertOk();
        $plain = $response->viewData('plainTextToken');
        $this->travelTo(CarbonImmutable::parse('2026-11-11 23:59:59.999999', 'Asia/Manila'));
        $this->app['auth']->forgetGuards();
        $this->withToken($plain)->getJson('/api/v1/verifications/unknown')->assertNotFound();
        $this->travelTo(CarbonImmutable::parse('2026-11-12 00:00:00', 'Asia/Manila'));
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/verifications/unknown')->assertUnauthorized();
    }

    public function test_token_name_and_required_fields_are_validated_and_names_are_escaped(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => Region::factory()]);
        $this->actingAs($admin)->post('/admin/auditor-tokens', [])->assertSessionHasErrors(['auditor_id', 'name', 'expires_on']);
        $this->post('/admin/auditor-tokens', ['auditor_id' => $auditor->id, 'name' => str_repeat('x', 101), 'expires_on' => now('Asia/Manila')->toDateString()])->assertSessionHasErrors('name');
        $this->post('/admin/auditor-tokens', ['auditor_id' => $auditor->id, 'name' => '<script>alert(1)</script>', 'expires_on' => now('Asia/Manila')->toDateString()])->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/admin/auditor-tokens')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}
