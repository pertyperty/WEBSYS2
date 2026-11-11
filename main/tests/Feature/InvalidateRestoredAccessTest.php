<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class InvalidateRestoredAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalidation_clears_all_restored_access_and_preserves_account_data(): void
    {
        config(['session.driver' => 'database']);
        $this->mock(MaintenanceMode::class)->shouldReceive('active')->andReturn(true);
        $user = $this->accountWithAccess();
        $original = $user->fresh()->getRawOriginal();

        $this->artisan('checkpoint:invalidate-restored-access')
            ->expectsOutput('Restored access invalidated. Accounts, passwords, evidence, and history were preserved.')
            ->assertSuccessful();

        foreach (['sessions', 'password_reset_tokens', 'personal_access_tokens'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertNull($user->fresh()->remember_token);
        foreach (['id', 'name', 'email', 'password', 'role', 'is_active', 'verification_reference'] as $attribute) {
            $this->assertSame($original[$attribute], $user->fresh()->getRawOriginal($attribute));
        }
        $this->artisan('checkpoint:invalidate-restored-access')->assertSuccessful();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_live_application_refuses_invalidation_without_changing_access(): void
    {
        config(['session.driver' => 'database']);
        $this->mock(MaintenanceMode::class)->shouldReceive('active')->andReturn(false);
        $user = $this->accountWithAccess();

        $this->artisan('checkpoint:invalidate-restored-access')
            ->expectsOutput('Keep the recovered application in maintenance mode before invalidating restored access.')
            ->assertFailed();

        foreach (['sessions', 'password_reset_tokens', 'personal_access_tokens'] as $table) {
            $this->assertDatabaseCount($table, 1);
        }
        $this->assertSame($user->remember_token, $user->fresh()->remember_token);
    }

    public function test_non_database_session_configuration_refuses_partial_invalidation(): void
    {
        config(['session.driver' => 'redis']);
        $this->mock(MaintenanceMode::class)->shouldReceive('active')->andReturn(true);
        $user = $this->accountWithAccess();

        $this->artisan('checkpoint:invalidate-restored-access')
            ->expectsOutput('This command supports database sessions only. No access records were changed.')
            ->assertFailed();

        foreach (['sessions', 'password_reset_tokens', 'personal_access_tokens'] as $table) {
            $this->assertDatabaseCount($table, 1);
        }
        $this->assertSame($user->remember_token, $user->fresh()->remember_token);
    }

    private function accountWithAccess(): User
    {
        $user = User::factory()->create();
        $user->createToken('Test access', ['verification:read'], now()->addDay());
        Password::broker()->createToken($user);
        DB::table('sessions')->insert(['id' => 'test-restored-session', 'user_id' => $user->id, 'payload' => 'test payload', 'last_activity' => now()->getTimestamp()]);

        return $user;
    }
}
