<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        if (Auth::check() && !$request->session()->has('explicit_logout')) {
            return redirect()->route('dashboard');
        }

        $users = User::select('id', 'name', 'email', 'is_admin')->get();

        return Inertia::render('Auth/Login', [
            'users' => $users,
        ]);
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

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $request->session()->forget('explicit_logout');

            return redirect()->intended('/')->with('success', 'Logged in successfully.');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Destroy an authenticated session (Logout).
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('explicit_logout', true);

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
