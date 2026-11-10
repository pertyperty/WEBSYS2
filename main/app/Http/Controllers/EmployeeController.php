<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        return view('employees.index', [
            'employees' => User::visibleTo($request->user())->with(['facility.region', 'jobRole'])
                ->orderBy('name')->orderBy('id')->paginate(15),
        ]);
    }

    public function show(Request $request, int $employee): View
    {
        $record = User::visibleTo($request->user())->with(['facility.region', 'jobRole.qualifications.safetyDomain'])
            ->findOrFail($employee);

        return view('employees.show', ['employee' => $record]);
    }
}
