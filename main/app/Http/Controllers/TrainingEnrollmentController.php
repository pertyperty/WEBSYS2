<?php

namespace App\Http\Controllers;

use App\Enums\TrainingStatus;
use App\Models\EmployeeTraining;
use App\Models\TrainingCourse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainingEnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = EmployeeTraining::visibleTo($request->user());

        return view('training-enrollments.index', [
            'total' => (clone $query)->count(),
            'completed' => (clone $query)->where('status', TrainingStatus::Completed)->count(),
            'enrollments' => $query->with(['employee.facility', 'trainingCourse.qualification', 'completedBy'])
                ->orderByDesc('enrolled_at')->orderByDesc('id')->paginate(15),
        ]);
    }

    public function store(Request $request, TrainingCourse $course): RedirectResponse
    {
        DB::transaction(function () use ($request, $course): void {
            $locked = TrainingCourse::with('qualification.safetyDomain')->lockForUpdate()->findOrFail($course->id);
            if (! $locked->acceptsEnrollment()) {
                throw ValidationException::withMessages(['course' => 'This course is not open for enrollment.']);
            }
            EmployeeTraining::firstOrCreate(
                ['employee_id' => $request->user()->id, 'training_course_id' => $locked->id],
                ['enrolled_at' => now()],
            );
        });

        return to_route('training-enrollments.index')->with('status', 'Your enrollment is saved. Enrollment alone does not grant a qualification.');
    }
}
