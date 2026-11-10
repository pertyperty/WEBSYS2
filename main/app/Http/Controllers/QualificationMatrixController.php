<?php

namespace App\Http\Controllers;

use App\Actions\AssessEmployeeCompliance;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QualificationMatrixController extends Controller
{
    public function __invoke(Request $request, AssessEmployeeCompliance $assessment): View
    {
        $employees = User::visibleTo($request->user())->with(AssessEmployeeCompliance::RELATIONS)
            ->orderBy('name')->orderBy('id')->paginate(15);
        $asOf = CarbonImmutable::now('Asia/Manila')->startOfDay();
        $assessments = $employees->getCollection()->mapWithKeys(fn (User $employee): array => [$employee->id => $assessment->handle($employee, $asOf)]);
        $qualifications = $assessments->flatMap(fn (array $result): array => array_values($result['items']))
            ->unique('qualification_id')->sortBy('name')->values();

        return view('employees.matrix', compact('employees', 'assessments', 'qualifications'));
    }
}
