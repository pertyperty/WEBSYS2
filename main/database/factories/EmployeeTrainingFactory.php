<?php

namespace Database\Factories;

use App\Enums\TrainingStatus;
use App\Models\EmployeeTraining;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\TrainingCourse;
use App\Models\User;
use Carbon\CarbonImmutable;
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

    public function completed(User $officer, ?CarbonImmutable $completedOn = null): static
    {
        return $this->for($officer, 'completedBy')->state(fn (): array => [
            'status' => TrainingStatus::Completed,
            'completed_at' => $completedOn ?? CarbonImmutable::now('Asia/Manila')->startOfDay(),
            'feedback' => 'Practical handling checks completed.',
        ]);
    }
}
