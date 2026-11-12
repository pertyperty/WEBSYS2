<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\AssessEmployeeCompliance;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeComplianceResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeComplianceController extends Controller
{
    public function __invoke(Request $request, string $reference, AssessEmployeeCompliance $assessment): JsonResponse
    {
        $employee = User::verifiableBy($request->user())->where('verification_reference', $reference)
            ->with(AssessEmployeeCompliance::RELATIONS)->firstOrFail();

        return (new EmployeeComplianceResource([
            'employee_reference' => $employee->verification_reference,
            'assessment' => $assessment->handle($employee),
        ]))->response()->header('Cache-Control', 'private, no-store');
    }
}
