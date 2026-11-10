<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Audit;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Audit> */
class AuditFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->sentence(3), 'facility_id' => Facility::factory(),
            'region_id' => fn (array $attributes): int => Facility::findOrFail($attributes['facility_id'])->region_id,
            'author_id' => User::factory()->state(['role' => UserRole::ComplianceOfficer]),
            'audited_on' => now('Asia/Manila')->toDateString(), 'snapshot' => ['region_name' => 'Test region', 'facility_name' => 'Test facility', 'employees' => []]];
    }
}
