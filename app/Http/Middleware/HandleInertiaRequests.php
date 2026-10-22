<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $isDemo = (bool) (
            $request->session()->get('is_demo_session') ||
            ($request->user() && $request->user()->email === config('demo.user_email', 'demo@finz.app'))
        );

        $demoToken = config('demo.token');
        $demoAccessUrl = url('/demo' . (!empty($demoToken) ? '?token=' . $demoToken : ''));

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    ...$request->user()->toArray(),
                    'is_demo' => $isDemo,
                ] : null,
            ],
            'is_demo' => $isDemo,
            'demo_access_url' => $demoAccessUrl,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
