<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceLogout
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $loginAt = $request->hasSession() ? $request->session()->get('authenticated_at') : $user?->currentAccessToken()?->created_at?->timestamp;
        if ($user?->force_logout_at && (! $loginAt || $user->force_logout_at->timestamp >= $loginAt)) {
            $user->tokens()->delete();
            auth()->guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
            }

            return $request->expectsJson() ? response()->json(['message' => 'Session revoked.'], 401) : redirect()->route('login')->withErrors(['email' => 'Your session was revoked by an administrator.']);
        }

        return $next($request);
    }
}
