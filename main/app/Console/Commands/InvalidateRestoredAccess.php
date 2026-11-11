<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Support\Facades\DB;

class InvalidateRestoredAccess extends Command
{
    protected $signature = 'checkpoint:invalidate-restored-access';

    protected $description = 'Clear restored sessions, remembered logins, reset links, and API tokens while in maintenance';

    public function handle(MaintenanceMode $maintenance): int
    {
        if (! $maintenance->active()) {
            $this->error('Keep the recovered application in maintenance mode before invalidating restored access.');

            return self::FAILURE;
        }
        if (config('session.driver') !== 'database') {
            $this->error('This command supports database sessions only. No access records were changed.');

            return self::FAILURE;
        }
        $counts = DB::transaction(fn (): array => [
            'Sessions' => DB::table(config('session.table'))->delete(),
            'Remembered logins' => DB::table('users')->whereNotNull('remember_token')->update(['remember_token' => null]),
            'Password-reset links' => DB::table(config('auth.passwords.'.config('auth.defaults.passwords').'.table'))->delete(),
            'API tokens' => DB::table('personal_access_tokens')->delete(),
        ]);
        $this->table(['Access record', 'Invalidated'], collect($counts)->map(fn (int $count, string $label): array => [$label, $count])->values()->all());
        $this->info('Restored access invalidated. Accounts, passwords, evidence, and history were preserved.');

        return self::SUCCESS;
    }
}
