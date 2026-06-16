<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->isActive()) {
            auth()->guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
            }
            abort(403, 'Your account is suspended.');
        }

        return $next($request);
    }
}
