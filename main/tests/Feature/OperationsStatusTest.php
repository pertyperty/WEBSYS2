<?php

namespace Tests\Feature;

use App\Actions\RecordOperation;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class OperationsStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['operations.enabled' => true]);
    }

    public function test_missing_stale_and_failed_operations_produce_a_nonzero_status(): void
    {
        Storage::fake('local');
        $this->freezeTime();
        $record = app(RecordOperation::class);

        $this->artisan('checkpoint:operations-status', ['--json' => true])->expectsOutputToContain('"status":"missing"')->assertFailed();
        $record->handle('scheduler', occurredAt: CarbonImmutable::now()->subMinutes(16));
        $record->handle('reconciliation', 'failed');
        $record->handle('backup');
        $this->artisan('checkpoint:operations-status', ['--json' => true])->expectsOutputToContain('"status":"stale"')->assertFailed();
    }

    public function test_healthy_status_and_acknowledged_incident_keep_logs_and_exclude_secrets(): void
    {
        Storage::fake('local');
        $this->freezeTime();
        config(['operations.record_application_errors' => true]);
        $record = app(RecordOperation::class);
        foreach (['scheduler', 'reconciliation', 'backup'] as $operation) {
            $record->handle($operation);
        }
        report(new RuntimeException('Do not expose secret-password in health metadata'));

        $this->artisan('checkpoint:operations-status', ['--json' => true])->expectsOutputToContain('"status":"failed"')->assertFailed();
        $this->assertStringNotContainsString('secret-password', Storage::disk('local')->get('operations/application.json'));
        $this->artisan('checkpoint:operations-status', ['--json' => true, '--acknowledge-error' => true])
            ->expectsOutputToContain('"healthy":true')->assertSuccessful();
    }

    public function test_corrupt_status_is_unhealthy_and_never_silently_treated_as_success(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('operations/scheduler.json', 'invalid json');

        $this->artisan('checkpoint:operations-status', ['--json' => true])->expectsOutputToContain('"status":"unreadable"')->assertFailed();
    }

    #[TestWith([''])]
    #[TestWith(['private/'])]
    public function test_backup_verification_keeps_creation_age_and_detects_tampering(string $prefix): void
    {
        Storage::fake('local');
        $this->freezeTime();
        $disk = Storage::disk('local');
        $disk->makeDirectory('backup-fixture');
        $directory = $disk->path('backup-fixture');
        file_put_contents($directory.'/checkpoint.sql', 'Harmless SQL fixture');
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($directory.'/private-files.zip', ZipArchive::CREATE));
        $zip->addFromString($prefix.'evidence.txt', 'Harmless evidence');
        $zip->close();
        $createdAt = CarbonImmutable::now()->subDays(2);
        file_put_contents($directory.'/manifest.json', json_encode([
            'created_local' => $createdAt->toIso8601String(),
            'sql_sha256' => hash_file('sha256', $directory.'/checkpoint.sql'),
            'zip_sha256' => hash_file('sha256', $directory.'/private-files.zip'),
            'private_files' => ['evidence.txt' => hash('sha256', 'Harmless evidence')],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('checkpoint:verify-backup', ['directory' => $directory])->assertSuccessful();
        $state = json_decode($disk->get('operations/backup.json'), true);
        $this->assertSame($createdAt->toIso8601String(), $state['occurred_at']);
        $this->artisan('checkpoint:operations-status', ['--json' => true])->expectsOutputToContain('"backup":{"status":"stale"')->assertFailed();
        file_put_contents($directory.'/checkpoint.sql', 'Changed contents');
        $this->artisan('checkpoint:verify-backup', ['directory' => $directory])->assertFailed();
        $this->assertSame('failed', json_decode($disk->get('operations/backup.json'), true)['status']);
    }

    public function test_scheduled_heartbeat_is_a_real_scheduler_event_and_updates_private_status(): void
    {
        Storage::fake('local');
        $events = app(Schedule::class)->events();
        $heartbeat = collect($events)->first(fn ($event): bool => $event->description === 'checkpoint:scheduler-heartbeat');
        $this->assertNotNull($heartbeat);

        $heartbeat->run(app());

        $this->assertSame('ok', json_decode(Storage::disk('local')->get('operations/scheduler.json'), true)['status']);
    }
}
