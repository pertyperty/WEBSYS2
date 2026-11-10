<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Models\Qualification;
use App\Models\TrainingCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TrainingCourse> */
class TrainingCourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'qualification_id' => Qualification::factory()->state(['requires_training' => true]),
            'status' => CourseStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => CourseStatus::Published]);
    }
}
