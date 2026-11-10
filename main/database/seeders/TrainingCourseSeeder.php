<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Models\Qualification;
use App\Models\TrainingCourse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class TrainingCourseSeeder extends Seeder
{
    public function run(): void
    {
        $qualifications = Qualification::active()->where('requires_training', true)
            ->whereIn('name', ['Defensive Driving', 'Manual Handling'])
            ->whereHas('safetyDomain', fn (Builder $query): Builder => $query->active())->get();

        foreach ($qualifications as $qualification) {
            TrainingCourse::firstOrCreate(
                ['qualification_id' => $qualification->id, 'title' => $qualification->name.' - Initial Offering'],
                ['description' => 'Safe practices for healthcare logistics personnel.', 'status' => CourseStatus::Published],
            );
        }
    }
}
