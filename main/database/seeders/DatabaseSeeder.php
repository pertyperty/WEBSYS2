<?php

namespace Database\Seeders;

use App\Models\JobRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PhilippineOrganizationSeeder::class);
        JobRole::firstOrCreate(['name' => 'Driver']);
        JobRole::firstOrCreate(['name' => 'Warehouse Staff']);
        $this->call([SafetyDomainSeeder::class, QualificationSeeder::class, TrainingCourseSeeder::class]);
    }
}
