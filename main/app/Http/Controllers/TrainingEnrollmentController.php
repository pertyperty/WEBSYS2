<?php

namespace App\Http\Controllers;

use App\Enums\TrainingStatus;
use App\Models\EmployeeTraining;
use App\Models\Facility;
use App\Models\TrainingCourse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainingEnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TrainingStatus::class)],
            'facility_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = '%'.addcslashes($filters['search'] ?? '', '\\%_').'%';
        $query = EmployeeTraining::visibleTo($request->user())
            ->when($search !== '%%', fn (Builder $query): Builder => $query->where(fn (Builder $names): Builder => $names
                ->whereHas('employee', fn (Builder $employees): Builder => $employees->where('name', 'like', $search))
                ->orWhereHas('trainingCourse', fn (Builder $courses): Builder => $courses->where('title', 'like', $search)
                    ->orWhereHas('qualification', fn (Builder $qualifications): Builder => $qualifications->where('name', 'like', $search)))))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['facility_id'] ?? null, fn (Builder $query, int $facility): Builder => $query
                ->whereHas('employee', fn (Builder $employees): Builder => $employees->where('facility_id', $facility)));

        $completed = (clone $query)->where('status', TrainingStatus::Completed)->count();
        $enrollments = $query->with(['employee.facility', 'trainingCourse.qualification', 'completedBy'])
            ->orderByDesc('enrolled_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('training-enrollments.index', [
            'enrollments' => $enrollments,
            'total' => $enrollments->total(),
            'completed' => $completed,
            'facilities' => Facility::whereHas('users.employeeTrainings', fn (Builder $enrollments): Builder => $enrollments
                ->visibleTo($request->user()))->orderBy('name')->get(),
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
