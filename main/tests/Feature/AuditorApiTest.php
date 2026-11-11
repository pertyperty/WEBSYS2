<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Enums\UserRole;
use App\Models\CredentialDocument;
use App\Models\Region;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AuditorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_scoped_request_returns_only_the_document_contract(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        $document = CredentialDocument::factory()->create(['status' => CredentialStatus::Approved]);
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => $document->employee->facility->region_id]);
        $token = $this->tokenFor($auditor);

        $response = $this->verify($document->reference, $token);

        $response->assertOk()->assertExactJson(['data' => [
            'reference' => $document->reference, 'employee_reference' => $document->employee->verification_reference,
            'qualification' => $document->qualification->name, 'review_status' => 'approved', 'validity' => 'valid',
            'issued_on' => '2026-11-11', 'expires_on' => '2027-11-11', 'reviewed_at' => null, 'evaluated_on' => '2026-11-11',
        ]]);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString($document->employee->name, $response->getContent());
        $this->assertStringNotContainsString($document->private_path, $response->getContent());
        $this->assertNotNull(PersonalAccessToken::firstOrFail()->last_used_at);
        $this->assertDatabaseCount('compliance_assessments', 0);
    }

    public function test_missing_invalid_expired_and_revoked_tokens_are_unauthenticated(): void
    {
        $this->freezeTime();
        $document = CredentialDocument::factory()->create();
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => $document->employee->facility->region_id]);
        $token = $this->tokenFor($auditor);
        $this->verify($document->reference)->assertUnauthorized();
        $this->verify($document->reference, 'not-a-valid-token')->assertUnauthorized();
        $auditor->tokens()->update(['expires_at' => now()->subSecond()]);
        $this->verify($document->reference, $token)->assertUnauthorized();
        $auditor->tokens()->delete();
        $this->verify($document->reference, $token)->assertUnauthorized();
        $this->get('/api/v1/verifications/'.$document->reference)->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
    }

    #[TestWith(['admin'])]
    #[TestWith(['compliance_officer'])]
    #[TestWith(['regional_director'])]
    #[TestWith(['facility_manager'])]
    #[TestWith(['staff'])]
    public function test_other_roles_cannot_verify_even_with_a_real_token(string $role): void
    {
        $document = CredentialDocument::factory()->create();
        $user = User::factory()->create(['role' => $role, 'region_id' => $document->employee->facility->region_id]);

        $this->verify($document->reference, $this->tokenFor($user))->assertForbidden();
    }

    #[TestWith([[]])]
    #[TestWith([['*']])]
    #[TestWith([['other:read']])]
    public function test_explicit_verification_ability_is_required(array $abilities): void
    {
        $document = CredentialDocument::factory()->create();
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => $document->employee->facility->region_id]);

        $this->verify($document->reference, $this->tokenFor($auditor, $abilities))->assertForbidden();
    }

    public function test_browser_session_alone_does_not_authenticate_the_api(): void
    {
        $document = CredentialDocument::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->getJson('/api/v1/verifications/'.$document->reference)->assertUnauthorized();
    }

    public function test_scope_uses_current_employee_assignment_and_hides_other_region_references(): void
    {
        $document = CredentialDocument::factory()->create();
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => $document->employee->facility->region_id]);
        $token = $this->tokenFor($auditor);
        $outside = CredentialDocument::factory()->create();
        $this->verify($outside->reference, $token)->assertNotFound();
        $this->verify('unknown-reference', $token)->assertNotFound();
        $this->verify($document->reference, $token)->assertOk();
        $document->employee->forceFill(['facility_id' => $outside->employee->facility_id])->save();
        $this->verify($document->reference, $token)->assertNotFound();
        $newAuditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => $outside->employee->facility->region_id]);
        $this->verify($document->reference, $this->tokenFor($newAuditor))->assertOk();
    }

    public function test_inactive_employees_and_facilities_are_not_verifiable(): void
    {
        $document = CredentialDocument::factory()->create();
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => $document->employee->facility->region_id]);
        $token = $this->tokenFor($auditor);
        $document->employee->forceFill(['is_active' => false])->save();
        $this->verify($document->reference, $token)->assertNotFound();
        $document->employee->forceFill(['is_active' => true])->save();
        $document->employee->facility->update(['is_active' => false]);
        $this->verify($document->reference, $token)->assertNotFound();
    }

    public function test_inactive_auditors_and_changed_or_unassigned_regions_cannot_use_old_tokens(): void
    {
        $document = CredentialDocument::factory()->create();
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => $document->employee->facility->region_id]);
        $token = $this->tokenFor($auditor);
        $auditor->forceFill(['is_active' => false])->save();
        $this->verify($document->reference, $token)->assertForbidden();
        $auditor->forceFill(['is_active' => true, 'region_id' => Region::factory()->create()->id])->save();
        $this->verify($document->reference, $token)->assertForbidden();
        $auditor->forceFill(['region_id' => null])->save();
        $this->verify($document->reference, $token)->assertForbidden();
    }

    public function test_tokens_do_not_authenticate_browser_or_private_download_routes(): void
    {
        $document = CredentialDocument::factory()->create();
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => $document->employee->facility->region_id]);
        $token = $this->tokenFor($auditor);
        $this->withToken($token)->get('/dashboard')->assertRedirect('/login');
        $this->get('/credentials/'.$document->id.'/download')->assertRedirect('/login');
    }

    public function test_rate_limit_is_shared_by_an_auditors_tokens(): void
    {
        $this->freezeTime();
        $document = CredentialDocument::factory()->create();
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => $document->employee->facility->region_id]);
        $token = $this->tokenFor($auditor);
        for ($request = 0; $request < 60; $request++) {
            $this->verify($document->reference, $token)->assertOk();
        }
        $this->verify($document->reference, $this->tokenFor($auditor))->assertTooManyRequests()->assertHeader('Retry-After');
        $other = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => $auditor->region_id]);
        $this->verify($document->reference, $this->tokenFor($other))->assertOk();
    }

    public function test_non_expiring_and_exactly_expired_tokens_are_not_accepted(): void
    {
        $this->freezeTime();
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => Region::factory()]);
        $token = $auditor->createToken('No expiry', ['verification:read']);
        $token->accessToken->forceFill(['region_id' => $auditor->region_id])->save();
        $this->verify('unknown', $token->plainTextToken)->assertUnauthorized();
        $token->accessToken->forceFill(['expires_at' => now()])->save();
        $this->verify('unknown', $token->plainTextToken)->assertUnauthorized();
    }

    public function test_anonymous_traffic_is_limited_before_token_authentication(): void
    {
        $this->freezeTime();
        for ($request = 0; $request < 120; $request++) {
            $this->verify('unknown')->assertUnauthorized();
        }
        $this->verify('unknown')->assertTooManyRequests()->assertHeader('Retry-After');
    }

    /** @param list<string> $abilities */
    private function tokenFor(User $user, array $abilities = ['verification:read']): string
    {
        $token = $user->createToken('Test verification client', $abilities, now()->addDay());
        $token->accessToken->forceFill(['region_id' => $user->region_id])->save();

        return $token->plainTextToken;
    }

    private function verify(string $reference, ?string $token = null): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->getJson('/api/v1/verifications/'.$reference, $token ? ['Authorization' => 'Bearer '.$token] : []);
    }
}
