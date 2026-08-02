<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Show the login screen.
     */
    public function showLogin(Request $request): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $dbPath = config('database.connections.sqlite.database');
        $userCount = \App\Models\User::count();
        $user = \App\Models\User::where('email', $credentials['email'])->first();
        $hashCheck = $user ? \Illuminate\Support\Facades\Hash::check($credentials['password'], $user->password) : false;
        $attemptResult = Auth::attempt($credentials, $request->boolean('remember'));

        \Illuminate\Support\Facades\Log::info('LOGIN_DEBUG', [
            'db' => $dbPath,
            'users' => $userCount,
            'user_found' => !!$user,
            'hash_check' => $hashCheck,
            'attempt' => $attemptResult,
        ]);

        if ($attemptResult) {
            $request->session()->regenerate();
            return redirect()->intended('/')->with('success', 'Logged in successfully.');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Destroy an authenticated session (Logout).
     */
    public function logout(Request $request): Response|RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Inertia::location(route('login'));
    }
}
