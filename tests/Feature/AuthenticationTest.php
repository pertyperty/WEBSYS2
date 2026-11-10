<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_pages_render(): void
    {
        foreach (['/login', '/register', '/forgot-password', '/reset-password/example?email=test@example.com'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_registration_cannot_set_privileged_attributes(): void
    {
        $this->post('/register', [
            'name' => 'New Staff', 'email' => 'staff@example.com',
            'password' => 'SafePassword123', 'password_confirmation' => 'SafePassword123',
            'role' => 'admin', 'is_active' => false, 'facility_id' => 999, 'job_role_id' => 999, 'region_id' => 999,
        ])->assertRedirect('/dashboard');

        $user = User::sole();
        $this->assertSame(UserRole::Staff, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->facility_id);
        $this->assertNull($user->job_role_id);
        $this->assertNull($user->region_id);
        $this->assertTrue(Hash::check('SafePassword123', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertRedirect('/awaiting-assignment');
        $this->get('/awaiting-assignment')->assertOk()->assertSee('Awaiting your assignment');
    }

    public function test_registration_rejects_duplicate_email_and_weak_password(): void
    {
        $user = User::factory()->create();
        $this->post('/register', ['name' => 'Staff', 'email' => $user->email, 'password' => 'weak', 'password_confirmation' => 'weak'])
            ->assertSessionHasErrors(['email', 'password']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_login_logout_and_invalid_password(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'incorrect'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_inactive_and_auditor_accounts_cannot_log_in(): void
    {
        foreach ([['is_active' => false], ['role' => UserRole::Auditor]] as $attributes) {
            $user = User::factory()->create($attributes);
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
            $this->assertGuest();
        }
    }

    public function test_existing_session_loses_access_when_account_becomes_inactive_or_auditor(): void
    {
        foreach ([['is_active' => false], ['role' => UserRole::Auditor]] as $attributes) {
            $user = User::factory()->create($attributes);
            $this->actingAs($user)->get('/profile')->assertRedirect('/login');
            $this->assertGuest();
            $this->actingAs($user)->put('/user/profile-information', ['name' => 'Changed', 'email' => $user->email])->assertRedirect('/login');
            $this->assertNotSame('Changed', $user->fresh()->name);
        }
    }

    public function test_profile_changes_cannot_change_assignments_or_role(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/profile')->assertOk();
        $this->put('/user/profile-information', [
            'name' => 'Changed Name', 'email' => 'changed@example.com',
            'role' => 'admin', 'facility_id' => 999, 'job_role_id' => 999, 'is_active' => false,
        ])->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame('Changed Name', $user->name);
        $this->assertSame(UserRole::Staff, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->facility_id);
    }

    public function test_profile_escapes_user_supplied_names(): void
    {
        $user = User::factory()->create(['name' => '<script>alert(1)</script>']);

        $this->actingAs($user)->get('/profile')->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create();
        $input = ['current_password' => 'wrong', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'];
        $this->actingAs($user)->put('/user/password', $input)->assertSessionHasErrorsIn('updatePassword', 'current_password');
        $input['current_password'] = 'password';
        $this->put('/user/password', $input)->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
    }

    public function test_reset_link_and_token_reset_the_password(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $this->post('/reset-password', [
                'token' => $notification->token, 'email' => $user->email,
                'password' => 'ResetPassword123', 'password_confirmation' => 'ResetPassword123',
            ])->assertSessionHasNoErrors()->assertRedirect('/login');

            return true;
        });
        $this->assertTrue(Hash::check('ResetPassword123', $user->fresh()->password));
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => 'limited@example.com', 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => 'limited@example.com', 'password' => 'wrong'])->assertTooManyRequests();
    }
}
