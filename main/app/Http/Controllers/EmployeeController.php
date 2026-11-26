<?php

namespace App\Http\Controllers;

use App\Actions\AssessEmployeeCompliance;
use App\Actions\RecordComplianceAssessment;
use App\Models\Facility;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request, AssessEmployeeCompliance $assessment): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'facility_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = '%'.addcslashes($filters['search'] ?? '', '\\%_').'%';
        $employees = User::visibleTo($request->user())->with(AssessEmployeeCompliance::RELATIONS)
            ->when($search !== '%%', fn (Builder $query): Builder => $query->where(fn (Builder $names): Builder => $names
                ->where('name', 'like', $search)
                ->orWhereHas('jobRole', fn (Builder $jobs): Builder => $jobs->where('name', 'like', $search))
                ->orWhereHas('facility', fn (Builder $facilities): Builder => $facilities->where('name', 'like', $search))))
            ->when($filters['facility_id'] ?? null, fn (Builder $query, int $facility): Builder => $query->where('facility_id', $facility))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();
        $asOf = CarbonImmutable::now('Asia/Manila')->startOfDay();
        $assessments = $employees->getCollection()->mapWithKeys(fn (User $employee): array => [$employee->id => $assessment->handle($employee, $asOf)]);

        return view('employees.index', [
            'employees' => $employees,
            'assessments' => $assessments,
            'facilities' => Facility::whereHas('users', fn (Builder $users): Builder => $users->visibleTo($request->user()))->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, int $employee, RecordComplianceAssessment $history): View
    {
        $record = User::visibleTo($request->user())->with(['facility.region', 'jobRole.qualifications.safetyDomain'])
            ->findOrFail($employee);

        return view('employees.show', ['employee' => $record, 'assessment' => $history->handle($record, 'read', $request->user())]);
    }
}
