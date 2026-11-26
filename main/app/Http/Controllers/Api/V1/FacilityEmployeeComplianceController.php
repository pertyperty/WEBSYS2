<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\AssessEmployeeCompliance;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeComplianceSummaryResource;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityEmployeeComplianceController extends Controller
{
    public function __invoke(Request $request, string $facility, AssessEmployeeCompliance $assessment): JsonResponse
    {
        $facility = Facility::verifiableBy($request->user())->whereKey($facility)->firstOrFail();
        $perPage = (int) ($request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ])['per_page'] ?? 25);

        $employees = User::query()->where('facility_id', $facility->getKey())
            ->where('role', UserRole::Staff)->where('is_active', true)
            ->with(AssessEmployeeCompliance::RELATIONS)->orderBy('id')->paginate($perPage);
        $employees->getCollection()->transform(fn (User $employee): array => [
            'employee_reference' => $employee->verification_reference,
            'assessment' => $assessment->handle($employee),
        ]);

        return EmployeeComplianceSummaryResource::collection($employees)->response()
            ->header('Cache-Control', 'private, no-store');
    }
}
