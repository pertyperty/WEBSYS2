<?php

namespace App\Http\Controllers;

use App\Actions\ReviewCredential;
use App\Enums\CredentialStatus;
use App\Http\Requests\ReviewCredentialDocumentRequest;
use App\Models\CredentialDocument;
use Illuminate\Http\RedirectResponse;

class CredentialReviewController extends Controller
{
    public function store(ReviewCredentialDocumentRequest $request, CredentialDocument $document, ReviewCredential $review): RedirectResponse
    {
        $review->handle($request->user(), $document, CredentialStatus::from($request->validated('decision')), $request->validated('note'));

        return to_route('credentials.show', $document)->with('status', 'Review recorded. Approval and current expiry validity are separate.');
    }
}
