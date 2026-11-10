<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffAssigned
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->role === UserRole::Staff && ! $request->user()->isAssignedStaff()) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
