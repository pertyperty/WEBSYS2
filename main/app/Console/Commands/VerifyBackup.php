<?php

namespace App\Console\Commands;

use App\Actions\RecordOperation;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;
use ZipArchive;

class VerifyBackup extends Command
{
    protected $signature = 'checkpoint:verify-backup {directory? : Existing retained backup directory}';

    protected $description = 'Verify retained SQL/archive/private-file hashes and record actual backup age without restoring data';

    public function handle(RecordOperation $record): int
    {
        $archive = new ZipArchive;
        $opened = false;
        try {
            $directory = $this->argument('directory') ?: config('operations.backup_directory');
            if (! is_string($directory) || ! is_dir($directory)) {
                throw new RuntimeException('Configure OPERATIONS_BACKUP_DIRECTORY to a retained backup directory.');
            }
            $manifest = json_decode(ltrim(file_get_contents($directory.'/manifest.json'), "\xEF\xBB\xBF"), true, flags: JSON_THROW_ON_ERROR);
            foreach (['checkpoint.sql' => 'sql_sha256', 'private-files.zip' => 'zip_sha256'] as $file => $key) {
                if (! is_file($directory.'/'.$file) || ! is_string($manifest[$key] ?? null)
                    || ! hash_equals(strtolower($manifest[$key]), hash_file('sha256', $directory.'/'.$file))) {
                    throw new RuntimeException('Backup SQL or archive hash did not match.');
                }
            }
            if ($archive->open($directory.'/private-files.zip', ZipArchive::CHECKCONS) !== true) {
                throw new RuntimeException('Backup archive could not be verified.');
            }
            $opened = true;
            if (! is_array($manifest['private_files'] ?? null) || $manifest['private_files'] === []) {
                throw new RuntimeException('Backup private-file manifest is missing.');
            }
            foreach ($manifest['private_files'] as $path => $hash) {
                $bytes = $archive->getFromName($path);
                if ($bytes === false) {
                    $bytes = $archive->getFromName('private/'.$path);
                }
                if ($bytes === false || ! is_string($hash) || ! hash_equals(strtolower($hash), hash('sha256', $bytes))) {
                    throw new RuntimeException('A private file did not match its retained hash.');
                }
            }
            if (! is_string($manifest['created_local'] ?? null) || $manifest['created_local'] === '') {
                throw new RuntimeException('Backup creation time is missing.');
            }
            $createdAt = CarbonImmutable::parse($manifest['created_local'], 'Asia/Manila');
            if ($createdAt->isFuture()) {
                throw new RuntimeException('Backup creation time is in the future.');
            }
            $record->handle('backup', occurredAt: $createdAt);
            $this->info('Retained backup hashes verified. Backup age uses its creation time, not this verification time. No data was restored.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $record->handle('backup', 'failed', $exception::class);
            $this->error('Backup verification failed. Check directory configuration, manifest, SQL/archive integrity, and private-file hashes.');

            return self::FAILURE;
        } finally {
            if ($opened) {
                $archive->close();
            }
        }
    }
}
