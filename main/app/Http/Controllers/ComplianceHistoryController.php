<?php

namespace App\Http\Controllers;

use App\Actions\RecordComplianceAssessment;
use App\Models\ComplianceAssessment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplianceHistoryController extends Controller
{
    public function __invoke(Request $request, int $employee, RecordComplianceAssessment $history): View
    {
        $record = User::visibleTo($request->user())->findOrFail($employee);
        $history->handle($record, 'read', $request->user());

        return view('employees.history', [
            'employee' => $record,
            'history' => ComplianceAssessment::where('employee_id', $record->id)->with('actor')->latest('id')->paginate(15),
        ]);
    }
}
