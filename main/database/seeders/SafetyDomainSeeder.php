<?php

namespace Database\Seeders;

use App\Models\SafetyDomain;
use Illuminate\Database\Seeder;

class SafetyDomainSeeder extends Seeder
{
    public function run(): void
    {
        SafetyDomain::firstOrCreate(['name' => 'Transport Safety']);
        SafetyDomain::firstOrCreate(['name' => 'Workplace Safety']);
    }
}
