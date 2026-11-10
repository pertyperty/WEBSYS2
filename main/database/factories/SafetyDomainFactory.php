<?php

namespace Database\Factories;

use App\Models\SafetyDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SafetyDomain> */
class SafetyDomainFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->words(3, true), 'is_active' => true];
    }
}
