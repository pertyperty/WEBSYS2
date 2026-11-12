<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuditorToken
{
    public function handle(Request $request, Closure $next, string $ability = 'verification:read'): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        abort_unless($request->bearerToken() && $token instanceof PersonalAccessToken, 401);
        abort_unless($user->is_active && $user->role === UserRole::Auditor && $user->region_id
            && $user->region?->is_active && (int) $token->region_id === $user->region_id
            && in_array($ability, $token->abilities ?? [], true), 403, 'This token cannot access the requested verification data.');

        return $next($request);
    }
}
