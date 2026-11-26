<?php

namespace App\Http\Controllers;

use App\Actions\AssessEmployeeCompliance;
use App\Enums\ComplianceStatus;
use App\Enums\CredentialStatus;
use App\Enums\TrainingStatus;
use App\Enums\UserRole;
use App\Models\AuditFinding;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AssessEmployeeCompliance $assessment): View
    {
        $asOf = CarbonImmutable::now('Asia/Manila')->startOfDay();
        $viewer = $request->user();
        $employees = User::visibleTo($viewer)->with(AssessEmployeeCompliance::RELATIONS)->get();
        $assessments = $employees->map(fn (User $employee): array => $assessment->handle($employee, $asOf));
        $assessable = $assessments->where('status', '!=', ComplianceStatus::NotAssessable->value);
        $eligible = $assessable->where('status', ComplianceStatus::Eligible->value)->count();
        $items = $assessable->flatMap(fn (array $result): array => array_values($result['items']));
        $trainings = $employees->flatMap->employeeTrainings;

        return view('dashboard', [
            'assessedOn' => $asOf->toDateString(),
            'ownAssessment' => $assessments->firstWhere('employee_id', $viewer->id),
            'openFindingsCount' => $viewer->can('view-audits')
                ? AuditFinding::visibleTo($viewer)->where('status', '!=', 'resolved')->count()
                : 0,
            'unassignedStaffCount' => $viewer->can('manage-users')
                ? User::query()->where('role', UserRole::Staff)->where('is_active', true)
                    ->where(fn ($query) => $query->whereNull('facility_id')->orWhereNull('job_role_id'))->count()
                : 0,
            'totals' => [
                'employees' => $employees->count(), 'assessable' => $assessable->count(), 'eligible' => $eligible,
                'not_eligible' => $assessable->count() - $eligible,
                'not_assessable' => $assessments->count() - $assessable->count(),
                'eligible_rate' => $assessable->isEmpty() ? null : round($eligible * 100 / $assessable->count(), 2),
                'missing' => $items->where('state', 'missing')->count(), 'expired' => $items->where('state', 'expired')->count(),
                'expiring_soon' => $items->where('expiring_soon', true)->count(),
                'training_incomplete' => $items->where('requires_training', true)->where('training_completed', false)->count(),
                'pending_documents' => $employees->flatMap->credentialDocuments->where('status', CredentialStatus::Pending)->count(),
                'enrolled' => $trainings->where('status', TrainingStatus::Enrolled)->count(),
                'completed' => $trainings->where('status', TrainingStatus::Completed)->count(),
            ],
        ]);
    }
}
