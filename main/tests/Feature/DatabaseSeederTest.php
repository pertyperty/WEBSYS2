<?php

namespace Tests\Feature;

use App\Actions\CreateSyntheticDataset;
use App\Models\CredentialDocument;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use DatabaseMigrations;

    public function test_standard_seeder_populates_over_one_thousand_related_records_without_prompts(): void
    {
        Storage::fake('local');
        $this->freezeTime();

        $this->artisan('migrate:fresh', ['--seed' => true, '--no-interaction' => true])->assertSuccessful();

        $total = collect(CreateSyntheticDataset::TABLES)->sum(fn (string $table): int => DB::table($table)->count());
        $this->assertGreaterThanOrEqual(1000, $total);
        $this->assertDatabaseCount('users', 220);
        $this->assertDatabaseCount('employee_trainings', 144);
        $this->assertDatabaseCount('credential_documents', 270);
        $this->assertDatabaseCount('audits', 18);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertSame(6, User::distinct()->count('role'));
        $this->assertSame(18, User::whereNotNull('facility_id')->distinct()->count('facility_id'));
        $admin = User::where('email', 'admin@checkpoint.example')->firstOrFail();
        $this->assertFalse(Hash::check('password', $admin->password));
        $document = CredentialDocument::firstOrFail();
        Storage::disk('local')->assertExists($document->private_path);
        $this->actingAs($document->employee)->get('/credentials/'.$document->id.'/download')->assertOk()->assertDownload();
    }

    public function test_reseeding_preserves_used_accounts_passwords_and_history(): void
    {
        Storage::fake('local');
        $this->freezeTime();
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@checkpoint.example')->firstOrFail();
        $admin->forceFill(['name' => 'Edited administrator', 'is_active' => false])->save();
        $password = $admin->password;
        $events = DB::table('credential_events')->count();
        $history = DB::table('finding_updates')->count();

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 220);
        $this->assertDatabaseCount('credential_events', $events);
        $this->assertDatabaseCount('finding_updates', $history);
        $this->assertSame('Edited administrator', $admin->fresh()->name);
        $this->assertFalse($admin->fresh()->is_active);
        $this->assertSame($password, $admin->fresh()->password);
        $this->assertCount(1, Storage::disk('local')->allFiles('synthetic'));
    }

    public function test_standard_seeder_skips_synthetic_data_on_an_existing_account_database(): void
    {
        Storage::fake('local');
        $existing = User::factory()->create();

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('credential_documents', 0);
        $this->assertSame($existing->email, User::firstOrFail()->email);
        $this->assertSame([], Storage::disk('local')->allFiles('synthetic'));
    }

    public function test_standard_seeder_never_adds_synthetic_accounts_in_production(): void
    {
        Storage::fake('local');
        $this->app->instance('env', 'production');
        try {
            $this->artisan('db:seed', ['--force' => true, '--no-interaction' => true])->assertSuccessful();
        } finally {
            $this->app->instance('env', 'testing');
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('regions', 18);
        $this->assertDatabaseCount('credential_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('synthetic'));
    }
}
