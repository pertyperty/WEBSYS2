<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Http\Requests\SaveTrainingCourseRequest;
use App\Models\Qualification;
use App\Models\TrainingCourse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainingCourseController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(CourseStatus::class)],
        ]);
        $search = '%'.addcslashes($filters['search'] ?? '', '\\%_').'%';

        return view('training-courses.index', [
            'courses' => TrainingCourse::visibleTo($request->user())->with('qualification.safetyDomain')
                ->withCount(['enrollments' => fn (Builder $query): Builder => $query->visibleTo($request->user())])
                ->when($search !== '%%', fn (Builder $query): Builder => $query->where(fn (Builder $names): Builder => $names
                    ->where('title', 'like', $search)
                    ->orWhereHas('qualification', fn (Builder $qualifications): Builder => $qualifications->where(fn (Builder $details): Builder => $details
                        ->where('name', 'like', $search)->orWhereHas('safetyDomain', fn (Builder $domains): Builder => $domains->where('name', 'like', $search))))))
                ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
                ->orderBy('title')->orderBy('id')->paginate(15)->withQueryString(),
            'statuses' => CourseStatus::cases(),
        ]);
    }

    public function show(Request $request, int $course): View
    {
        $record = TrainingCourse::visibleTo($request->user())->with('qualification.safetyDomain')->findOrFail($course);

        return view('training-courses.show', [
            'course' => $record,
            'ownEnrollment' => $record->enrollments()->where('employee_id', $request->user()->id)->first(),
        ]);
    }

    public function create(): View
    {
        return view('training-courses.form', [
            'course' => new TrainingCourse,
            'qualifications' => Qualification::active()->where('requires_training', true)->with('safetyDomain')->orderBy('name')->get(),
            'statuses' => [CourseStatus::Draft, CourseStatus::Published],
        ]);
    }

    public function store(SaveTrainingCourseRequest $request): RedirectResponse
    {
        $course = TrainingCourse::create($request->validated());

        return to_route('training-courses.show', $course)->with('status', 'Course created.');
    }

    public function edit(TrainingCourse $course): View
    {
        return view('training-courses.form', [
            'course' => $course,
            'qualifications' => Qualification::where(fn (Builder $query): Builder => $query->active()->where('requires_training', true))
                ->orWhereKey($course->qualification_id)->with('safetyDomain')->orderBy('name')->get(),
            'statuses' => $course->status === CourseStatus::Archived ? [CourseStatus::Archived] : CourseStatus::cases(),
        ]);
    }

    public function update(SaveTrainingCourseRequest $request, TrainingCourse $course): RedirectResponse
    {
        DB::transaction(function () use ($request, $course): void {
            $locked = TrainingCourse::lockForUpdate()->findOrFail($course->id);
            if ($locked->status === CourseStatus::Archived && $request->input('status') !== CourseStatus::Archived->value) {
                throw ValidationException::withMessages(['status' => 'Archived offerings stay archived. Create a new course for renewals.']);
            }
            if ($locked->qualification_id !== $request->integer('qualification_id') && $locked->enrollments()->exists()) {
                throw ValidationException::withMessages(['qualification_id' => 'The qualification cannot change after anyone enrolls. Create a new offering instead.']);
            }
            $locked->update($request->validated());
        });

        return to_route('training-courses.show', $course)->with('status', 'Course updated.');
    }
}
