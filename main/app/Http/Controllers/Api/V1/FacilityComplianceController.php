<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\SummarizeFacilityCompliance;
use App\Http\Controllers\Controller;
use App\Http\Resources\FacilityComplianceResource;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityComplianceController extends Controller
{
    public function __invoke(Request $request, string $facility, SummarizeFacilityCompliance $summary): JsonResponse
    {
        $facility = Facility::verifiableBy($request->user())->whereKey($facility)->firstOrFail();

        return (new FacilityComplianceResource($summary->handle($facility)))->response()
            ->header('Cache-Control', 'private, no-store');
    }
}
