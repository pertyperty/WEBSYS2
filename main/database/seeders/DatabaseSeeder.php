<?php

namespace Database\Seeders;

use App\Models\JobRole;
use App\Models\Region;
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
        $region = Region::firstOrCreate(['name' => 'Demo Region']);
        $region->facilities()->firstOrCreate(['name' => 'Demo Logistics Hub']);
        JobRole::firstOrCreate(['name' => 'Driver']);
        JobRole::firstOrCreate(['name' => 'Warehouse Staff']);
        $this->call([SafetyDomainSeeder::class, QualificationSeeder::class]);
    }
}
