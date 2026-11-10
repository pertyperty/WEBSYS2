<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveJobRoleRequest;
use App\Models\JobRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JobRoleController extends Controller
{
    public function index(): View
    {
        return view('job-roles.index', [
            'jobs' => JobRole::query()->withCount('qualifications')->orderBy('name')->orderBy('id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('job-roles.form', [
            'jobRole' => new JobRole(['is_active' => true]),
        ]);
    }

    public function store(SaveJobRoleRequest $request): RedirectResponse
    {
        JobRole::create($request->validated());

        return to_route('job-roles.index')->with('status', 'Record created.');
    }

    public function edit(JobRole $jobRole): View
    {
        return view('job-roles.form', [
            'jobRole' => $jobRole,
        ]);
    }

    public function update(SaveJobRoleRequest $request, JobRole $jobRole): RedirectResponse
    {
        $jobRole->update($request->validated());

        return to_route('job-roles.index')->with('status', 'Record updated.');
    }
}
