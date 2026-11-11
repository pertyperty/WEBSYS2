<?php

namespace Tests\Feature;

use App\Actions\AssessEmployeeCompliance;
use App\Actions\CaptureAudit;
use App\Actions\CreateSyntheticDataset;
use App\Enums\UserRole;
use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\CredentialDocument;
use App\Models\JobRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PhilippineOrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SyntheticDatasetTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_a_complete_relationship_aware_dataset_with_private_working_evidence(): void
    {
        Storage::fake('local');
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        $this->seed(PhilippineOrganizationSeeder::class);

        $this->artisan('checkpoint:seed-synthetic')
            ->expectsQuestion('Synthetic account password (12+ characters with letters and numbers)', 'SyntheticPassword123')
            ->expectsQuestion('Confirm password', 'SyntheticPassword123')
            ->expectsOutputToContain('synthetic records. Existing records were preserved.')
            ->assertSuccessful();

        foreach (['users' => 220, 'safety_domains' => 1, 'job_roles' => 2, 'qualifications' => 2, 'job_role_qualification' => 2, 'training_courses' => 3, 'employee_trainings' => 144, 'credential_documents' => 270, 'credential_events' => 468, 'employee_qualification' => 144, 'compliance_assessments' => 164, 'audits' => 18, 'audit_findings' => 72, 'finding_updates' => 108, 'notifications' => 72, 'personal_access_tokens' => 0] as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertSame(6, User::distinct()->count('role'));
        $this->assertSame(18, User::where('role', UserRole::FacilityManager)->distinct()->count('facility_id'));
        $this->assertSame(18, User::where('role', UserRole::Auditor)->distinct()->count('region_id'));
        $this->assertTrue(Hash::check('SyntheticPassword123', User::where('email', 'admin@checkpoint.example')->firstOrFail()->password));
        $this->assertSame(220, User::whereNotNull('verification_reference')->distinct()->count('verification_reference'));
        $assessment = $this->app->make(AssessEmployeeCompliance::class);
        foreach (['current' => 'eligible', 'expiring' => 'eligible', 'expired' => 'not_eligible', 'pending' => 'not_eligible', 'rejected' => 'not_eligible', 'missing' => 'not_eligible', 'incomplete' => 'not_eligible', 'renewal' => 'eligible', 'observer' => 'not_assessable'] as $scenario => $status) {
            $employee = User::where('email', 'staff.01.'.$scenario.'@checkpoint.example')->firstOrFail();
            $this->assertSame($status, $assessment->handle($employee)['status'], $scenario);
        }
        $renewal = User::where('email', 'staff.01.renewal@checkpoint.example')->firstOrFail();
        $this->assertSame(3, $renewal->credentialDocuments()->count());
        $this->assertSame(2, $renewal->qualifications()->count());
        $document = CredentialDocument::firstOrFail();
        Storage::disk('local')->assertExists($document->private_path);
        $this->assertSame('image/png', (new \finfo(FILEINFO_MIME_TYPE))->buffer(Storage::disk('local')->get($document->private_path)));
        $this->assertCount(1, Storage::disk('local')->allFiles('synthetic'));
        $this->actingAs($document->employee)->get('/credentials/'.$document->id.'/download')->assertOk()->assertDownload();
        $resolved = AuditFinding::where('status', 'resolved')->firstOrFail();
        $this->assertNotSame($resolved->assignee_id, $resolved->updates()->reorder()->latest('id')->firstOrFail()->author_id);
        $this->assertCount(9, Audit::firstOrFail()->snapshot['employees']);
    }

    public function test_existing_records_and_edits_survive_an_initial_run_and_a_refused_repeat(): void
    {
        Storage::fake('local');
        $this->seed(PhilippineOrganizationSeeder::class);
        $existing = User::factory()->create(['name' => 'Existing employee']);
        $dataset = $this->app->make(CreateSyntheticDataset::class);
        $dataset->handle('SyntheticPassword123');
        $admin = User::where('email', 'admin@checkpoint.example')->firstOrFail();
        $admin->forceFill(['name' => 'Edited name', 'is_active' => false])->save();
        $before = DB::table('users')->count();

        try {
            $dataset->handle('DifferentPassword456');
            $this->fail('A repeat must not overwrite accounts, dates, or review history.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already exist', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', $before);
        $this->assertSame('Existing employee', $existing->fresh()->name);
        $this->assertSame('Edited name', $admin->fresh()->name);
        $this->assertFalse($admin->fresh()->is_active);
        $this->assertTrue(Hash::check('SyntheticPassword123', $admin->fresh()->password));
        $this->assertCount(1, Storage::disk('local')->allFiles('synthetic'));
    }

    public function test_reserved_address_or_catalog_conflicts_are_rejected_without_promoting_accounts(): void
    {
        Storage::fake('local');
        $existing = User::factory()->create(['email' => 'admin@checkpoint.example']);

        try {
            $this->app->make(CreateSyntheticDataset::class)->handle('SyntheticPassword123');
            $this->fail('Reserved addresses must not be promoted or overwritten.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already exist', $exception->getMessage());
        }

        $this->assertSame(UserRole::Staff, $existing->fresh()->role);
        $this->assertDatabaseCount('users', 1);
        $existing->delete();
        $job = JobRole::factory()->create(['name' => 'Operations Observer']);
        try {
            $this->app->make(CreateSyntheticDataset::class)->handle('SyntheticPassword123');
            $this->fail('Existing catalog names must not be reused or changed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already exist', $exception->getMessage());
        }
        $this->assertModelExists($job);
        $this->assertDatabaseCount('users', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_missing_organization_prevents_partial_data_creation(): void
    {
        Storage::fake('local');

        try {
            $this->app->make(CreateSyntheticDataset::class)->handle('SyntheticPassword123');
            $this->fail('All regional facilities must be available first.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('18 regions', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('safety_domains', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_late_failure_rolls_back_all_database_rows_and_removes_only_the_new_fixture(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('credentials/existing.txt', 'Preserve this file.');
        $this->seed(PhilippineOrganizationSeeder::class);
        $capture = Mockery::mock(CaptureAudit::class);
        $capture->shouldReceive('handle')->once()->andThrow(new RuntimeException('Capture failed.'));
        $this->app->instance(CaptureAudit::class, $capture);

        try {
            $this->app->make(CreateSyntheticDataset::class)->handle('SyntheticPassword123');
            $this->fail('A late failure must roll back the dataset.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Capture failed.', $exception->getMessage());
        }

        foreach (CreateSyntheticDataset::TABLES as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertDatabaseCount('regions', 18);
        $this->assertSame([], Storage::disk('local')->allFiles('synthetic'));
        Storage::disk('local')->assertExists('credentials/existing.txt');
    }

    public function test_command_refuses_weak_passwords_and_noninteractive_execution(): void
    {
        $this->artisan('checkpoint:seed-synthetic')
            ->expectsQuestion('Synthetic account password (12+ characters with letters and numbers)', 'weak')
            ->expectsQuestion('Confirm password', 'weak')
            ->assertFailed();
        $this->artisan('checkpoint:seed-synthetic', ['--no-interaction' => true])
            ->expectsOutput('Run interactively to enter a private password; no default password is provided.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_and_action_refuse_production_even_when_invoked_directly(): void
    {
        $this->app->instance('env', 'production');

        $this->artisan('checkpoint:seed-synthetic')
            ->expectsOutput('Synthetic data is available only in local or testing environments.')
            ->assertFailed();
        $this->assertDatabaseCount('users', 0);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Synthetic data is available only in local or testing environments.');
        $this->app->make(CreateSyntheticDataset::class)->handle('SyntheticPassword123');
    }
}
