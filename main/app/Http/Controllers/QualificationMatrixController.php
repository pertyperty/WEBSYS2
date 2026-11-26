<?php

namespace App\Http\Controllers;

use App\Actions\AssessEmployeeCompliance;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QualificationMatrixController extends Controller
{
    public function __invoke(Request $request, AssessEmployeeCompliance $assessment): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'facility_id' => ['nullable', 'integer', 'min:1'],
            'job_role_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = '%'.addcslashes($filters['search'] ?? '', '\\%_').'%';
        $employees = User::visibleTo($request->user())->with(AssessEmployeeCompliance::RELATIONS)
            ->when($search !== '%%', fn (Builder $query): Builder => $query->where('name', 'like', $search))
            ->when($filters['facility_id'] ?? null, fn (Builder $query, int $facility): Builder => $query->where('facility_id', $facility))
            ->when($filters['job_role_id'] ?? null, fn (Builder $query, int $job): Builder => $query->where('job_role_id', $job))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();
        $facilities = Facility::whereHas('users', fn (Builder $users): Builder => $users->visibleTo($request->user()))->orderBy('name')->get();
        $jobs = JobRole::whereHas('users', fn (Builder $users): Builder => $users->visibleTo($request->user()))->orderBy('name')->get();
        $asOf = CarbonImmutable::now('Asia/Manila')->startOfDay();
        $assessments = $employees->getCollection()->mapWithKeys(fn (User $employee): array => [$employee->id => $assessment->handle($employee, $asOf)]);
        $qualifications = $assessments->flatMap(fn (array $result): array => array_values($result['items']))
            ->unique('qualification_id')->sortBy('name')->values();

        return view('employees.matrix', compact('employees', 'assessments', 'qualifications', 'facilities', 'jobs'));
    }
}
