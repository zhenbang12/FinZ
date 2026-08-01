<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    /**
     * Display Active Sessions & Device Security Page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentSessionId = session()->getId();

        $rawSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get();

        $sessions = $rawSessions->map(function ($s) use ($currentSessionId) {
            $agent = $s->user_agent ?: '';
            
            return [
                'id' => $s->id,
                'ip_address' => $s->ip_address ?: 'Unknown IP',
                'device_name' => $this->parseDeviceName($agent),
                'browser' => $this->parseBrowser($agent),
                'platform' => $this->parsePlatform($agent),
                'is_current_device' => ($s->id === $currentSessionId),
                'last_activity' => date('Y-m-d H:i:s', $s->last_activity),
                'last_activity_human' => \Illuminate\Support\Carbon::createFromTimestamp($s->last_activity)->diffForHumans(),
            ];
        });

        return Inertia::render('Security/Index', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Log out from a specific session/device.
     */
    public function destroySession(Request $request, string $sessionId)
    {
        $user = $request->user();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete();

        return redirect()->back()->with('success', 'Logged out device session successfully.');
    }

    /**
     * Log out from all other devices except current one.
     */
    public function logoutOtherDevices(Request $request)
    {
        $user = $request->user();
        $currentSessionId = session()->getId();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        return redirect()->back()->with('success', 'Successfully logged out all other active devices.');
    }

    protected function parseDeviceName(string $agent): string
    {
        if (str_contains($agent, 'iPhone')) return 'iPhone';
        if (str_contains($agent, 'iPad')) return 'iPad';
        if (str_contains($agent, 'Android')) return 'Android Phone';
        if (str_contains($agent, 'Windows')) return 'Windows PC';
        if (str_contains($agent, 'Macintosh') || str_contains($agent, 'Mac OS')) return 'MacBook / Mac';
        if (str_contains($agent, 'Linux')) return 'Linux PC';
        return 'Unknown Device';
    }

    protected function parseBrowser(string $agent): string
    {
        if (str_contains($agent, 'Chrome') && !str_contains($agent, 'Edg')) return 'Google Chrome';
        if (str_contains($agent, 'Safari') && !str_contains($agent, 'Chrome')) return 'Apple Safari';
        if (str_contains($agent, 'Edg')) return 'Microsoft Edge';
        if (str_contains($agent, 'Firefox')) return 'Mozilla Firefox';
        return 'Browser';
    }

    protected function parsePlatform(string $agent): string
    {
        if (str_contains($agent, 'iPhone') || str_contains($agent, 'iPad')) return 'iOS';
        if (str_contains($agent, 'Android')) return 'Android';
        if (str_contains($agent, 'Windows')) return 'Windows';
        if (str_contains($agent, 'Macintosh') || str_contains($agent, 'Mac OS')) return 'macOS';
        if (str_contains($agent, 'Linux')) return 'Linux';
        return 'OS';
    }
}
