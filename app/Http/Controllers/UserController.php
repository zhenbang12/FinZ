<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of all users for Superusers.
     */
    public function index(Request $request): Response
    {
        $users = User::withCount(['accounts', 'transactions', 'receipts'])
            ->orderBy('is_admin', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        $allUsersList = User::select('id', 'name', 'email', 'is_admin')->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'allUsersList' => $allUsersList,
        ]);
    }

    /**
     * Store a newly created user account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'is_admin' => ['required', 'boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'],
        ]);

        return redirect()->back()->with('success', "User account '{$validated['name']}' created successfully.");
    }

    /**
     * Update the specified user account.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'is_admin' => ['required', 'boolean'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => $validated['is_admin'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->back()->with('success', "User account '{$user->name}' updated successfully.");
    }

    /**
     * Delete the specified user account.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $currentUserId = $request->user()?->id;

        if ($user->id === $currentUserId) {
            return redirect()->back()->with('error', 'You cannot delete your own logged-in user account.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->back()->with('success', "User account '{$userName}' deleted successfully.");
    }

    /**
     * Switch current user session (Superuser utility).
     */
    public function switchUser(Request $request, User $user): RedirectResponse
    {
        auth()->login($user);

        return redirect()->route('dashboard')->with('success', "Switched user session to '{$user->name}'.");
    }
}
