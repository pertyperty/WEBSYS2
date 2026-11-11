<?php

namespace Tests\Feature;

use App\Actions\CaptureAudit;
use App\Actions\IssueAuditorToken;
use App\Actions\ReviewCredential;
use App\Enums\CredentialStatus;
use App\Enums\UserRole;
use App\Models\Audit;
use App\Models\CredentialDocument;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use ZipArchive;

#[Group('recovery')]
class BackupRestoreTest extends TestCase
{
    use DatabaseMigrations;

    public function test_mysql_dump_and_private_archive_restore_preserve_access_history_and_encrypted_sessions(): void
    {
        $finder = new ExecutableFinder;
        $mysql = $finder->find('mysql');
        $mysqldump = $finder->find('mysqldump');
        if (! $mysql || ! $mysqldump || ! extension_loaded('zip')) {
            $this->markTestSkipped('Recovery rehearsal requires MySQL 8 mysql/mysqldump on PATH and PHP ZIP. No recovery result is established when skipped.');
        }
        Storage::fake('local');
        $disk = Storage::disk('local');
        $disk->makeDirectory('recovery');
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        config(['session.driver' => 'database', 'session.encrypt' => true]);
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $staff = User::factory()->for(Facility::factory())->for(JobRole::factory())->create(['password' => 'RecoveryPassword123']);
        $officer = User::factory()->create(['role' => UserRole::ComplianceOfficer]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $auditor = User::factory()->create(['role' => UserRole::Auditor, 'region_id' => $staff->facility->region_id]);
        $qualification = Qualification::factory()->create();
        $staff->jobRole->qualifications()->attach($qualification);
        $bytes = file_get_contents(database_path('fixtures/synthetic-evidence.png'));
        $disk->put('credentials/recovery.png', $bytes);
        $document = CredentialDocument::factory()->create(['employee_id' => $staff->id, 'qualification_id' => $qualification->id, 'private_path' => 'credentials/recovery.png', 'mime_type' => 'image/png', 'file_size' => strlen($bytes)]);
        $this->app->make(ReviewCredential::class)->handle($officer, $document, CredentialStatus::Approved, null);
        $audit = $this->app->make(CaptureAudit::class)->handle($officer, ['title' => 'Recovery readiness snapshot', 'region_id' => $staff->facility->region_id, 'facility_id' => $staff->facility_id]);
        $originalSnapshot = $audit->fresh()->snapshot;
        $active = $this->app->make(IssueAuditorToken::class)->handle($admin, ['auditor_id' => $auditor->id, 'name' => 'Retained access', 'expires_on' => '2026-12-11']);
        $revoked = $this->app->make(IssueAuditorToken::class)->handle($admin, ['auditor_id' => $auditor->id, 'name' => 'Revoked before backup', 'expires_on' => '2026-12-11']);
        $revoked->accessToken->delete();
        $this->post('/login', ['email' => $staff->email, 'password' => 'RecoveryPassword123'])->assertSessionHasNoErrors()->assertRedirect('/dashboard');
        $sessionId = $this->app['session']->getId();
        $encryptedSession = DB::table('sessions')->where('id', $sessionId)->value('payload');
        $this->assertNotNull($encryptedSession);
        $this->assertStringNotContainsString('login_web', base64_decode($encryptedSession));
        $optionFile = $disk->path('recovery/client.cnf');
        $dumpFile = $disk->path('recovery/checkpoint_test.sql');
        $archiveFile = $disk->path('recovery/private-files.zip');

        try {
            $this->writeClientOptions($optionFile);
            $dump = new Process([$mysqldump, '--defaults-file='.$optionFile, '--single-transaction', '--no-tablespaces', '--set-gtid-purged=OFF', '--result-file='.$dumpFile, 'checkpoint_test']);
            $dump->setTimeout(60)->run();
            $this->assertSame(0, $dump->getExitCode(), 'MySQL dump failed; inspect client compatibility and test-account privileges privately.');
            $this->assertGreaterThan(0, filesize($dumpFile));
            $active->accessToken->delete();
            $this->assertDatabaseCount('personal_access_tokens', 0);
            $archive = new ZipArchive;
            $this->assertTrue($archive->open($archiveFile, ZipArchive::CREATE | ZipArchive::OVERWRITE));
            $archive->addFile($disk->path($document->private_path), $document->private_path);
            $archive->close();

            $this->assertTrue(app()->environment('testing'));
            $this->assertSame('mysql', DB::connection()->getDriverName());
            $this->assertSame('checkpoint_test', DB::connection()->getDatabaseName());
            Schema::dropAllTables();
            $disk->delete($document->private_path);
            $this->assertFalse(Schema::hasTable('users'));
            $disk->assertMissing($document->private_path);
            $stream = fopen($dumpFile, 'rb');
            try {
                $restore = new Process([$mysql, '--defaults-file='.$optionFile, '--database=checkpoint_test']);
                $restore->setInput($stream)->setTimeout(60)->run();
                $this->assertSame(0, $restore->getExitCode(), 'MySQL import failed; no development database was targeted.');
            } finally {
                fclose($stream);
            }
            $this->assertTrue($archive->open($archiveFile));
            $this->assertTrue($archive->extractTo($disk->path('')));
            $archive->close();
            DB::purge('mysql');

            $restored = User::findOrFail($staff->id);
            $this->assertTrue(Hash::check('RecoveryPassword123', $restored->password));
            $this->assertSame($staff->verification_reference, $restored->verification_reference);
            $this->assertSame($originalSnapshot, Audit::findOrFail($audit->id)->snapshot);
            $this->assertDatabaseHas('employee_qualification', ['employee_id' => $staff->id, 'credential_document_id' => $document->id]);
            $this->assertSame(1, CredentialDocument::findOrFail($document->id)->events()->count());
            $this->assertSame(hash('sha256', $bytes), hash('sha256', $disk->get($document->private_path)));
            $this->assertSame($encryptedSession, DB::table('sessions')->where('id', $sessionId)->value('payload'));
            $this->app['session']->forgetDrivers();
            $this->app->forgetInstance('session.store');
            $this->app['auth']->forgetGuards();
            $session = $this->app['session']->driver();
            $session->setId($sessionId);
            $session->start();
            $this->assertSame($staff->id, $session->get($this->app['auth']->guard('web')->getName()));
            $this->actingAs($restored, 'web')->get('/credentials/'.$document->id.'/download')->assertOk()->assertDownload();
            $manager = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $staff->facility_id]);
            $this->actingAs($manager)->get('/credentials/'.$document->id.'/download')->assertForbidden();
            $this->app['auth']->forgetGuards();
            $this->getJson('/api/v1/verifications/'.$document->reference, ['Authorization' => 'Bearer '.$active->plainTextToken])->assertOk()->assertJsonPath('data.validity', 'valid');
            $this->app['auth']->forgetGuards();
            $this->getJson('/api/v1/verifications/'.$document->reference, ['Authorization' => 'Bearer '.$revoked->plainTextToken])->assertUnauthorized();
            $this->assertDatabaseCount('personal_access_tokens', 1);
            $this->mock(MaintenanceMode::class)->shouldReceive('active')->andReturn(true, false);
            $this->artisan('checkpoint:invalidate-restored-access')->assertSuccessful();
            $this->assertDatabaseCount('sessions', 0);
            $this->assertDatabaseCount('password_reset_tokens', 0);
            $this->assertDatabaseCount('personal_access_tokens', 0);
            $this->assertNull($restored->fresh()->remember_token);
            $this->app['auth']->forgetGuards();
            $this->getJson('/api/v1/verifications/'.$document->reference, ['Authorization' => 'Bearer '.$active->plainTextToken])->assertUnauthorized();
            $this->assertSame($originalSnapshot, Audit::findOrFail($audit->id)->snapshot);
        } finally {
            $disk->deleteDirectory('recovery');
        }
    }

    private function writeClientOptions(string $path): void
    {
        $this->assertSame('checkpoint_test', DB::connection()->getDatabaseName());
        $connection = DB::connection()->getConfig();
        $options = ['protocol' => 'TCP', 'host' => $connection['host'], 'port' => $connection['port'], 'user' => $connection['username'], 'password' => $connection['password']];
        $contents = "[client]\n";
        foreach ($options as $key => $value) {
            $escaped = strtr((string) $value, ['\\' => '\\\\', '"' => '\\"', "\n" => '\\n', "\r" => '\\r']);
            $contents .= $key.'="'.$escaped.'"'."\n";
        }
        file_put_contents($path, $contents);
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($path, 0600);
        }
    }
}
