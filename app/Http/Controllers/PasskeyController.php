<?php

namespace App\Http\Controllers;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PasskeyController extends Controller
{
    /**
     * Get challenge & options for registering a new Passkey.
     */
    public function registerOptions(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!\Illuminate\Support\Facades\Schema::hasTable('passkeys')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {}
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('passkeys')) {
            return response()->json(['message' => 'Passkeys feature is currently being set up. Please try again in a minute.'], 500);
        }

        $challenge = $this->base64urlEncode(random_bytes(32));
        session(['passkey_register_challenge' => $challenge]);

        // Get existing credential IDs to prevent re-registering
        $existingCredentials = Passkey::where('user_id', $user->id)
            ->pluck('credential_id')
            ->map(fn($id) => ['id' => $id, 'type' => 'public-key'])
            ->toArray();

        $userIdBase64Url = $this->base64urlEncode('user_' . $user->id);

        return response()->json([
            'challenge' => $challenge,
            'rp' => [
                'name' => config('app.name', 'FinZ'),
                'id' => $request->getHost(),
            ],
            'user' => [
                'id' => $userIdBase64Url,
                'name' => $user->email,
                'displayName' => $user->name ?: $user->email,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],  // ES256
                ['type' => 'public-key', 'alg' => -257], // RS256
            ],
            'timeout' => 60000,
            'attestation' => 'none',
            'excludeCredentials' => $existingCredentials,
            'authenticatorSelection' => [
                'residentKey' => 'preferred',
                'userVerification' => 'preferred',
            ],
        ]);
    }

    /**
     * Store a newly created Passkey credential.
     */
    public function register(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'id' => 'required|string',
            'rawId' => 'required|string',
            'response' => 'required|array',
            'name' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $storedChallenge = session('passkey_register_challenge');
        session()->forget('passkey_register_challenge');

        if (!$storedChallenge) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['message' => 'Registration challenge expired. Please try again.']);
            }
            return response()->json(['message' => 'Registration challenge expired. Please try again.'], 422);
        }

        $deviceName = $request->name ?: $this->getDeviceName($request->header('User-Agent', ''));

        Passkey::create([
            'user_id' => $user->id,
            'name' => $deviceName,
            'credential_id' => $request->id,
            'public_key' => json_encode($request->response),
            'counter' => 0,
        ]);

        if ($request->header('X-Inertia')) {
            return redirect()->back()->with('success', 'Passkey registered successfully!');
        }

        return response()->json(['success' => true, 'message' => 'Passkey registered successfully!']);
    }

    /**
     * Get challenge & options for logging in with a Passkey.
     */
    public function loginOptions(Request $request): JsonResponse
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('passkeys')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {}
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('passkeys')) {
            return response()->json(['message' => 'Passkeys feature is currently being set up. Please try again in a minute.'], 500);
        }

        $challenge = $this->base64urlEncode(random_bytes(32));
        session(['passkey_login_challenge' => $challenge]);

        return response()->json([
            'challenge' => $challenge,
            'timeout' => 60000,
            'userVerification' => 'preferred',
            'rpId' => $request->getHost(),
        ]);
    }

    /**
     * Verify Passkey signature and authenticate user session.
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('passkeys')) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['email' => 'Passkeys database table does not exist.']);
            }
            return response()->json(['message' => 'Passkeys database table does not exist.'], 500);
        }

        $request->validate([
            'id' => 'required|string',
            'response' => 'required|array',
        ]);

        $storedChallenge = session('passkey_login_challenge');
        session()->forget('passkey_login_challenge');

        if (!$storedChallenge) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['email' => 'Login challenge expired. Please try again.']);
            }
            return response()->json(['message' => 'Login challenge expired. Please try again.'], 422);
        }

        $passkey = Passkey::where('credential_id', $request->id)->first();

        if (!$passkey) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['email' => 'Passkey not recognized. Please sign in with your email & password.']);
            }
            return response()->json(['message' => 'Passkey not recognized. Please sign in with your email & password.'], 404);
        }

        $user = $passkey->user;

        if (!$user) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['email' => 'User associated with this passkey was not found.']);
            }
            return response()->json(['message' => 'User associated with this passkey was not found.'], 404);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($request->header('X-Inertia')) {
            return redirect()->intended('/')->with('success', 'Logged in successfully with Passkey!');
        }

        return response()->json([
            'success' => true,
            'redirect' => route('dashboard'),
            'message' => 'Logged in successfully with Passkey!',
        ]);
    }

    /**
     * Delete a registered Passkey.
     */
    public function destroy(Request $request, Passkey $passkey): RedirectResponse
    {
        if ($passkey->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized passkey action.');
        }

        $passkey->delete();

        return redirect()->back()->with('success', 'Passkey removed successfully.');
    }

    protected function getDeviceName(string $agent): string
    {
        if (str_contains($agent, 'iPhone')) return 'Google Passkey / Touch ID (iPhone)';
        if (str_contains($agent, 'iPad')) return 'Google Passkey / Touch ID (iPad)';
        if (str_contains($agent, 'Android')) return 'Google Passkey (Android)';
        if (str_contains($agent, 'Windows')) return 'Windows Hello / Google Passkey';
        if (str_contains($agent, 'Macintosh') || str_contains($agent, 'Mac OS')) return 'Touch ID / Passkey (Mac)';
        return 'Google Passkey / Security Key';
    }

    protected function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
