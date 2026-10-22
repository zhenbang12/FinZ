<?php

namespace App\Http\Controllers;

use App\Services\DemoDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DemoController extends Controller
{
    protected DemoDataService $demoDataService;

    public function __construct(DemoDataService $demoDataService)
    {
        $this->demoDataService = $demoDataService;
    }

    /**
     * Authenticate visitor into the FinZ demo session via private link only.
     */
    public function access(Request $request, ?string $token = null): RedirectResponse
    {
        // 1. Verify demo mode is globally enabled
        if (!config('demo.enabled', true)) {
            abort(404, 'Demo mode is currently disabled.');
        }

        // 2. Strict Link-Only Token Authentication
        $expectedToken = config('demo.token', env('DEMO_ACCESS_TOKEN', 'finz-preview-access'));
        $providedToken = $token ?: $request->query('token');

        if (!empty($expectedToken)) {
            if (empty($providedToken) || !hash_equals((string) $expectedToken, (string) $providedToken)) {
                Log::warning("Unauthorized demo access attempt from IP: {$request->ip()}");
                return redirect()->route('login')->with('error', 'Private Demo Access Required: A valid demo link with an authorized access token is required.');
            }
        }

        // 3. Ensure Demo User & Demonstration Data exist and are healthy
        $demoUser = $this->demoDataService->ensureDemoData();

        // 4. Invalidate old session and regenerate to prevent session fixation
        $request->session()->invalidate();
        $request->session()->regenerate();

        // 5. Authenticate Demo User
        Auth::login($demoUser);

        // 6. Explicitly tag this session as an immutable demo session
        $request->session()->put('is_demo_session', true);

        return redirect()->route('dashboard')->with(
            'success',
            'Welcome to the FinZ Interactive Demo! Explore all features in read-only sandbox mode.'
        );
    }

    /**
     * Safely terminate the demo session.
     */
    public function exit(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->forget('is_demo_session');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have exited the FinZ demo session.');
    }
}
