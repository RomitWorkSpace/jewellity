<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** A suspended customer is signed out on their next request, whatever session or cookie they still hold. */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBlocked() && ! $user->isAdmin()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'This account has been suspended.'], 403);
            }

            return redirect()->route('login')->withErrors(['email' => 'This account has been suspended. Please contact support.']);
        }

        return $next($request);
    }
}
