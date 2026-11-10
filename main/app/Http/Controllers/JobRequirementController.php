<?php

namespace App\Http\Controllers;

use App\Actions\RecordComplianceAssessment;
use App\Enums\UserRole;
use App\Http\Requests\UpdateJobRequirementsRequest;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\User;
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

    public function update(UpdateJobRequirementsRequest $request, JobRole $jobRole, RecordComplianceAssessment $history): RedirectResponse
    {
        DB::transaction(function () use ($request, $jobRole): void {
            $jobRole->qualifications()->sync($request->validated('qualification_ids'));
        });

        foreach (User::where('role', UserRole::Staff)->where('job_role_id', $jobRole->id)->lazyById(100) as $employee) {
            $history->handle($employee, 'requirements', $request->user());
        }

        return to_route('job-roles.requirements.edit', $jobRole)->with('status', 'Mandatory requirements updated.');
    }
}
