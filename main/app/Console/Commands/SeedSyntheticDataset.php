<?php

namespace App\Console\Commands;

use App\Actions\CreateSyntheticDataset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class SeedSyntheticDataset extends Command
{
    protected $signature = 'checkpoint:seed-synthetic';

    protected $description = 'Append fictional classroom records locally without resetting existing data';

    public function handle(CreateSyntheticDataset $dataset): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Synthetic data is available only in local or testing environments.');

            return self::FAILURE;
        }
        if (! $this->input->isInteractive()) {
            $this->error('Run interactively to enter a private password; no default password is provided.');

            return self::FAILURE;
        }
        $input = [
            'password' => $this->secret('Synthetic account password (12+ characters with letters and numbers)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];
        $validator = Validator::make($input, ['password' => ['required', 'confirmed', Password::default()]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        try {
            $counts = $dataset->handle($input['password']);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->table(['Table', 'Added records'], collect($counts)->map(fn (int $count, string $table): array => [$table, $count])->values()->all());
        $this->info('Added '.array_sum($counts).' synthetic records. Existing records were preserved.');
        $this->line('Account addresses and scenario guide: docs/Implementation.md. No API token was issued.');

        return self::SUCCESS;
    }
}
