<?php

namespace Database\Seeders;

use App\Actions\CreateSyntheticDataset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SyntheticDatasetSeeder extends Seeder
{
    public function run(CreateSyntheticDataset $dataset): void
    {
        if (! app()->environment('local', 'testing') || User::exists()) {
            $this->command?->info('Synthetic accounts skipped: only an empty local/testing account database is populated.');

            return;
        }

        $counts = $dataset->handle(Str::password(32));
        $this->command?->info(array_sum($counts).' synthetic records created using factories. Account passwords are random; use the local password-reset flow to sign in.');
    }
}
