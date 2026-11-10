<?php

namespace App\Http\Controllers;

use App\Actions\RecordComplianceAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);

        return view('employees.index', [
            'employees' => User::visibleTo($request->user())->with(['facility.region', 'jobRole'])
                ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where('name', 'like', '%'.$search.'%'))
                ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString(),
        ]);
    }

    public function show(Request $request, int $employee, RecordComplianceAssessment $history): View
    {
        $record = User::visibleTo($request->user())->with(['facility.region', 'jobRole.qualifications.safetyDomain'])
            ->findOrFail($employee);

        return view('employees.show', ['employee' => $record, 'assessment' => $history->handle($record, 'read', $request->user())]);
    }
}
