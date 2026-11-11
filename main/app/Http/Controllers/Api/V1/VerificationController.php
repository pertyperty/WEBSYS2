<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\VerifyCredentialReference;
use App\Http\Controllers\Controller;
use App\Http\Resources\VerificationResource;
use App\Models\CredentialDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __invoke(Request $request, string $reference, VerifyCredentialReference $verification): JsonResponse
    {
        $document = CredentialDocument::verifiableBy($request->user())->where('reference', $reference)
            ->with(['employee', 'qualification.safetyDomain', 'employeeTraining.trainingCourse', 'latestReview'])->firstOrFail();

        return (new VerificationResource($verification->handle($document)))->response()
            ->header('Cache-Control', 'private, no-store');
    }
}
