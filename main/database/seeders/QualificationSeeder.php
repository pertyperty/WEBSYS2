<?php

namespace Database\Seeders;

use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\SafetyDomain;
use Illuminate\Database\Seeder;

class QualificationSeeder extends Seeder
{
    public function run(): void
    {
        $transport = SafetyDomain::firstOrCreate(['name' => 'Transport Safety']);
        $workplace = SafetyDomain::firstOrCreate(['name' => 'Workplace Safety']);
        $driving = Qualification::firstOrCreate(
            ['safety_domain_id' => $transport->id, 'name' => 'Defensive Driving'],
            ['requires_expiry' => true, 'requires_training' => true],
        );
        $handling = Qualification::firstOrCreate(
            ['safety_domain_id' => $workplace->id, 'name' => 'Manual Handling'],
            ['requires_expiry' => true, 'requires_training' => true],
        );
        $driver = JobRole::firstOrCreate(['name' => 'Driver']);
        $warehouse = JobRole::firstOrCreate(['name' => 'Warehouse Staff']);

        if ($driver->refresh()->is_active && $driving->refresh()->is_active && $transport->refresh()->is_active) {
            $driver->qualifications()->syncWithoutDetaching([$driving->id]);
        }
        if ($warehouse->refresh()->is_active && $handling->refresh()->is_active && $workplace->refresh()->is_active) {
            $warehouse->qualifications()->syncWithoutDetaching([$handling->id]);
        }
    }
}
