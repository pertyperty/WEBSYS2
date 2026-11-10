<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdministratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_interactive_setup_creates_an_administrator_with_a_hashed_password(): void
    {
        $this->artisan('checkpoint:create-admin')
            ->expectsQuestion('Administrator name', 'Demo Administrator')
            ->expectsQuestion('Administrator email', 'ADMIN@example.com')
            ->expectsQuestion('Password (12+ characters with letters and numbers)', 'SetupPassword123')
            ->expectsQuestion('Confirm password', 'SetupPassword123')
            ->expectsOutput('Administrator created. Sign in using the credentials you entered.')
            ->assertSuccessful();

        $admin = User::sole();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame('admin@example.com', $admin->email);
        $this->assertTrue(Hash::check('SetupPassword123', $admin->password));
    }

    public function test_setup_does_not_promote_or_overwrite_an_existing_account(): void
    {
        $staff = User::factory()->create(['email' => 'staff@example.com']);

        $this->artisan('checkpoint:create-admin')
            ->expectsQuestion('Administrator name', 'Demo Administrator')
            ->expectsQuestion('Administrator email', $staff->email)
            ->expectsQuestion('Password (12+ characters with letters and numbers)', 'SetupPassword123')
            ->expectsQuestion('Confirm password', 'SetupPassword123')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame(UserRole::Staff, $staff->fresh()->role);
        $this->assertTrue(Hash::check('password', $staff->fresh()->password));
    }
}
