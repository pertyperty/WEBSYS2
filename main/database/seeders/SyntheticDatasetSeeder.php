<?php

namespace Database\Seeders;

use App\Actions\CreateSyntheticDataset;
use App\Models\User;
use Illuminate\Database\Seeder;

class SyntheticDatasetSeeder extends Seeder
{
    public function run(CreateSyntheticDataset $dataset): void
    {
        if (! app()->environment('local', 'testing') || User::exists()) {
            $this->command?->info('Synthetic accounts skipped: only an empty local/testing account database is populated.');

            return;
        }

        $counts = $dataset->handle('checkpoint123');
        $this->command?->info(array_sum($counts).' synthetic records created using factories. New local sample accounts use the documented classroom password. Existing accounts are never updated.');
    }
}
