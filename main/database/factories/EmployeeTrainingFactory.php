<?php

namespace Database\Factories;

use App\Models\EmployeeTraining;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\TrainingCourse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeTraining> */
class EmployeeTrainingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => User::factory()->for(Facility::factory())->for(JobRole::factory()),
            'training_course_id' => TrainingCourse::factory()->published(),
            'enrolled_at' => now(),
        ];
    }
}
