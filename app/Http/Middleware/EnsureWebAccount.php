<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && (! $request->user()->is_active || $request->user()->role === UserRole::Auditor)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'This account cannot access the web application.']);
        }

        return $next($request);
    }
}
