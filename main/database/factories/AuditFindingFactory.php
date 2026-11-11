<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditFinding> */
class AuditFindingFactory extends Factory
{
    public function definition(): array
    {
        return ['audit_id' => Audit::factory(),
            'facility_id' => fn (array $attributes): int => Audit::findOrFail($attributes['audit_id'])->facility_id,
            'region_id' => fn (array $attributes): int => Audit::findOrFail($attributes['audit_id'])->region_id,
            'assignee_id' => fn (array $attributes): int => User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $attributes['facility_id']])->id,
            'title' => fake()->sentence(3), 'description' => fake()->paragraph(), 'due_on' => now('Asia/Manila')->addDays(7)->toDateString()];
    }
}
