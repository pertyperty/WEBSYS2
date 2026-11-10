<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateJobRequirementsRequest;
use App\Models\JobRole;
use App\Models\Qualification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JobRequirementController extends Controller
{
    public function edit(JobRole $jobRole): View
    {
        return view('job-roles.requirements', [
            'jobRole' => $jobRole,
            'domains' => Qualification::active()->with('safetyDomain')
                ->orderBy('name')->get()->groupBy('safetyDomain.name'),
            'selected' => $jobRole->qualifications()->pluck('qualifications.id')->all(),
        ]);
    }

    public function update(UpdateJobRequirementsRequest $request, JobRole $jobRole): RedirectResponse
    {
        DB::transaction(function () use ($request, $jobRole): void {
            $jobRole->qualifications()->sync($request->validated('qualification_ids'));
        });

        return to_route('job-roles.requirements.edit', $jobRole)->with('status', 'Mandatory requirements updated.');
    }
}
