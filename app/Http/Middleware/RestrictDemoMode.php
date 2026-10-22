<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictDemoMode
{
    /**
     * Handle an incoming request and enforce strict read-only sandbox restrictions for demo sessions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isDemo = (bool) (
            $request->session()->get('is_demo_session') ||
            ($user && $user->email === config('demo.user_email', 'demo@finz.app'))
        );

        if (!$isDemo) {
            return $next($request);
        }

        // 1. Strictly block Superuser Admin routes for demo users
        if ($request->is('admin*') || $request->is('*/admin*')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access Denied: Admin panel is disabled in Demo Mode.'], 403);
            }
            return redirect()->route('dashboard')->with('error', 'Access Denied: Superuser administration is disabled in Demo Mode.');
        }

        // 2. Strictly block backup restore & session termination
        if ($request->is('settings/backup/restore*') || $request->is('settings/sessions*') || $request->is('passkeys*')) {
            if ($request->isMethod('GET')) {
                // Allow viewing settings/security overview
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Security settings modifications are disabled in Demo Mode.'], 403);
            }
            return redirect()->back()->with('error', 'Security settings and backup restorations are disabled in Demo Mode.');
        }

        // 3. Intercept state-modifying HTTP methods (POST, PUT, PATCH, DELETE)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // Whitelisted read-only / simulation / exit routes:
            $allowedRoutes = [
                'logout',
                'demo/exit',
            ];

            foreach ($allowedRoutes as $allowed) {
                if ($request->is($allowed)) {
                    return $next($request);
                }
            }

            // Allow in-memory calculations (e.g. SmartSplit pro-rata calculation) that do NOT write to database
            if ($request->is('receipts/*/split')) {
                return $next($request);
            }

            // Block all other mutations with an informative message
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Demo Sandbox Restriction: Data modifications are disabled to protect demo data integrity.',
                    'demo_restricted' => true,
                ], 403);
            }

            return redirect()->back()->with('error', 'Demo Sandbox Restriction: Adding, editing, or deleting data is disabled in Read-Only Demo Mode.');
        }

        return $next($request);
    }
}
