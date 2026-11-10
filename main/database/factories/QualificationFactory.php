<?php

namespace Database\Factories;

use App\Models\Qualification;
use App\Models\SafetyDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Qualification> */
class QualificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'safety_domain_id' => SafetyDomain::factory(),
            'requires_expiry' => true,
            'requires_training' => false,
            'is_active' => true,
        ];
    }
}
