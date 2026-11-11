<?php

namespace App\Http\Controllers;

use App\Actions\IssueAuditorToken;
use App\Enums\UserRole;
use App\Http\Requests\IssueAuditorTokenRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class AuditorTokenController extends Controller
{
    public function index(): View
    {
        $auditors = User::where('role', UserRole::Auditor)->where('is_active', true)
            ->whereHas('region', fn ($query) => $query->active())->with('region')->orderBy('name')->get(['id', 'name', 'region_id']);
        $tokens = PersonalAccessToken::where('tokenable_type', (new User)->getMorphClass())
            ->with('tokenable')->orderByDesc('id')->paginate(15);

        return view('admin.auditor-tokens.index', compact('auditors', 'tokens'));
    }

    public function store(IssueAuditorTokenRequest $request, IssueAuditorToken $issue): Response
    {
        $token = $issue->handle($request->user(), $request->validated());

        return response()->view('admin.auditor-tokens.created', [
            'plainTextToken' => $token->plainTextToken,
            'tokenName' => $token->accessToken->name,
            'expiresAt' => $token->accessToken->expires_at,
        ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function destroy(int $token): RedirectResponse
    {
        $token = PersonalAccessToken::where('tokenable_type', (new User)->getMorphClass())->findOrFail($token);
        DB::transaction(function () use ($token): void {
            User::lockForUpdate()->findOrFail($token->tokenable_id);
            $token->delete();
        });

        return to_route('auditor-tokens.index')->with('status', 'API token revoked.');
    }
}
