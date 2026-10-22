<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request and ensure only authenticated Superusers have access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $isDemo = (bool) (
            $request->session()->get('is_demo_session') ||
            ($user && $user->email === config('demo.user_email', 'demo@finz.app'))
        );

        if (!$user || !$user->is_admin || $isDemo) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized: Superuser access required.'], 403);
            }

            return redirect()->route('dashboard')->with('error', 'Unauthorized: Superuser access required.');
        }

        return $next($request);
    }
}
